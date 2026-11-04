<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ListagemPesquisavel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAutorRequest;
use App\Http\Requests\Admin\UpdateAutorRequest;
use App\Models\Autor;
use App\Support\OrdenacaoListagem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AutorController extends Controller
{
    use ListagemPesquisavel;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Autor::class);

        return $this->listagemPesquisavel(
            $request,
            Autor::query()->withCount('itensAcervo'),
            ['nome', 'observacao'],
            OrdenacaoListagem::fromRequest($request, ['nome' => 'nome', 'tipo' => OrdenacaoListagem::porValores('tipo', ['pessoa', 'instituicao']), 'itens' => 'itens_acervo_count'], 'nome', comecamDecrescentes: ['itens']),
            'admin.autores.index',
            'autores',
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        Gate::authorize('viewAny', Autor::class);

        return view('admin.autores.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAutorRequest $request): RedirectResponse
    {
        $autor = Autor::create($request->validated());

        return redirect()
            ->route('admin.autores.index')
            ->with('success', 'Autor cadastrado com sucesso.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Autor $autor): View
    {
        Gate::authorize('update', $autor);

        return view('admin.autores.edit', [
            'autor' => $autor,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAutorRequest $request, Autor $autor): RedirectResponse
    {
        $autor->update($request->validated());

        return redirect()
            ->route('admin.autores.index')
            ->with('success', 'Autor atualizado com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Autor $autor): RedirectResponse
    {
        Gate::authorize('delete', Autor::class);

        $autor->delete();

        return redirect()
            ->route('admin.autores.index')
            ->with('success', 'Autor excluído com sucesso.');
    }
}
