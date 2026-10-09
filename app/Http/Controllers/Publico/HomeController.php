<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Queries\Publico\ConsultaFotografiasPublicas;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(ConsultaFotografiasPublicas $fotografias): View
    {
        return view('public.home', [
            'fotografias' => $fotografias->recentes(5),
        ]);
    }
}
