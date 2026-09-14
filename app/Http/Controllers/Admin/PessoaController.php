<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePessoaRequest;
use App\Http\Requests\Admin\UpdatePessoaRequest;
use App\Models\Pessoa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PessoaController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Pessoa::class);

        $pessoas = Pessoa::query()
            ->withCount('itensAcervo')
            ->orderBy('nome')
            ->get();

        return view('admin.pessoas.index', [
            'pessoas' => $pessoas,
        ]);
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
