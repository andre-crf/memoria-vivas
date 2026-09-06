<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoriaRequest;
use App\Http\Requests\Admin\UpdateCategoriaRequest;
use App\Models\Categoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CategoriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        Gate::authorize('viewAny', Categoria::class);

        $categorias = Categoria::query()
            ->withCount('itensAcervo')
            ->orderBy('titulo')
            ->get();

        return view('admin.categorias.index', [
            'categorias' => $categorias
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', Categoria::class);

        return view('admin.categorias.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoriaRequest $request): RedirectResponse
    {
        Categoria::create($request->validated());

        return redirect()
            ->route('admin.categorias.index')
            ->with('success', 'Categoria cadastrada com sucesso.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Categoria $categoria): View
    {
        Gate::authorize('update', Categoria::class);

        return view('admin.categorias.edit', [
            'categoria' => $categoria,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoriaRequest $request, Categoria $categoria): RedirectResponse
    {
        $categoria->update($request->validated());

        return redirect()
            ->route('admin.categorias.index')
            ->with('success', 'Categoria atualizada com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
