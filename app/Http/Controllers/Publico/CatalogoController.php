<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Queries\Publico\ConsultaFotografiasPublicas;
use App\Queries\Publico\FiltrosCatalogoPublico;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CatalogoController extends Controller
{
    private const FOTOGRAFIAS_POR_PAGINA = 12;

    public function __invoke(Request $request, ConsultaFotografiasPublicas $consulta): View
    {
        $decadas = $consulta->decadasDisponiveis();
        $categorias = $consulta->categoriasDisponiveis();
        $assuntos = $consulta->assuntosDisponiveis();
        $filtros = new FiltrosCatalogoPublico(
            termo: $this->texto($request, 'q'),
            decada: $this->opcaoTextual($request, 'decada', $decadas),
            categoriaId: $this->opcaoNumerica($request, 'categoria', $categorias->modelKeys()),
            assuntoId: $this->opcaoNumerica($request, 'assunto', $assuntos->modelKeys()),
        );

        return view('public.catalogo', [
            'fotografias' => $consulta
                ->paginadas(self::FOTOGRAFIAS_POR_PAGINA, $filtros)
                ->appends($filtros->parametros()),
            'filtros' => $filtros,
            'decadas' => $decadas,
            'categorias' => $categorias,
            'assuntos' => $assuntos,
        ]);
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
