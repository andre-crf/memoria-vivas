<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAutorRequest;
use App\Http\Requests\Admin\UpdateAutorRequest;
use App\Models\Autor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AutorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        Gate::authorize('viewAny', Autor::class);
        
        $autores = Autor::query()
            ->withCount('itensAcervo')
            ->orderBy('nome')
            ->get();

        return view('admin.autores.index', [
            'autores' => $autores
        ]);
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
        Autor::create($request->validated());

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
            'autor' => $autor
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
