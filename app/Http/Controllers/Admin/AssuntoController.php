<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ListagemPesquisavel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAssuntoRequest;
use App\Http\Requests\Admin\UpdateAssuntoRequest;
use App\Models\Assunto;
use App\Support\OrdenacaoListagem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AssuntoController extends Controller
{
    use ListagemPesquisavel;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Assunto::class);

        return $this->listagemPesquisavel(
            $request,
            Assunto::query()->withCount('itensAcervo'),
            ['titulo', 'descricao'],
            OrdenacaoListagem::fromRequest($request, ['titulo' => 'titulo', 'itens' => 'itens_acervo_count'], 'titulo', comecamDecrescentes: ['itens']),
            'admin.assuntos.index',
            'assuntos',
        );
    }

    public function create(): View
    {
        Gate::authorize('create', Assunto::class);

        return view('admin.assuntos.create');
    }

    public function store(StoreAssuntoRequest $request): RedirectResponse
    {
        $assunto = Assunto::create($request->validated());

        return redirect()
            ->route('admin.assuntos.index')
            ->with('success', 'Assunto cadastrado com sucesso.');
    }

    public function edit(Assunto $assunto): View
    {
        Gate::authorize('update', $assunto);

        return view('admin.assuntos.edit', [
            'assunto' => $assunto,
        ]);
    }

    public function update(UpdateAssuntoRequest $request, Assunto $assunto): RedirectResponse
    {
        $assunto->update($request->validated());

        return redirect()
            ->route('admin.assuntos.index')
            ->with('success', 'Assunto atualizado com sucesso.');
    }

    public function destroy(Assunto $assunto): RedirectResponse
    {
        Gate::authorize('delete', $assunto);

        $assunto->delete();

        return redirect()
            ->route('admin.assuntos.index')
            ->with('success', 'Assunto excluído com sucesso.');
    }
}
