<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePerfilPasswordRequest;
use App\Http\Requests\Admin\UpdatePerfilRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PerfilController extends Controller
{
    public function edit(Request $request): View
    {
        $usuario = $request->user();

        Gate::authorize('updateIdentity', $usuario);

        return view('admin.perfil.edit', [
            'usuario' => $usuario,
        ]);
    }

    public function update(UpdatePerfilRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return redirect()
            ->route('admin.perfil.edit')
            ->with('profile_success', 'Dados pessoais atualizados com sucesso.');
    }

    public function updatePassword(UpdatePerfilPasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->validated('password'),
        ]);

        return redirect()
            ->route('admin.perfil.edit')
            ->with('password_success', 'Senha atualizada com sucesso.');
    }
}
