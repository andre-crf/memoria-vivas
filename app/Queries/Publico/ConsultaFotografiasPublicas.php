<?php

namespace App\Queries\Publico;

use App\Enums\TipoData;
use App\Models\Arquivo;
use App\Models\Assunto;
use App\Models\Categoria;
use App\Models\ItemAcervo;
use App\Queries\Concerns\PesquisaTextual;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;

class ConsultaFotografiasPublicas
{
    use PesquisaTextual;

    private const VERSOES_PUBLICAS = ['thumbnail', 'medium', 'large'];

    /**
     * @return Builder<ItemAcervo>
     */
    public function query(): Builder
    {
        return $this->queryElegivel()
            ->with([
                'arquivos' => fn (HasMany $query) => $this->restringirAImagensPublicas($query),
            ]);
    }

    /**
     * @return Collection<int, ItemAcervo>
     */
    public function recentes(int $limite): Collection
    {
        return $this->ordenadasPorRecencia()
            ->limit($limite)
            ->get();
    }

    /**
     * @return LengthAwarePaginator<ItemAcervo>
     */
    public function paginadas(int $porPagina, FiltrosCatalogoPublico $filtros): LengthAwarePaginator
    {
        $query = $this->ordenadasPorRecencia();

        if ($filtros->termo !== '') {
            $this->restringirAoTermo($query, $filtros->termo);
        }

        if ($filtros->decada !== null) {
            $this->restringirADecada($query, $filtros->decada);
        }

        if ($filtros->categoriaId !== null) {
            $query->whereHas(
                'categorias',
                fn (Builder $categorias) => $categorias->whereKey($filtros->categoriaId),
            );
        }

        if ($filtros->assuntoId !== null) {
            $query->whereHas(
                'assuntos',
                fn (Builder $assuntos) => $assuntos->whereKey($filtros->assuntoId),
            );
        }

        return $query->paginate($porPagina);
    }

    /** @return array<int, string> */
    public function decadasDisponiveis(): array
    {
        return $this->queryElegivel()
            ->distinct()
            ->get(['tipo_data', 'ano', 'decada'])
            ->map(function (ItemAcervo $fotografia): ?string {
                if ($fotografia->tipo_data === TipoData::Decada) {
                    return $fotografia->decada;
                }

                if ($fotografia->ano === null || $fotografia->tipo_data === TipoData::Desconhecida) {
                    return null;
                }

                return (string) (intdiv($fotografia->ano, 10) * 10);
            })
            ->filter()
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    /** @return Collection<int, Categoria> */
    public function categoriasDisponiveis(): Collection
    {
        return Categoria::query()
            ->whereHas('itensAcervo', fn (Builder $itens) => $this->restringirAItensPublicos($itens))
            ->orderBy('titulo')
            ->get(['id', 'titulo']);
    }

    /** @return Collection<int, Assunto> */
    public function assuntosDisponiveis(): Collection
    {
        return Assunto::query()
            ->whereHas('itensAcervo', fn (Builder $itens) => $this->restringirAItensPublicos($itens))
            ->orderBy('titulo')
            ->get(['id', 'titulo']);
    }

    public function porIdentificador(int|string $identificador): ?ItemAcervo
    {
        return $this->query()->find($identificador);
    }

    public function detalhePorIdentificador(int|string $identificador): ?ItemAcervo
    {
        return $this->query()
            ->with([
                'autor' => fn (BelongsTo $query) => $query
                    ->select(['autores.id', 'autores.nome']),
                'categorias' => fn (BelongsToMany $query) => $query
                    ->select(['categorias.id', 'categorias.titulo'])
                    ->orderBy('categorias.titulo')
                    ->orderBy('categorias.id'),
                'assuntos' => fn (BelongsToMany $query) => $query
                    ->select(['assuntos.id', 'assuntos.titulo'])
                    ->orderBy('assuntos.titulo')
                    ->orderBy('assuntos.id'),
                'palavrasChave' => fn (BelongsToMany $query) => $query
                    ->select(['palavras_chave.id', 'palavras_chave.termo'])
                    ->orderBy('palavras_chave.termo')
                    ->orderBy('palavras_chave.id'),
                'pessoas' => fn (BelongsToMany $query) => $query
                    ->select(['pessoas.id', 'pessoas.nome'])
                    ->orderBy('pessoas.nome')
                    ->orderBy('pessoas.id'),
                'colecoes' => fn (BelongsToMany $query) => $query
                    ->select(['colecoes.id', 'colecoes.titulo'])
                    ->where('colecoes.status', 'publicada')
                    ->orderBy('colecoes.titulo')
                    ->orderBy('colecoes.id'),
                'conjuntosContextuais' => fn (BelongsToMany $query) => $query
                    ->select(['conjuntos_contextuais.id', 'conjuntos_contextuais.titulo'])
                    ->orderByPivot('ordem')
                    ->orderBy('conjuntos_contextuais.id'),
            ])
            ->find($identificador);
    }

    /**
     * @param  Builder<Arquivo>|HasMany<Arquivo, ItemAcervo>  $query
     */
    private function restringirAImagensPublicas(Builder|HasMany $query): void
    {
        $query
            ->where('provider', 'local')
            ->where('tipo_arquivo', 'imagem')
            ->where('mime_type', 'like', 'image/%')
            ->whereIn('versao_arquivo', self::VERSOES_PUBLICAS);
    }

    /**
     * @param  Builder<ItemAcervo>  $query
     */
    private function restringirAItensPublicos(Builder $query): void
    {
        $query
            ->where('tipo_item', 'fotografia')
            ->where('status', 'publicado')
            ->where('visibilidade', 'publico')
            ->whereHas('arquivos', fn (Builder $arquivos) => $this->restringirAImagensPublicas($arquivos));
    }

    /**
     * @param  Builder<ItemAcervo>  $query
     */
    private function restringirAoTermo(Builder $query, string $termo): void
    {
        // Só campos já exibidos na página pública; a localização física do original é administrativa.
        $query->where(fn (Builder $pesquisa) => $this->contemTermo($pesquisa, ['titulo', 'legenda', 'local_atual', 'local_epoca'], $termo));
    }

    /**
     * @param  Builder<ItemAcervo>  $query
     */
    private function restringirADecada(Builder $query, string $decada): void
    {
        $anoInicial = (int) $decada;

        $query->where(function (Builder $datas) use ($decada, $anoInicial): void {
            $datas
                ->where(function (Builder $aproximadas) use ($decada): void {
                    $aproximadas
                        ->where('tipo_data', TipoData::Decada->value)
                        ->where('decada', $decada);
                })
                ->orWhere(function (Builder $conhecidas) use ($anoInicial): void {
                    $conhecidas
                        ->whereIn('tipo_data', [
                            TipoData::DataExata->value,
                            TipoData::MesAno->value,
                            TipoData::Ano->value,
                        ])
                        ->whereBetween('ano', [$anoInicial, $anoInicial + 9]);
                });
        });
    }

    /**
     * @return Builder<ItemAcervo>
     */
    private function ordenadasPorRecencia(): Builder
    {
        return $this->query()
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /**
     * @return Builder<ItemAcervo>
     */
    private function queryElegivel(): Builder
    {
        $query = ItemAcervo::query();
        $this->restringirAItensPublicos($query);

        return $query;
    }
}
