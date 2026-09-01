<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ItemAcervo;
use Illuminate\Contracts\View\View;
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
}
