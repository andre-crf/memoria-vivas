<?php

namespace App\Console\Commands;

use App\Models\Estado;
use App\Models\Municipio;
use App\Models\Pais;
use App\Services\Ibge\IbgeLocalidadesClient;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sincroniza países, UFs e municípios a partir da API do IBGE.
 *
 * Roda sob demanda (ou agendado); a aplicação nunca consulta o IBGE em runtime.
 * É idempotente e nunca remove registros: localidades extintas continuam na
 * base porque podem estar referenciadas por downloads já registrados.
 */
class SincronizarLocalidades extends Command
{
    protected $signature = 'localidades:sync
        {--paises : Sincroniza apenas os países}
        {--estados : Sincroniza apenas as UFs}
        {--municipios : Sincroniza apenas os municípios}
        {--uf=* : Limita os municípios às UFs informadas (ex.: --uf=PR --uf=SP)}';

    protected $description = 'Sincroniza países, UFs e municípios com a API de localidades do IBGE';

    private int $falhas = 0;

    public function handle(IbgeLocalidadesClient $ibge): int
    {
        $tudo = ! $this->option('paises') && ! $this->option('estados') && ! $this->option('municipios');

        Log::info('localidades:sync iniciado');

        if ($tudo || $this->option('paises')) {
            $this->sincronizarPaises($ibge);
        }

        if ($tudo || $this->option('estados')) {
            $this->sincronizarEstados($ibge);
        }

        if ($tudo || $this->option('municipios')) {
            $this->sincronizarMunicipios($ibge);
        }

        if ($this->falhas > 0) {
            $this->error("Sincronização concluída com {$this->falhas} falha(s). Veja os logs.");
            Log::error('localidades:sync concluído com falhas', ['falhas' => $this->falhas]);

            return self::FAILURE;
        }

        $this->info('Sincronização concluída.');
        Log::info('localidades:sync concluído com sucesso');

        return self::SUCCESS;
    }

    private function sincronizarPaises(IbgeLocalidadesClient $ibge): void
    {
        $paises = $this->buscar('países', fn () => $ibge->paises());

        if ($paises === null) {
            return;
        }

        $gravados = 0;

        foreach ($paises as $pais) {
            $codigo = $pais['id']['ISO-ALPHA-2'] ?? null;
            $nome = is_array($pais['nome'] ?? null) ? ($pais['nome']['abreviado'] ?? null) : ($pais['nome'] ?? null);

            if (! is_string($codigo) || strlen($codigo) !== 2 || ! is_string($nome) || $nome === '') {
                Log::warning('localidades:sync ignorou país sem código ISO ou nome', ['pais' => $pais]);

                continue;
            }

            // `ativo` fica de fora: é decisão da aplicação, não do IBGE.
            Pais::updateOrCreate(['codigo' => strtoupper($codigo)], ['nome' => $nome]);
            $gravados++;
        }

        $this->info("Países sincronizados: {$gravados}");
        Log::info('localidades:sync gravou países', ['total' => $gravados]);
    }

    private function sincronizarEstados(IbgeLocalidadesClient $ibge): void
    {
        $estados = $this->buscar('UFs', fn () => $ibge->estados());

        if ($estados === null) {
            return;
        }

        $gravados = 0;

        foreach ($estados as $estado) {
            $codigoIbge = isset($estado['id']) ? str_pad((string) $estado['id'], 2, '0', STR_PAD_LEFT) : null;
            $sigla = $estado['sigla'] ?? null;
            $nome = $estado['nome'] ?? null;

            if (! $codigoIbge || ! is_string($sigla) || ! is_string($nome)) {
                Log::warning('localidades:sync ignorou UF incompleta', ['estado' => $estado]);

                continue;
            }

            Estado::updateOrCreate(
                ['codigo_ibge' => $codigoIbge],
                ['sigla' => strtoupper($sigla), 'nome' => $nome],
            );
            $gravados++;
        }

        $this->info("UFs sincronizadas: {$gravados}");
        Log::info('localidades:sync gravou UFs', ['total' => $gravados]);
    }

    private function sincronizarMunicipios(IbgeLocalidadesClient $ibge): void
    {
        $siglas = array_map('strtoupper', (array) $this->option('uf'));

        $estados = Estado::query()
            ->when($siglas !== [], fn ($query) => $query->whereIn('sigla', $siglas))
            ->orderBy('sigla')
            ->get();

        if ($estados->isEmpty()) {
            $this->warn('Nenhuma UF na base. Rode `localidades:sync --estados` antes.');
            Log::warning('localidades:sync não encontrou UFs para sincronizar municípios');
            $this->falhas++;

            return;
        }

        $total = 0;

        foreach ($estados as $estado) {
            $municipios = $this->buscar(
                "municípios de {$estado->sigla}",
                fn () => $ibge->municipiosDaUf($estado->sigla),
            );

            if ($municipios === null) {
                continue; // uma UF que falhou não interrompe as demais
            }

            $linhas = [];

            foreach ($municipios as $municipio) {
                $codigoIbge = isset($municipio['id']) ? str_pad((string) $municipio['id'], 7, '0', STR_PAD_LEFT) : null;
                $nome = $municipio['nome'] ?? null;

                if (! $codigoIbge || ! is_string($nome)) {
                    Log::warning('localidades:sync ignorou município incompleto', ['municipio' => $municipio]);

                    continue;
                }

                $linhas[] = ['codigo_ibge' => $codigoIbge, 'estado_id' => $estado->id, 'nome' => $nome];
            }

            foreach (array_chunk($linhas, 500) as $lote) {
                Municipio::upsert($lote, ['codigo_ibge'], ['estado_id', 'nome']);
            }

            $total += count($linhas);
            $this->line("  {$estado->sigla}: ".count($linhas).' municípios');
        }

        $this->info("Municípios sincronizados: {$total}");
        Log::info('localidades:sync gravou municípios', ['total' => $total]);
    }

    /**
     * Executa uma chamada ao IBGE isolando a falha: retorna null e contabiliza.
     *
     * @param  \Closure(): array<int, array<string, mixed>>  $chamada
     * @return array<int, array<string, mixed>>|null
     */
    private function buscar(string $descricao, \Closure $chamada): ?array
    {
        try {
            return $chamada();
        } catch (ConnectionException|RequestException $e) {
            $this->falhas++;
            $this->error("Falha ao buscar {$descricao}: {$e->getMessage()}");
            Log::error('localidades:sync falhou ao consultar o IBGE', [
                'recurso' => $descricao,
                'erro' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            $this->falhas++;
            $this->error("Erro inesperado ao processar {$descricao}: {$e->getMessage()}");
            Log::error('localidades:sync erro inesperado', [
                'recurso' => $descricao,
                'erro' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
