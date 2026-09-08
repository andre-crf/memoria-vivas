<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAssuntoRequest;
use App\Http\Requests\Admin\UpdateAssuntoRequest;
use App\Models\Assunto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AssuntoController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Assunto::class);

        $assuntos = Assunto::query()
            ->withCount('itensAcervo')
            ->orderBy('titulo')
            ->get();

        return view('admin.assuntos.index', [
            'assuntos' => $assuntos,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Assunto::class);

        return view('admin.assuntos.create');
    }

    public function store(StoreAssuntoRequest $request): RedirectResponse
    {
        Assunto::create($request->validated());

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
