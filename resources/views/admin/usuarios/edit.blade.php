<x-layouts.admin title="Editar usuário | Memórias Vivas">
    <div class="mx-auto max-w-3xl px-6 py-8">
        <section class="mb-6">
            <p class="text-sm font-medium text-[#6B5E2E]">Administração</p>
            <h2 class="mt-1 text-2xl font-semibold text-stone-950">Editar usuário</h2>
            <p class="mt-2 text-sm leading-6 text-stone-600">Atualize os dados da conta. Senhas não são alteradas nesta tela.</p>
        </section>

        <section class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
            @if ($errors->any())
                <div class="mb-6 rounded-md border border-red-200 bg-red-50 p-4">
                    <p class="text-sm font-medium text-red-800">A operação não foi realizada. Verifique os dados informados.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.usuarios.update', $usuario) }}" class="space-y-6">
                @csrf
                @method('PUT')
                <div>
                    <label for="nome" class="block text-sm font-medium text-stone-900">Nome</label>
                    <input id="nome" name="nome" type="text" value="{{ old('nome', $usuario->nome) }}" required maxlength="255" autofocus class="mt-2 block w-full rounded-md border border-stone-300 px-3 py-2 text-sm text-stone-900 shadow-sm outline-none transition focus:border-[#173F35] focus:ring-2 focus:ring-[#173F35]">
                    @error('nome') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-stone-900">E-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $usuario->email) }}" required maxlength="255" class="mt-2 block w-full rounded-md border border-stone-300 px-3 py-2 text-sm text-stone-900 shadow-sm outline-none transition focus:border-[#173F35] focus:ring-2 focus:ring-[#173F35]">
                    @error('email') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                @if (auth()->user()->is($usuario))
                    <div>
                        <p class="block text-sm font-medium text-stone-900">Perfil</p>
                        <p class="mt-2 rounded-md border border-stone-200 bg-stone-100 px-3 py-2 text-sm text-stone-700">{{ $usuario->isAdmin() ? 'Administrador' : 'Operador' }}</p>
                    </div>
                    <div>
                        <p class="block text-sm font-medium text-stone-900">Situação</p>
                        <p class="mt-2 rounded-md border border-stone-200 bg-stone-100 px-3 py-2 text-sm text-stone-700">{{ $usuario->ativo() ? 'Ativo' : 'Inativo' }}</p>
                    </div>
                    <p class="text-sm text-stone-600">Você não pode alterar seu próprio perfil ou situação nesta área.</p>
                @else
                    <div>
                        <label for="role" class="block text-sm font-medium text-stone-900">Perfil</label>
                        <select id="role" name="role" required class="mt-2 block w-full rounded-md border border-stone-300 px-3 py-2 text-sm text-stone-900 shadow-sm outline-none transition focus:border-[#173F35] focus:ring-2 focus:ring-[#173F35]">
                            <option value="operador" @selected(old('role', $usuario->role) === 'operador')>Operador</option>
                            <option value="admin" @selected(old('role', $usuario->role) === 'admin')>Administrador</option>
                        </select>
                        @error('role') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-stone-900">Situação</label>
                        <select id="status" name="status" required class="mt-2 block w-full rounded-md border border-stone-300 px-3 py-2 text-sm text-stone-900 shadow-sm outline-none transition focus:border-[#173F35] focus:ring-2 focus:ring-[#173F35]">
                            <option value="ativo" @selected(old('status', $usuario->status) === 'ativo')>Ativo</option>
                            <option value="inativo" @selected(old('status', $usuario->status) === 'inativo')>Inativo</option>
                        </select>
                        @error('status') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="flex items-center justify-end gap-3 border-t border-stone-200 pt-6">
                    <a href="{{ route('admin.usuarios.index') }}" class="rounded-md border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2">Cancelar</a>
                    <button type="submit" class="rounded-md bg-[#173F35] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0f2b24] focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2">Salvar alterações</button>
                </div>
            </form>
        </section>
    </div>
</x-layouts.admin>
