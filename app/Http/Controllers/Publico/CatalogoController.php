<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Queries\Publico\ConsultaFotografiasPublicas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class CatalogoController extends Controller
{
    public function index(Request $request, ConsultaFotografiasPublicas $consulta): Response
    {
        $validator = Validator::make($request->query(), [
            'q' => ['nullable', 'string', 'max:200'],
        ], [
            'q.string' => 'Digite um texto válido para pesquisar no catálogo.',
            'q.max' => 'A pesquisa deve ter no máximo 200 caracteres.',
        ]);
        $invalid = $validator->fails();
        $termo = $invalid ? '' : trim($validator->validated()['q'] ?? '');
        $query = $consulta->query();

        if ($invalid) {
            $query->whereRaw('1 = 0');
        } elseif ($termo !== '') {
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $termo).'%';
            $query->where(function (Builder $query) use ($pattern): void {
                foreach (['titulo', 'legenda', 'local_atual', 'local_epoca'] as $field) {
                    $query->orWhereRaw("{$field} LIKE ? ESCAPE '!'", [$pattern]);
                }
            });
        }

        return response()->view('public.catalogo', [
            'termo' => $termo,
            'searchError' => $validator->errors()->first('q'),
            'fotografias' => $query->orderByDesc('created_at')->orderByDesc('id')
                ->paginate(12)->appends($termo !== '' ? ['q' => $termo] : []),
        ], $invalid ? 422 : 200);
    }

    public function show(string $fotografia, ConsultaFotografiasPublicas $consulta): View
    {
        $item = $consulta->porIdentificador($fotografia);
        abort_unless($item, 404);
        $item->load('autor');

        return view('public.fotografia', ['fotografia' => $item]);
    }
}
