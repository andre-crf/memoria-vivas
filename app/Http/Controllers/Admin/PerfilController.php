<?php

namespace App\Http\Controllers\Admin;

use App\Auditing\AuditContextFactory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePerfilPasswordRequest;
use App\Http\Requests\Admin\UpdatePerfilRequest;
use App\Services\Usuarios\AlterarSenhaPerfil;
use App\Services\Usuarios\AtualizarPerfil;
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

    public function update(
        UpdatePerfilRequest $request,
        AtualizarPerfil $service,
        AuditContextFactory $contexts,
    ): RedirectResponse {
        $service->execute(
            usuario: $request->user(),
            data: $request->validated(),
            context: $contexts->fromRequest($request),
        );

        return redirect()
            ->route('admin.perfil.edit')
            ->with('profile_success', 'Dados pessoais atualizados com sucesso.');
    }

    public function updatePassword(
        UpdatePerfilPasswordRequest $request,
        AlterarSenhaPerfil $service,
        AuditContextFactory $contexts,
    ): RedirectResponse {
        $service->execute(
            usuario: $request->user(),
            password: $request->validated('password'),
            context: $contexts->fromRequest($request),
        );

        return redirect()
            ->route('admin.perfil.edit')
            ->with('password_success', 'Senha atualizada com sucesso.');
    }
}
