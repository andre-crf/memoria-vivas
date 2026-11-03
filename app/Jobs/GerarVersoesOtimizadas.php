<?php

namespace App\Jobs;

use App\Auditing\AuditContext;
use App\Auditing\Enums\AuditSource;
use App\Enums\StatusDerivacoes;
use App\Models\Arquivo;
use App\Models\ItemAcervo;
use App\Models\User;
use App\Services\Arquivos\RegenerarVersoesOtimizadas;
use App\Support\OptimizedImageVersions;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Gera as versões otimizadas de um original enviado no modo assíncrono.
 *
 * O job leva só o id e o SHA-256 do original esperado. Na execução, ele confere que
 * esse original ainda existe e não foi substituído, e reserva o processamento com uma
 * troca condicional de estado no banco: um segundo job do mesmo original (ou uma nova
 * entrega) encerra sem fazer nada. A geração em si é a da regeneração, no modo "somente
 * ausentes", com as mesmas regras de bloqueio, comparação do original e limpeza.
 */
class GerarVersoesOtimizadas implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120];

    // Igual ao --timeout do worker e abaixo do DB_QUEUE_RETRY_AFTER (360 s). Uma geração
    // pendente ou em processamento há mais que isso é tratada como sem progresso.
    public const TIMEOUT = 300;

    public int $timeout = self::TIMEOUT;

    /**
     * @param  bool  $todas  regenera todas as versões (pedido manual); senão, só as ausentes.
     * @param  int|null  $solicitanteId  quem pediu a regeneração, para a auditoria.
     */
    public function __construct(
        public readonly int $arquivoId,
        public readonly string $sha256,
        public readonly string $correlationId,
        public readonly bool $todas = false,
        public readonly ?int $solicitanteId = null,
    ) {
        $this->onQueue(config('acervo.optimized_processing.queue', 'imagens'));
        $this->afterCommit();
    }

    public function handle(RegenerarVersoesOtimizadas $regenerar, OptimizedImageVersions $versoes): void
    {
        $original = $this->originalEsperado();
        $item = $original instanceof Arquivo
            ? ItemAcervo::query()->withTrashed()->find($original->item_acervo_id)
            : null;

        // Substituído (o job do arquivo novo cuida dele) ou excluído definitivamente.
        if (! $original instanceof Arquivo || ! $item instanceof ItemAcervo) {
            return;
        }

        if (! $this->reservar($original)) {
            return;
        }

        try {
            $resultado = $regenerar->execute(
                actor: null,
                item: $item,
                context: $this->auditContext(),
                somenteAusentes: ! $this->todas,
                sha256Esperado: $this->sha256,
            );
        } catch (Throwable $exception) {
            if (! $this->originalEsperado() instanceof Arquivo) {
                Log::info('Geração de versões abandonada: o original foi substituído ou excluído.', $this->logContext());

                return;
            }

            // Libera a reserva para a próxima tentativa.
            $versoes->markStatus($original, StatusDerivacoes::Pendente, onlyFrom: StatusDerivacoes::Processando);

            throw $exception;
        }

        // Falha parcial vem no resultado, sem exceção; o serviço já registrou "falhou".
        if ($resultado->failedVersions === []) {
            return;
        }

        if ($this->attempts() < $this->tries && ! $this->job instanceof SyncJob) {
            $versoes->markStatus($original, StatusDerivacoes::Pendente, onlyFrom: StatusDerivacoes::Falhou);
            $this->release($this->backoff[$this->attempts() - 1] ?? end($this->backoff));

            return;
        }

        $this->fail(new RuntimeException('Versões otimizadas não geradas: '.implode(', ', $resultado->failedVersions).'.'));
    }

    /**
     * Tentativas esgotadas, tempo limite ou falha definitiva.
     */
    public function failed(?Throwable $exception): void
    {
        $original = $this->originalEsperado();

        if ($original instanceof Arquivo) {
            app(OptimizedImageVersions::class)->markStatus(
                $original,
                StatusDerivacoes::Falhou,
                onlyFrom: [StatusDerivacoes::Pendente, StatusDerivacoes::Processando],
            );
        }

        Log::warning('A geração das versões otimizadas falhou definitivamente.', [
            ...$this->logContext(),
            'erro' => $exception?->getMessage(),
        ]);
    }

    private function originalEsperado(): ?Arquivo
    {
        return Arquivo::query()
            ->whereKey($this->arquivoId)
            ->where('versao_arquivo', 'original')
            ->where('sha256', $this->sha256)
            ->first();
    }

    /**
     * As permissões foram conferidas na requisição; aqui o solicitante só identifica o evento.
     */
    private function auditContext(): AuditContext
    {
        $solicitante = $this->solicitanteId !== null ? User::query()->find($this->solicitanteId) : null;

        return $solicitante instanceof User
            ? AuditContext::forUser($solicitante, AuditSource::Job, (string) Str::uuid(), $this->correlationId)
            : AuditContext::forSystem(AuditSource::Job, (string) Str::uuid(), $this->correlationId);
    }

    /**
     * Passa o original para "processando" se ele estiver pendente ou com falha (um job
     * esgotado reenviado por queue:retry), ou preso em "processando" há mais que o tempo
     * limite (worker encerrado no meio).
     */
    private function reservar(Arquivo $original): bool
    {
        $agora = now();
        $reservado = Arquivo::query()
            ->whereKey($original->getKey())
            ->where('sha256', $this->sha256)
            ->where(fn ($query) => $query
                ->whereIn('derivacoes_status', [StatusDerivacoes::Pendente->value, StatusDerivacoes::Falhou->value])
                ->orWhere(fn ($preso) => $preso
                    ->where('derivacoes_status', StatusDerivacoes::Processando->value)
                    ->where('derivacoes_atualizadas_em', '<', $agora->copy()->subSeconds($this->timeout))))
            ->update([
                'derivacoes_status' => StatusDerivacoes::Processando->value,
                'derivacoes_atualizadas_em' => $agora,
            ]) > 0;

        if ($reservado) {
            $original->forceFill(['derivacoes_status' => StatusDerivacoes::Processando, 'derivacoes_atualizadas_em' => $agora]);
        }

        return $reservado;
    }

    /** @return array<string, mixed> */
    private function logContext(): array
    {
        return ['arquivo_id' => $this->arquivoId, 'correlation_id' => $this->correlationId];
    }
}
