<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TipoData;
use App\Enums\Visibilidade;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFotografiaRequest;
use App\Http\Requests\Admin\UpdateFotografiaRequest;
use App\Models\ItemAcervo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class FotografiaController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', ItemAcervo::class);

        $fotografias = ItemAcervo::query()
            ->where('tipo_item', 'fotografia')
            ->latest('id')
            ->paginate(15);

        return view('admin.fotografias.index', [
            'fotografias' => $fotografias,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', ItemAcervo::class);

        return view('admin.fotografias.create', [
            'tipoDataOptions' => TipoData::cases(),
            'estadoConservacaoOptions' => ItemAcervo::ESTADOS_CONSERVACAO,
            'statusOptions' => ItemAcervo::STATUS,
            'visibilidadeOptions' => Visibilidade::cases(),
        ]);
    }

    public function store(StoreFotografiaRequest $request): RedirectResponse
    {
        ItemAcervo::create($request->payload());

        return redirect()
            ->route('admin.fotografias.index')
            ->with('success', 'Fotografia cadastrada com sucesso.');
    }

    public function show(ItemAcervo $fotografia): View
    {
        $this->ensurePhotograph($fotografia);
        Gate::authorize('view', $fotografia);

        $fotografia->load([
            'arquivos',
            'assuntos',
            'autor',
            'categorias',
            'colecoes',
            'conjuntosContextuais',
            'criadoPor',
            'atualizadoPor',
            'palavrasChave',
            'pessoas',
        ]);

        return view('admin.fotografias.show', [
            'fotografia' => $fotografia,
        ]);
    }

    public function edit(ItemAcervo $fotografia): View
    {
        $this->ensurePhotograph($fotografia);
        Gate::authorize('update', $fotografia);

        return view('admin.fotografias.edit', [
            'fotografia' => $fotografia,
            'tipoDataOptions' => TipoData::cases(),
            'estadoConservacaoOptions' => ItemAcervo::ESTADOS_CONSERVACAO,
            'statusOptions' => ItemAcervo::STATUS,
            'visibilidadeOptions' => Visibilidade::cases(),
        ]);
    }

    public function update(UpdateFotografiaRequest $request, ItemAcervo $fotografia): RedirectResponse
    {
        $this->ensurePhotograph($fotografia);

        $fotografia->update($request->payload());

        return redirect()
            ->route('admin.fotografias.show', $fotografia)
            ->with('success', 'Fotografia atualizada com sucesso.');
    }

    public function destroy(ItemAcervo $fotografia): RedirectResponse
    {
        $this->ensurePhotograph($fotografia);
        Gate::authorize('delete', $fotografia);

        $fotografia->delete();

        return redirect()
            ->route('admin.fotografias.index')
            ->with('success', 'Fotografia excluída com sucesso.');
    }

    private function ensurePhotograph(ItemAcervo $fotografia): void
    {
        abort_unless($fotografia->tipo_item === 'fotografia', Response::HTTP_NOT_FOUND);
    }
}
