<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Queries\Publico\ConsultaFotografiasPublicas;
use App\Queries\Publico\FiltrosCatalogoPublico;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class CatalogoController extends Controller
{
    private const FOTOGRAFIAS_POR_PAGINA = 12;

    public function __invoke(Request $request, ConsultaFotografiasPublicas $consulta): Response
    {
        $validator = Validator::make($request->query(), [
            'q' => ['nullable', 'string', 'max:200'],
        ], [
            'q.string' => 'Digite um texto válido para pesquisar no catálogo.',
            'q.max' => 'A pesquisa deve ter no máximo 200 caracteres.',
        ]);
        $pesquisaInvalida = $validator->fails();
        $decadas = $consulta->decadasDisponiveis();
        $categorias = $consulta->categoriasDisponiveis();
        $assuntos = $consulta->assuntosDisponiveis();
        $filtros = new FiltrosCatalogoPublico(
            termo: $pesquisaInvalida ? '' : trim((string) ($validator->validated()['q'] ?? '')),
            decada: $this->opcaoTextual($request, 'decada', $decadas),
            categoriaId: $this->opcaoNumerica($request, 'categoria', $categorias->modelKeys()),
            assuntoId: $this->opcaoNumerica($request, 'assunto', $assuntos->modelKeys()),
        );

        return response()->view('public.catalogo', [
            'fotografias' => $consulta
                ->paginadas(self::FOTOGRAFIAS_POR_PAGINA, $filtros)
                ->appends($filtros->parametros()),
            'filtros' => $filtros,
            'decadas' => $decadas,
            'categorias' => $categorias,
            'assuntos' => $assuntos,
            'searchError' => $validator->errors()->first('q'),
        ], $pesquisaInvalida ? 422 : 200);
    }

    private function texto(Request $request, string $parametro): string
    {
        $valor = $request->query($parametro);

        return is_string($valor) ? trim($valor) : '';
    }

    /** @param array<int, string> $opcoes */
    private function opcaoTextual(Request $request, string $parametro, array $opcoes): ?string
    {
        $valor = $this->texto($request, $parametro);

        return in_array($valor, $opcoes, true) ? $valor : null;
    }

    /** @param array<int, int|string> $opcoes */
    private function opcaoNumerica(Request $request, string $parametro, array $opcoes): ?int
    {
        $valor = $this->texto($request, $parametro);

        if ($valor === '' || ! ctype_digit($valor) || (int) $valor < 1) {
            return null;
        }

        $id = (int) $valor;

        return in_array($id, array_map('intval', $opcoes), true) ? $id : null;
    }
}
