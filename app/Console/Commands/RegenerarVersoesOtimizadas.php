<?php

namespace App\Console\Commands;

use App\Auditing\AuditContext;
use App\Auditing\Enums\AuditSource;
use App\Models\ItemAcervo;
use App\Services\Arquivos\RegenerarVersoesOtimizadas as RegenerarVersoes;
use App\Support\OptimizedImageVersions;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

/**
 * Regenera as versões otimizadas (thumbnail, medium, large) a partir dos originais.
 *
 * Cada fotografia é processada na sua própria transação: uma falha não interrompe
 * o lote. Toda a execução compartilha o mesmo correlation_id na auditoria.
 */
class RegenerarVersoesOtimizadas extends Command
{
    protected $signature = 'acervo:regenerar-versoes
        {fotografia?* : IDs das fotografias (ex.: 12 15)}
        {--ausentes : Gera apenas as versões que estão faltando}
        {--todas : Processa todas as fotografias com original de imagem}
        {--lote=50 : Quantidade de fotografias carregadas por vez}';

    protected $description = 'Regenera as versões otimizadas das fotografias a partir do original preservado';

    public function handle(RegenerarVersoes $regenerar, OptimizedImageVersions $versoes): int
    {
        $ids = array_map('intval', (array) $this->argument('fotografia'));
        $somenteAusentes = (bool) $this->option('ausentes');
        $lote = (int) $this->option('lote');

        if ($ids === [] && ! $somenteAusentes && ! $this->option('todas')) {
            $this->error('Informe os IDs das fotografias, --ausentes ou --todas.');

            return self::INVALID;
        }

        if ($lote < 1) {
            $this->error('O tamanho do lote deve ser maior que zero.');

            return self::INVALID;
        }

        $apenasIncompletas = $somenteAusentes && ! $this->option('todas') && $ids === [];
        $consulta = $this->consulta($ids);
        $correlationId = (string) Str::uuid();
        $resumo = ['processadas' => 0, 'geradas' => 0, 'falhas' => 0, 'ignoradas' => 0];

        if ($ids !== []) {
            $encontrados = (clone $consulta)->pluck('id')->all();

            foreach (array_diff($ids, $encontrados) as $id) {
                $this->warn("#{$id}: fotografia não encontrada ou sem original de imagem.");
                $resumo['ignoradas']++;
            }
        }

        $consulta->chunkById($lote, function (Collection $fotografias) use ($regenerar, $versoes, $somenteAusentes, $apenasIncompletas, $correlationId, &$resumo): void {
            foreach ($fotografias as $fotografia) {
                if ($apenasIncompletas && ! $this->incompleta($fotografia, $versoes)) {
                    continue;
                }

                $resumo['processadas']++;

                try {
                    $resultado = $regenerar->execute(
                        actor: null,
                        item: $fotografia,
                        context: AuditContext::forSystem(AuditSource::Console, (string) Str::uuid(), $correlationId),
                        somenteAusentes: $somenteAusentes,
                    );
                } catch (Throwable $exception) {
                    $resumo['falhas']++;
                    $this->error("#{$fotografia->id} {$fotografia->titulo}: {$exception->getMessage()}");

                    continue;
                }

                $geradas = collect($resultado->generated)->pluck('versao_arquivo')->join(', ');
                $resumo['geradas'] += count($resultado->generated);

                if ($resultado->failedVersions !== []) {
                    $resumo['falhas']++;
                    $falhas = implode(', ', $resultado->failedVersions);
                    $this->warn("#{$fotografia->id} {$fotografia->titulo}: falha em {$falhas}".($geradas !== '' ? "; geradas: {$geradas}" : ''));
                } elseif ($geradas === '') {
                    $this->line("#{$fotografia->id} {$fotografia->titulo}: nada a gerar.");
                } else {
                    $this->info("#{$fotografia->id} {$fotografia->titulo}: {$geradas}");
                }
            }
        });

        $this->newLine();
        $this->line(sprintf(
            '%d fotografia(s) processada(s), %d versão(ões) gerada(s), %d com falha, %d ignorada(s).',
            $resumo['processadas'],
            $resumo['geradas'],
            $resumo['falhas'],
            $resumo['ignoradas'],
        ));

        return $resumo['falhas'] > 0 || $resumo['ignoradas'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Fotografias com original de imagem, com os arquivos carregados para conferir as versões.
     *
     * @param  list<int>  $ids
     * @return Builder<ItemAcervo>
     */
    private function consulta(array $ids): Builder
    {
        return ItemAcervo::query()
            ->with('arquivos')
            ->where('tipo_item', 'fotografia')
            ->whereHas('arquivos', fn (Builder $arquivo) => $arquivo
                ->where('versao_arquivo', 'original')
                ->where('mime_type', 'like', 'image/%'))
            ->when($ids !== [], fn (Builder $query) => $query->whereKey($ids));
    }

    /**
     * Falta alguma versão esperada? As dispensadas (dimensões repetidas) não contam.
     */
    private function incompleta(ItemAcervo $fotografia, OptimizedImageVersions $versoes): bool
    {
        $original = $fotografia->arquivos->firstWhere('versao_arquivo', 'original');

        return array_diff($versoes->expectedVersions($original), $fotografia->arquivos->pluck('versao_arquivo')->all()) !== [];
    }
}
