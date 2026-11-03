<?php

namespace App\Services\Arquivos;

use App\Auditing\ArquivoAuditSnapshot;
use App\Auditing\AuditContext;
use App\Auditing\AuditEventCollector;
use App\Auditing\AuditTransaction;
use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditEntity;
use App\Enums\StatusDerivacoes;
use App\Models\Arquivo;
use App\Models\ItemAcervo;
use App\Models\User;
use App\Support\OptimizedImageResult;
use App\Support\OptimizedImageVersions;
use Illuminate\Support\Facades\Gate;
use RuntimeException;
use Throwable;

/**
 * Recria as versões otimizadas a partir do original preservado.
 *
 * No modo completo, as versões passam para o formato configurado e as versões que
 * repetiriam as dimensões de uma menor são removidas (é assim que um acervo em JPEG
 * migra para WebP). No modo "somente ausentes", nada que já existe é tocado.
 *
 * Os arquivos novos são gravados antes da transação, sem bloquear o item durante o
 * processamento. Na transação, cada versão gerada com sucesso troca de registro; a
 * versão que falhou mantém o derivado antigo. Os arquivos antigos só são apagados
 * depois do commit, e uma falha em qualquer ponto remove apenas os arquivos novos.
 */
final readonly class RegenerarVersoesOtimizadas
{
    public const ORIGINAL_MUDOU = 'O arquivo original mudou durante a regeneração das versões.';

    public function __construct(
        private AuditTransaction $auditTransaction,
        private GerenciarArquivoOriginal $arquivos,
        private OptimizedImageVersions $optimizedVersions,
    ) {}

    /**
     * @param  User|null  $actor  null quando a regeneração parte do console (sistema).
     * @param  bool  $somenteAusentes  gera apenas as versões que o item ainda não tem.
     * @param  string|null  $sha256Esperado  original que o chamador espera processar (job da fila).
     */
    public function execute(
        ?User $actor,
        ItemAcervo $item,
        AuditContext $context,
        bool $somenteAusentes = false,
        ?string $sha256Esperado = null,
    ): OptimizedImageResult {
        $original = $item->arquivos()->where('versao_arquivo', 'original')->firstOrFail();

        if ($sha256Esperado !== null && $original->sha256 !== $sha256Esperado) {
            throw new RuntimeException(self::ORIGINAL_MUDOU);
        }

        if ($actor instanceof User) {
            $context->assertActor($actor);
            Gate::forUser($actor)->authorize('update', $item);
            Gate::forUser($actor)->authorize('replaceOriginal', $original);
        }

        if (! $original->isImagem()) {
            return $this->finish($item, $original, new OptimizedImageResult(applicable: false));
        }

        $versions = $this->optimizedVersions->versions();

        if ($somenteAusentes) {
            $existentes = $item->arquivos()->whereIn('versao_arquivo', $versions)->pluck('versao_arquivo')->all();
            $versions = array_values(array_diff($this->optimizedVersions->expectedVersions($original), $existentes));
        }

        if ($versions === []) {
            return $this->finish($item, $original, new OptimizedImageResult(applicable: true));
        }

        // Gravado fora da transação: se o processo morrer no meio, o original fica marcado.
        $statusAnterior = $original->derivacoes_status;
        $this->optimizedVersions->markStatus($original, StatusDerivacoes::Processando);
        $journal = $this->arquivos->journal();

        try {
            ['files' => $files, 'failed' => $failedVersions, 'dispensed' => $dispensed] = $this->optimizedVersions->render(
                $item,
                $original,
                $versions,
                $journal,
            );
            $redundantes = $somenteAusentes ? [] : $dispensed;

            if ($files === [] && $failedVersions === [] && ! $item->arquivos()->whereIn('versao_arquivo', $redundantes)->exists()) {
                return $this->finish($item, $original, new OptimizedImageResult(applicable: true, dispensedVersions: $dispensed));
            }

            $generated = $this->auditTransaction->run(
                $context,
                fn (AuditEventCollector $audit): array => $this->register(
                    $item,
                    $original,
                    $files,
                    $failedVersions,
                    $dispensed,
                    $redundantes,
                    $somenteAusentes,
                    $audit,
                    $journal,
                ),
            );
        } catch (Throwable $exception) {
            $journal->rollbackCreated();
            // As versões anteriores foram mantidas; um original substituído no meio já tem o próprio estado.
            $this->optimizedVersions->markStatus($original, $statusAnterior, onlyFrom: StatusDerivacoes::Processando);

            throw $exception;
        }

        $journal->cleanupObsolete();

        return new OptimizedImageResult(
            applicable: true,
            generated: $generated,
            failedVersions: $failedVersions,
            dispensedVersions: $dispensed,
        );
    }

    /**
     * @param  array<string, array<string, mixed>>  $files
     * @param  list<string>  $failedVersions
     * @param  list<string>  $dispensed
     * @param  list<string>  $redundantes  versões dispensadas cujos registros antigos devem sair
     * @return list<Arquivo>
     */
    private function register(
        ItemAcervo $item,
        Arquivo $original,
        array $files,
        array $failedVersions,
        array $dispensed,
        array $redundantes,
        bool $somenteAusentes,
        AuditEventCollector $audit,
        StorageMutationJournal $journal,
    ): array {
        // Inclui a lixeira: o job da fila gera as versões de um item excluído logicamente,
        // para que ele volte completo ao ser restaurado.
        ItemAcervo::query()->withTrashed()->lockForUpdate()->findOrFail($item->getKey());
        $atual = Arquivo::query()->lockForUpdate()->find($original->getKey());

        // O original pode ter sido substituído enquanto as versões eram geradas.
        if (
            ! $atual instanceof Arquivo
            || $atual->storage_path !== $original->storage_path
            || $atual->sha256 !== $original->sha256
        ) {
            throw new RuntimeException(self::ORIGINAL_MUDOU);
        }

        $removed = [];
        $generated = [];

        foreach ($files as $version => $attributes) {
            $antigo = $item->arquivos()->where('versao_arquivo', $version)->lockForUpdate()->first();

            if ($antigo instanceof Arquivo) {
                $journal->obsolete($antigo->provider, $antigo->storage_path);
                $removed[] = $antigo;
                $antigo->delete();
            }

            $generated[] = $item->arquivos()->create($attributes);
        }

        foreach ($item->arquivos()->whereIn('versao_arquivo', $redundantes)->lockForUpdate()->get() as $redundante) {
            $journal->obsolete($redundante->provider, $redundante->storage_path);
            $removed[] = $redundante;
            $redundante->delete();
        }

        $this->optimizedVersions->recordStatus($item, $original, new OptimizedImageResult(
            applicable: true,
            failedVersions: $failedVersions,
            dispensedVersions: $dispensed,
        ));

        $audit->capture(
            action: AuditAction::Regenerated,
            subjectType: AuditEntity::Arquivo,
            subjectId: $original->id,
            subjectLabel: $original->nome_original,
            metadata: [
                'operation' => 'arquivo_derivations_regenerate',
                'item_acervo' => ['id' => (int) $item->id, 'label' => $item->titulo],
                'mode' => $somenteAusentes ? 'ausentes' : 'todas',
                'derivations' => [
                    'applicable' => true,
                    'generated' => ArquivoAuditSnapshot::summaries($generated),
                    'failed_versions' => $failedVersions,
                    'dispensed_versions' => $redundantes,
                ],
                'derivations_removed' => ArquivoAuditSnapshot::summaries($removed),
            ],
        );

        return $generated;
    }

    /**
     * Fim sem nada a trocar: registra o estado que as versões persistidas já representam.
     */
    private function finish(ItemAcervo $item, Arquivo $original, OptimizedImageResult $result): OptimizedImageResult
    {
        $this->optimizedVersions->recordStatus($item, $original, $result);

        return $result;
    }
}
