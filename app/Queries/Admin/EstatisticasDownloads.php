<?php

namespace App\Queries\Admin;

use App\Models\Estado;
use App\Models\ItemAcervo;
use App\Models\MotivoDownload;
use App\Models\Municipio;
use App\Models\Pais;
use App\Models\PerfilDownload;
use App\Models\RegistroDownload;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Consultas do relatório de downloads, também usadas pelo resumo do painel.
 *
 * Os agrupamentos devolvem objetos { chave, rotulo, total }, do mais frequente ao menos frequente;
 * a chave nula (ex.: perfil não informado em downloads antigos) vira "Não informado".
 */
class EstatisticasDownloads
{
    /** Datas digitadas nos filtros e exibidas no relatório seguem o horário de Umuarama. */
    public const FUSO = 'America/Sao_Paulo';

    public const NAO_INFORMADO = 'Não informado';

    /**
     * Registros filtrados por busca (fotografia, número, nome ou e-mail) e período (datas inclusivas, Y-m-d).
     *
     * @return Builder<RegistroDownload>
     */
    public function consulta(string $busca = '', ?string $de = null, ?string $ate = null): Builder
    {
        $busca = trim($busca);
        $query = RegistroDownload::query();

        if ($busca !== '') {
            $query->where(function (Builder $registros) use ($busca): void {
                $termo = "%{$busca}%";
                $registros
                    ->where('nome', 'like', $termo)
                    ->orWhere('email', 'like', $termo)
                    ->orWhereHas('itemAcervo', fn (Builder $itens) => $itens->where('titulo', 'like', $termo))
                    // Fotografias excluídas definitivamente só têm o título gravado no download.
                    ->orWhere(fn (Builder $excluidas) => $excluidas->whereNull('item_acervo_id')->where('titulo_item', 'like', $termo));

                if (preg_match('/^#?(\d+)$/', $busca, $matches) === 1) {
                    $registros->orWhere('item_acervo_id', (int) $matches[1]);
                }
            });
        }
        if ($de !== null) {
            $query->where('created_at', '>=', $this->data($de)->startOfDay()->utc());
        }
        if ($ate !== null) {
            $query->where('created_at', '<=', $this->data($ate)->endOfDay()->utc());
        }

        return $query;
    }

    /** @param  Builder<RegistroDownload>  $query */
    public function total(Builder $query): int
    {
        return (clone $query)->count();
    }

    /** @param  Builder<RegistroDownload>  $query */
    public function fotografiasDistintas(Builder $query): int
    {
        return (clone $query)->distinct()->count('item_acervo_id');
    }

    /** @param  Builder<RegistroDownload>  $query */
    public function ultimoDownload(Builder $query): ?Carbon
    {
        return (clone $query)->orderByDesc('created_at')->first(['created_at'])?->created_at;
    }

    /**
     * @param  Builder<RegistroDownload>  $query
     * @return Collection<int, object{chave: int|null, rotulo: string, total: int}>
     */
    public function perfis(Builder $query, ?int $limite = null): Collection
    {
        return $this->agrupar($query, 'perfil_download_id', fn (array $ids) => PerfilDownload::whereIn('id', $ids)->pluck('titulo', 'id'), $limite);
    }

    /**
     * @param  Builder<RegistroDownload>  $query
     * @return Collection<int, object{chave: int|null, rotulo: string, total: int}>
     */
    public function motivos(Builder $query): Collection
    {
        return $this->agrupar($query, 'motivo_download_id', fn (array $ids) => MotivoDownload::whereIn('id', $ids)->pluck('titulo', 'id'));
    }

    /**
     * Fotografias mais baixadas. Downloads de fotografias excluídas definitivamente formam um grupo só (chave nula).
     *
     * @param  Builder<RegistroDownload>  $query
     * @return Collection<int, object{chave: int|null, item_acervo_id: int|null, rotulo: string, total: int, item: ItemAcervo|null}>
     */
    public function maisBaixadas(Builder $query, int $limite = 5): Collection
    {
        $grupos = $this->agrupar($query, 'item_acervo_id', fn (): Collection => collect(), $limite);
        $itens = ItemAcervo::withTrashed()->whereIn('id', $grupos->pluck('chave')->filter()->all())->get(['id', 'titulo', 'deleted_at'])->keyBy('id');

        return $grupos->map(function (object $grupo) use ($itens): object {
            $item = $grupo->chave !== null ? $itens->get($grupo->chave) : null;

            return (object) [
                'chave' => $grupo->chave,
                'item_acervo_id' => $grupo->chave,
                'rotulo' => $grupo->chave === null ? 'Fotografias excluídas' : ($item->titulo ?? 'Fotografia removida'),
                'total' => $grupo->total,
                'item' => $item,
            ];
        });
    }

    /**
     * @param  Builder<RegistroDownload>  $query
     * @return Collection<int, object{chave: int|null, rotulo: string, total: int}>
     */
    public function paises(Builder $query, ?int $limite = null): Collection
    {
        return $this->agrupar($query, 'pais_id', fn (array $ids) => Pais::whereIn('id', $ids)->pluck('nome', 'id'), $limite);
    }

    /**
     * Estados de origem, só entre os downloads feitos do Brasil.
     *
     * @param  Builder<RegistroDownload>  $query
     * @return Collection<int, object{chave: int|null, rotulo: string, total: int}>
     */
    public function estados(Builder $query, ?int $limite = null): Collection
    {
        return $this->agrupar(
            $this->doBrasil($query),
            'estado_id',
            fn (array $ids) => Estado::whereIn('id', $ids)->get(['id', 'nome', 'sigla'])->mapWithKeys(fn (Estado $estado): array => [$estado->id => "{$estado->nome} ({$estado->sigla})"]),
            $limite,
        );
    }

    /**
     * Cidades de origem (Brasil), com a sigla do estado.
     *
     * @param  Builder<RegistroDownload>  $query
     * @return Collection<int, object{chave: int|null, rotulo: string, total: int}>
     */
    public function municipios(Builder $query, ?int $limite = null): Collection
    {
        return $this->agrupar(
            $this->doBrasil($query),
            'municipio_id',
            fn (array $ids) => Municipio::with('estado:id,sigla')->whereIn('id', $ids)->get(['id', 'nome', 'estado_id'])->mapWithKeys(fn (Municipio $municipio): array => [$municipio->id => "{$municipio->nome}/{$municipio->estado?->sigla}"]),
            $limite,
        );
    }

    /**
     * Perfis dos solicitantes em cada país de origem: linhas por país, com os perfis do mais ao menos frequente.
     *
     * @param  Builder<RegistroDownload>  $query
     * @return Collection<int, object{rotulo: string, total: int, perfis: Collection<int, object{rotulo: string, total: int}>}>
     */
    public function perfisPorPais(Builder $query, int $limitePaises = 5): Collection
    {
        $paises = $this->paises($query, $limitePaises);
        if ($paises->isEmpty()) {
            return collect();
        }

        // Poucas combinações (países x perfis): agrupa tudo e separa por país em memória.
        $linhas = (clone $query)
            ->selectRaw('pais_id, perfil_download_id, COUNT(*) as total')
            ->groupBy('pais_id', 'perfil_download_id')
            ->orderByDesc('total')
            ->orderBy('perfil_download_id')
            ->toBase()
            ->get()
            ->groupBy(fn (object $linha): string => (string) $linha->pais_id);
        $titulosPerfis = PerfilDownload::pluck('titulo', 'id');

        return $paises->map(fn (object $pais): object => (object) [
            'rotulo' => $pais->rotulo,
            'total' => $pais->total,
            'perfis' => $linhas->get((string) $pais->chave, collect())
                ->map(fn (object $linha): object => (object) [
                    'rotulo' => $linha->perfil_download_id !== null ? $titulosPerfis->get($linha->perfil_download_id, self::NAO_INFORMADO) : self::NAO_INFORMADO,
                    'total' => (int) $linha->total,
                ])
                ->values(),
        ]);
    }

    /**
     * Downloads ao longo do tempo, em até 14 barras. Sem período, mostra os últimos 14 dias.
     *
     * @param  Builder<RegistroDownload>  $query
     * @return array{serie: list<array{rotulo: string, total: int}>, maiorDia: int, periodo: string}
     */
    public function serie(Builder $query, ?string $de = null, ?string $ate = null): array
    {
        $inicio = match (true) {
            $de !== null => $this->data($de)->startOfDay(),
            $ate !== null => $this->data($ate)->subDays(13)->startOfDay(),
            default => Carbon::now(self::FUSO)->subDays(13)->startOfDay(),
        };
        $fim = $ate !== null ? $this->data($ate)->endOfDay() : Carbon::now(self::FUSO)->endOfDay();
        if ($inicio->greaterThan($fim)) {
            $fim = $inicio->copy()->endOfDay();
        }

        $dias = (int) $inicio->diffInDays($fim->copy()->startOfDay()) + 1;
        $passo = max(1, (int) ceil($dias / 14));
        $serie = [];
        for ($deslocamento = 0; $deslocamento < $dias; $deslocamento += $passo) {
            $inicioIntervalo = $inicio->copy()->addDays($deslocamento);
            $fimIntervalo = $inicioIntervalo->copy()->addDays($passo - 1)->min($fim);
            $serie[] = [
                'rotulo' => $passo === 1 ? $inicioIntervalo->format('d/m') : $inicioIntervalo->format('d/m').'–'.$fimIntervalo->format('d/m'),
                'total' => 0,
            ];
        }

        foreach ((clone $query)->whereBetween('created_at', [$inicio->copy()->utc(), $fim->copy()->utc()])->select('created_at')->cursor() as $registro) {
            $dia = $registro->created_at->copy()->timezone(self::FUSO)->startOfDay();
            $indice = intdiv((int) $inicio->diffInDays($dia), $passo);
            if (isset($serie[$indice])) {
                $serie[$indice]['total']++;
            }
        }

        return [
            'serie' => $serie,
            'maiorDia' => max(array_column($serie, 'total')),
            'periodo' => $inicio->format('d/m/Y').' a '.$fim->format('d/m/Y'),
        ];
    }

    /**
     * @param  Builder<RegistroDownload>  $query
     * @return Builder<RegistroDownload>
     */
    private function doBrasil(Builder $query): Builder
    {
        return (clone $query)->whereIn('pais_id', Pais::query()->where('codigo', 'BR')->select('id'));
    }

    /**
     * @param  Builder<RegistroDownload>  $query
     * @param  Closure(list<int>): Collection<int|string, string>  $rotulos  rótulos por chave
     * @return Collection<int, object{chave: int|null, rotulo: string, total: int}>
     */
    private function agrupar(Builder $query, string $coluna, Closure $rotulos, ?int $limite = null): Collection
    {
        $grupos = (clone $query)
            ->selectRaw("{$coluna}, COUNT(*) as total")
            ->groupBy($coluna)
            ->orderByDesc('total')
            ->orderBy($coluna)
            ->when($limite !== null, fn (Builder $grupos) => $grupos->limit($limite))
            ->toBase()
            ->get();
        $nomes = $rotulos($grupos->pluck($coluna)->filter()->map(fn ($id): int => (int) $id)->values()->all());

        return $grupos->map(function (object $grupo) use ($coluna, $nomes): object {
            $chave = $grupo->{$coluna} === null ? null : (int) $grupo->{$coluna};

            return (object) [
                'chave' => $chave,
                'rotulo' => $chave === null ? self::NAO_INFORMADO : ($nomes[$chave] ?? self::NAO_INFORMADO),
                'total' => (int) $grupo->total,
            ];
        });
    }

    private function data(string $data): Carbon
    {
        return Carbon::createFromFormat('!Y-m-d', $data, self::FUSO);
    }
}
