<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Estado;
use Illuminate\Http\JsonResponse;

class LocalidadeController extends Controller
{
    /** Cidades de um estado, em ordem alfabética, para o campo de cidade do formulário de download. */
    public function municipios(Estado $estado): JsonResponse
    {
        return response()->json($estado->municipios()->orderBy('nome')->get(['id', 'nome']));
    }
}
