<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePalavraChaveRequest;
use App\Http\Requests\Admin\UpdatePalavraChaveRequest;
use App\Models\PalavraChave;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PalavraChaveController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', PalavraChave::class);

        $palavrasChave = PalavraChave::query()
            ->withCount('itensAcervo')
            ->orderBy('termo')
            ->get();

        return view('admin.palavras-chave.index', [
            'palavrasChave' => $palavrasChave,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', PalavraChave::class);

        return view('admin.palavras-chave.create');
    }

    public function store(StorePalavraChaveRequest $request): RedirectResponse
    {
        PalavraChave::create($request->validated());

        return redirect()
            ->route('admin.palavras-chave.index')
            ->with('success', 'Palavra-chave cadastrada com sucesso.');
    }

    public function edit(PalavraChave $palavraChave): View
    {
        Gate::authorize('update', $palavraChave);

        return view('admin.palavras-chave.edit', [
            'palavraChave' => $palavraChave,
        ]);
    }

    public function update(UpdatePalavraChaveRequest $request, PalavraChave $palavraChave): RedirectResponse
    {
        $palavraChave->update($request->validated());

        return redirect()
            ->route('admin.palavras-chave.index')
            ->with('success', 'Palavra-chave atualizada com sucesso.');
    }

    public function destroy(PalavraChave $palavraChave): RedirectResponse
    {
        Gate::authorize('delete', $palavraChave);

        $palavraChave->delete();

        return redirect()
            ->route('admin.palavras-chave.index')
            ->with('success', 'Palavra-chave excluída com sucesso.');
    }
}
