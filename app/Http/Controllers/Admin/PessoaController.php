<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ListagemPesquisavel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePessoaRequest;
use App\Http\Requests\Admin\UpdatePessoaRequest;
use App\Models\Pessoa;
use App\Support\OrdenacaoListagem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PessoaController extends Controller
{
    use ListagemPesquisavel;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Pessoa::class);

        return $this->listagemPesquisavel(
            $request,
            Pessoa::query()->withCount('itensAcervo'),
            ['nome', 'observacao'],
            OrdenacaoListagem::fromRequest($request, ['nome' => 'nome', 'itens' => 'itens_acervo_count'], 'nome', comecamDecrescentes: ['itens']),
            'admin.pessoas.index',
            'pessoas',
        );
    }

    public function create(): View
    {
        Gate::authorize('create', Pessoa::class);

        return view('admin.pessoas.create');
    }

    public function store(StorePessoaRequest $request): RedirectResponse
    {
        Pessoa::create($request->validated());

        return redirect()
            ->route('admin.pessoas.index')
            ->with('success', 'Pessoa cadastrada com sucesso.');
    }

    public function edit(Pessoa $pessoa): View
    {
        Gate::authorize('update', $pessoa);

        return view('admin.pessoas.edit', [
            'pessoa' => $pessoa,
        ]);
    }

    public function update(UpdatePessoaRequest $request, Pessoa $pessoa): RedirectResponse
    {
        $pessoa->update($request->validated());

        return redirect()
            ->route('admin.pessoas.index')
            ->with('success', 'Pessoa atualizada com sucesso.');
    }

    public function destroy(Pessoa $pessoa): RedirectResponse
    {
        Gate::authorize('delete', $pessoa);

        $pessoa->delete();

        return redirect()
            ->route('admin.pessoas.index')
            ->with('success', 'Pessoa excluída com sucesso.');
    }
}
