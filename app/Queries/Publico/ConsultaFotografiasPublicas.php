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
    public function paginadas(int $porPagina, ?string $termo = null): LengthAwarePaginator
    {
        $query = $this->ordenadasPorRecencia();

        if ($termo !== null && $termo !== '') {
            $this->restringirAoTermo($query, $termo);
        }

        return $query->paginate($porPagina);
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
     * @param  Builder<ItemAcervo>  $query
     */
    private function restringirAoTermo(Builder $query, string $termo): void
    {
        $termoEscapado = str_replace(
            ['!', '%', '_'],
            ['!!', '!%', '!_'],
            $termo,
        );
        $padrao = "%{$termoEscapado}%";

        $query->where(function (Builder $pesquisa) use ($padrao): void {
            $pesquisa
                ->whereRaw("titulo LIKE ? ESCAPE '!'", [$padrao])
                ->orWhereRaw("legenda LIKE ? ESCAPE '!'", [$padrao]);
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
}
