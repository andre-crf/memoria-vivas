<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Queries\Publico\ConsultaFotografiasPublicas;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CatalogoController extends Controller
{
    private const FOTOGRAFIAS_POR_PAGINA = 12;

    public function __invoke(Request $request, ConsultaFotografiasPublicas $consulta): View
    {
        return view('public.catalogo', [
            'fotografias' => $consulta
                ->paginadas(self::FOTOGRAFIAS_POR_PAGINA)
                ->withQueryString(),
            'termo' => trim((string) $request->query('q', '')),
        ]);
    }
}
