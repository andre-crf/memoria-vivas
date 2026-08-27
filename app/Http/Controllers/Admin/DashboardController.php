<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Arquivo;
use App\Models\ItemAcervo;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'totalItens' => ItemAcervo::count(),
            'itensPublicados' => ItemAcervo::where('status', 'publicado')->count(),
            'totalArquivos' => Arquivo::count(),
            'usuariosAtivos' => User::where('status', 'ativo')->count(),
        ]);
    }
}
