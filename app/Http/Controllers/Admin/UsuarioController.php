<?php

namespace App\Http\Controllers\Admin;

use App\Auditing\AuditContextFactory;
use App\Http\Controllers\Admin\Concerns\ListagemPesquisavel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUsuarioRequest;
use App\Http\Requests\Admin\UpdateUsuarioRequest;
use App\Models\User;
use App\Services\Usuarios\AtualizarUsuario;
use App\Services\Usuarios\CriarUsuario;
use App\Support\OrdenacaoListagem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    use ListagemPesquisavel;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        return $this->listagemPesquisavel(
            $request,
            User::query(),
            ['nome', 'email'],
            OrdenacaoListagem::fromRequest($request, [
                'nome' => 'nome',
                'email' => 'email',
                'perfil' => OrdenacaoListagem::porValores('role', ['admin', 'operador']),
                'situacao' => OrdenacaoListagem::porValores('status', ['ativo', 'inativo']),
            ], 'nome'),
            'admin.usuarios.index',
            'usuarios',
        );
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('admin.usuarios.create');
    }

    public function store(
        StoreUsuarioRequest $request,
        CriarUsuario $service,
        AuditContextFactory $contexts,
    ): RedirectResponse {
        $service->execute(
            actor: $request->user(),
            data: $request->validated(),
            context: $contexts->fromRequest($request),
        );

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', 'Usuário cadastrado com sucesso.');
    }

    public function edit(User $usuario): View
    {
        Gate::authorize('update', $usuario);

        return view('admin.usuarios.edit', [
            'usuario' => $usuario,
        ]);
    }

    public function update(
        UpdateUsuarioRequest $request,
        User $usuario,
        AtualizarUsuario $service,
        AuditContextFactory $contexts,
    ): RedirectResponse {
        $service->execute(
            actor: $request->user(),
            usuario: $usuario,
            data: $request->validated(),
            context: $contexts->fromRequest($request),
        );

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', 'Usuário atualizado com sucesso.');
    }
}
