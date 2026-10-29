<?php

namespace App\Queries\Admin;

use App\Enums\Visibilidade;
use App\Models\ItemAcervo;
use App\Queries\Concerns\PesquisaTextual;
use App\Support\OrdenacaoListagem;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Listagens administrativas de fotografias (ativas e lixeira) com pesquisa e filtros.
 */
class ConsultaFotografiasAdmin
{
    use PesquisaTextual;

    public const POR_PAGINA = 15;

    /** Colunas do item consultadas pela pesquisa, além do nome do autor e do ID. */
    private const COLUNAS_PESQUISADAS = ['titulo', 'legenda', 'local_atual', 'local_epoca', 'evento', 'cedente', 'localizacao_fisica'];

    /**
     * Colunas ordenáveis; a lixeira acrescenta a data de exclusão.
     *
     * @return array<string, string|Closure(Builder, string): mixed>
     */
    public static function ordenaveis(bool $lixeira = false): array
    {
        return [
            'recentes' => 'id',
            'titulo' => 'titulo',
            'data' => fn (Builder $query, string $direcao) => $query->ordenadoPorDataHistorica($direcao),
            'status' => OrdenacaoListagem::porValores('status', array_keys(ItemAcervo::STATUS)),
            'visibilidade' => OrdenacaoListagem::porValores('visibilidade', array_column(Visibilidade::cases(), 'value')),
            ...($lixeira ? ['exclusao' => 'deleted_at'] : []),
        ];
    }

    public static function ordenacao(Request $request, bool $lixeira = false): OrdenacaoListagem
    {
        return OrdenacaoListagem::fromRequest(
            $request,
            self::ordenaveis($lixeira),
            padrao: $lixeira ? 'exclusao' : 'recentes',
            direcaoPadrao: 'desc',
            comecamDecrescentes: ['recentes', 'data', 'exclusao'],
        );
    }

    /**
     * @return LengthAwarePaginator<ItemAcervo>
     */
    public function ativas(FiltrosFotografiasAdmin $filtros, OrdenacaoListagem $ordenacao, int $porPagina = self::POR_PAGINA): LengthAwarePaginator
    {
        $query = ItemAcervo::query()
            ->with(['arquivos' => fn ($query) => $query->where('versao_arquivo', 'thumbnail')]);

        return $this->paginar($query, $filtros, $ordenacao, $porPagina);
    }

    /**
     * Só registros excluídos logicamente.
     *
     * @return LengthAwarePaginator<ItemAcervo>
     */
    public function excluidas(FiltrosFotografiasAdmin $filtros, OrdenacaoListagem $ordenacao, int $porPagina = self::POR_PAGINA): LengthAwarePaginator
    {
        $query = ItemAcervo::query()
            ->onlyTrashed()
            ->with([
                'excluidoPor',
                'arquivos' => fn ($query) => $query->whereIn('versao_arquivo', ['thumbnail', 'medium', 'large', 'original']),
            ]);

        return $this->paginar($query, $filtros, $ordenacao, $porPagina);
    }

    /**
     * @param  Builder<ItemAcervo>  $query
     * @return LengthAwarePaginator<ItemAcervo>
     */
    private function paginar(Builder $query, FiltrosFotografiasAdmin $filtros, OrdenacaoListagem $ordenacao, int $porPagina): LengthAwarePaginator
    {
        $query->where('tipo_item', 'fotografia');

        if ($filtros->termo !== '') {
            $this->restringirAoTermo($query, $filtros->termo);
        }

        if ($filtros->status !== null) {
            $query->where('status', $filtros->status);
        }

        if ($filtros->visibilidade !== null) {
            $query->where('visibilidade', $filtros->visibilidade);
        }

        return $ordenacao->aplicar($query)
            ->paginate($porPagina)
            ->appends([...$filtros->parametros(), ...$ordenacao->parametros()]);
    }

    /**
     * Texto nas colunas do item ou no nome do autor; "#12" ou "12" também acham pelo ID.
     *
     * @param  Builder<ItemAcervo>  $query
     */
    private function restringirAoTermo(Builder $query, string $termo): void
    {
        $id = preg_match('/^#?(\d{1,18})$/', $termo, $numero) === 1 ? (int) $numero[1] : null;

        $query->where(function (Builder $pesquisa) use ($termo, $id): void {
            $this->contemTermo($pesquisa, self::COLUNAS_PESQUISADAS, $termo);
            $pesquisa->orWhereHas('autor', fn (Builder $autor) => $autor->where(
                fn (Builder $nome) => $this->contemTermo($nome, ['nome'], $termo),
            ));

            if ($id !== null) {
                $pesquisa->orWhere($pesquisa->getModel()->getQualifiedKeyName(), $id);
            }
        });
    }
}
