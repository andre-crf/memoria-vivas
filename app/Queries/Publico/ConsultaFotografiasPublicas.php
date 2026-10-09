<?php

namespace App\Queries\Publico;

use App\Models\Arquivo;
use App\Models\ItemAcervo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;

class ConsultaFotografiasPublicas
{
    private const VERSOES_PUBLICAS = ['thumbnail', 'medium', 'large'];

    /**
     * @return Builder<ItemAcervo>
     */
    public function query(): Builder
    {
        return ItemAcervo::query()
            ->where('tipo_item', 'fotografia')
            ->where('status', 'publicado')
            ->where('visibilidade', 'publico')
            ->whereHas('arquivos', fn (Builder $query) => $this->restringirAImagensPublicas($query))
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
    public function paginadas(int $porPagina): LengthAwarePaginator
    {
        return $this->ordenadasPorRecencia()->paginate($porPagina);
    }

    public function porIdentificador(int|string $identificador): ?ItemAcervo
    {
        return $this->query()->find($identificador);
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
     * @return Builder<ItemAcervo>
     */
    private function ordenadasPorRecencia(): Builder
    {
        return $this->query()
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
}
