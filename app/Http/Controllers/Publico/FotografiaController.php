<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\ItemAcervo;
use App\Queries\Publico\ConsultaFotografiasPublicas;
use Illuminate\Contracts\View\View;

class FotografiaController extends Controller
{
    public function show(
        ItemAcervo $fotografia,
        ConsultaFotografiasPublicas $fotografias,
    ): View {
        $fotografiaPublica = $fotografias->porIdentificador($fotografia->getKey());

        abort_unless($fotografiaPublica, 404);

        return view('public.fotografia', [
            'fotografia' => $fotografiaPublica,
        ]);
    }
}
