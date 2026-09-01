<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TipoData;
use App\Enums\Visibilidade;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFotografiaRequest;
use App\Models\ItemAcervo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
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
}
