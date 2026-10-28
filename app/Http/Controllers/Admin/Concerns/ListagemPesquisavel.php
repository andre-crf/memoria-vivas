<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Queries\Concerns\PesquisaTextual;
use App\Support\OrdenacaoListagem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

/**
 * Pesquisa textual, ordenação e paginação das listagens administrativas.
 *
 * Como no catálogo público, uma pesquisa inválida é ignorada e a página responde 422
 * com a mensagem; a paginação leva o termo e a ordenação nos links.
 */
trait ListagemPesquisavel
{
    use PesquisaTextual;

    private int $porPaginaDaListagem = 15;

    /**
     * Termo validado e a mensagem de erro, quando houver.
     *
     * @return array{0: string, 1: string|null}
     */
    protected function pesquisa(Request $request): array
    {
        $validator = Validator::make($request->query(), [
            'q' => ['nullable', 'string', 'max:200'],
        ], [
            'q.string' => 'Digite um texto válido para pesquisar.',
            'q.max' => 'A pesquisa deve ter no máximo 200 caracteres.',
        ]);

        if ($validator->fails()) {
            return ['', $validator->errors()->first('q')];
        }

        return [trim((string) ($validator->validated()['q'] ?? '')), null];
    }

    /**
     * @param  Builder<Model>  $query  já com as contagens
     * @param  list<string>  $colunas  colunas pesquisadas pelo termo
     * @param  array<string, mixed>  $dados  dados extras para a view
     */
    protected function listagemPesquisavel(
        Request $request,
        Builder $query,
        array $colunas,
        OrdenacaoListagem $ordenacao,
        string $view,
        string $chave,
        array $dados = [],
    ): Response {
        [$termo, $erro] = $this->pesquisa($request);

        if ($termo !== '') {
            $query->where(fn (Builder $pesquisa) => $this->contemTermo($pesquisa, $colunas, $termo));
        }

        // Pesquisa que os links dos cabeçalhos levam adiante (sem ordenação nem página).
        $parametros = $termo !== '' ? ['q' => $termo] : [];

        return response()->view($view, [
            ...$dados,
            $chave => $ordenacao->aplicar($query)
                ->paginate($this->porPaginaDaListagem)
                ->appends([...$parametros, ...$ordenacao->parametros()]),
            'termo' => $termo,
            'searchError' => $erro,
            'ordenacao' => $ordenacao,
            'parametros' => $parametros,
        ], $erro !== null ? 422 : 200);
    }
}
