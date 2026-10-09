<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Queries\Publico\ConsultaFotografiasPublicas;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

class FotografiaController extends Controller
{
    public function show(string $fotografia, ConsultaFotografiasPublicas $consulta): View
    {
        $fotografia = $consulta->porIdentificador($fotografia);

        abort_unless($fotografia, Response::HTTP_NOT_FOUND);

        return view('public.fotografia', [
            'fotografia' => $fotografia,
        ]);
    }
}
