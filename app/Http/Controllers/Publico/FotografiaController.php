<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Queries\Publico\ConsultaFotografiasPublicas;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FotografiaController extends Controller
{
    public function show(
        Request $request,
        string $fotografia,
        ConsultaFotografiasPublicas $consulta,
    ): View {
        $fotografia = $consulta->detalhePorIdentificador($fotografia);

        abort_unless($fotografia, Response::HTTP_NOT_FOUND);

        return view('public.fotografia', [
            'fotografia' => $fotografia,
            'retornoCatalogo' => route('public.catalogo', $this->parametrosDeRetorno($request)),
        ]);
    }

    /** @return array<string, string|int> */
    private function parametrosDeRetorno(Request $request): array
    {
        $parametros = [];
        $termo = $this->texto($request, 'q');

        if ($termo !== '') {
            $parametros['q'] = $termo;
        }

        $decada = $this->texto($request, 'decada');

        if (preg_match('/^[0-9]{3}0$/', $decada) === 1) {
            $parametros['decada'] = $decada;
        }

        foreach (['categoria', 'assunto', 'page'] as $parametro) {
            $valor = $this->inteiroPositivo($request, $parametro);

            if ($valor !== null) {
                $parametros[$parametro] = $valor;
            }
        }

        return $parametros;
    }

    private function texto(Request $request, string $parametro): string
    {
        $valor = $request->query($parametro);

        return is_string($valor) ? trim($valor) : '';
    }

    private function inteiroPositivo(Request $request, string $parametro): ?int
    {
        $valor = $this->texto($request, $parametro);

        if ($valor === '' || ! ctype_digit($valor) || (int) $valor < 1) {
            return null;
        }

        return (int) $valor;
    }
}
