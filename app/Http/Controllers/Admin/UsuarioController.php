<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUsuarioRequest;
use App\Http\Requests\Admin\UpdateUsuarioRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('admin.usuarios.index', [
            'usuarios' => User::query()->orderBy('nome')->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('admin.usuarios.create');
    }

    public function store(StoreUsuarioRequest $request): RedirectResponse
    {
        User::create([
            ...$request->validated(),
            'status' => 'ativo',
        ]);

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

    public function update(UpdateUsuarioRequest $request, User $usuario): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $usuario, $data): void {
            // Serialize changes to active administrators before rechecking the policy.
            User::query()
                ->where('role', 'admin')
                ->where('status', 'ativo')
                ->lockForUpdate()
                ->get();

            $usuario = User::query()->lockForUpdate()->findOrFail($usuario->getKey());
            Gate::authorize('update', $usuario);

            $role = $data['role'] ?? $usuario->role;
            $status = $data['status'] ?? $usuario->status;

            if ($role !== $usuario->role && ! Gate::allows('updateRole', [$usuario, $role])) {
                throw ValidationException::withMessages([
                    'role' => $request->user()->is($usuario)
                        ? 'Você não pode alterar seu próprio perfil.'
                        : 'O último administrador ativo não pode perder o perfil de administrador.',
                ]);
            }

            if ($status !== $usuario->status && ! Gate::allows('updateStatus', [$usuario, $status])) {
                throw ValidationException::withMessages([
                    'status' => $request->user()->is($usuario)
                        ? 'Você não pode alterar sua própria situação.'
                        : 'O último administrador ativo não pode ser inativado.',
                ]);
            }

            $usuario->update([
                'nome' => $data['nome'],
                'email' => $data['email'],
                'role' => $role,
                'status' => $status,
            ]);
        });

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', 'Usuário atualizado com sucesso.');
    }
}
