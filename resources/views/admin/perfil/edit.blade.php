<x-layouts.admin title="Meu perfil | Memórias Vivas">
    <div class="mx-auto max-w-5xl px-6 py-8">
        <section class="mb-6">
            <p class="text-sm font-medium text-[#6B5E2E]">Minha conta</p>
            <h2 class="mt-1 text-2xl font-semibold text-stone-950">Meu perfil</h2>
            <p class="mt-2 text-sm leading-6 text-stone-600">Consulte seus dados de acesso e mantenha suas informações pessoais atualizadas.</p>
        </section>

        <div class="grid gap-6 lg:grid-cols-2 lg:items-start">
            <section class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
                <div class="border-b border-stone-200 pb-5">
                    <h3 class="text-lg font-semibold text-stone-950">Dados pessoais</h3>
                    <p class="mt-1 text-sm leading-6 text-stone-600">Atualize o nome e o e-mail associados à sua conta.</p>
                </div>

                @if (session('profile_success'))
                    <div class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800" role="status">
                        {{ session('profile_success') }}
                    </div>
                @endif

                @if ($errors->profileUpdate->any())
                    <div class="mt-5 rounded-md border border-red-200 bg-red-50 p-4" role="alert">
                        <p class="text-sm font-medium text-red-800">Os dados pessoais não foram atualizados. Verifique os campos informados.</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.perfil.update') }}" class="mt-6 space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="nome" class="block text-sm font-medium text-stone-900">Nome</label>
                        <input id="nome" name="nome" type="text" value="{{ old('nome', $usuario->nome) }}" required maxlength="255" autocomplete="name" autofocus class="mt-2 block w-full rounded-md border border-stone-300 px-3 py-2 text-sm text-stone-900 shadow-sm outline-none transition focus:border-[#173F35] focus:ring-2 focus:ring-[#173F35]">
                        @error('nome', 'profileUpdate') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-stone-900">E-mail</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $usuario->email) }}" required maxlength="255" autocomplete="email" class="mt-2 block w-full rounded-md border border-stone-300 px-3 py-2 text-sm text-stone-900 shadow-sm outline-none transition focus:border-[#173F35] focus:ring-2 focus:ring-[#173F35]">
                        @error('email', 'profileUpdate') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>

                    <dl class="grid gap-4 rounded-md border border-stone-200 bg-stone-50 p-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-500">Perfil</dt>
                            <dd class="mt-1 text-sm font-medium text-stone-900">{{ $usuario->isAdmin() ? 'Administrador' : 'Operador' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-500">Situação</dt>
                            <dd class="mt-1 text-sm font-medium text-stone-900">{{ $usuario->ativo() ? 'Ativo' : 'Inativo' }}</dd>
                        </div>
                    </dl>

                    <div class="flex justify-end border-t border-stone-200 pt-6">
                        <button type="submit" class="rounded-md bg-[#173F35] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0f2b24] focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2">Salvar dados pessoais</button>
                    </div>
                </form>
            </section>

            <section class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
                <div class="border-b border-stone-200 pb-5">
                    <h3 class="text-lg font-semibold text-stone-950">Segurança</h3>
                    <p class="mt-1 text-sm leading-6 text-stone-600">Confirme sua senha atual antes de definir uma nova senha.</p>
                </div>

                @if (session('password_success'))
                    <div class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800" role="status">
                        {{ session('password_success') }}
                    </div>
                @endif

                @if ($errors->passwordUpdate->any())
                    <div class="mt-5 rounded-md border border-red-200 bg-red-50 p-4" role="alert">
                        <p class="text-sm font-medium text-red-800">A senha não foi atualizada. Verifique os campos informados.</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.perfil.password.update') }}" class="mt-6 space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="current_password" class="block text-sm font-medium text-stone-900">Senha atual</label>
                        <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="mt-2 block w-full rounded-md border border-stone-300 px-3 py-2 text-sm text-stone-900 shadow-sm outline-none transition focus:border-[#173F35] focus:ring-2 focus:ring-[#173F35]">
                        @error('current_password', 'passwordUpdate') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-stone-900">Nova senha</label>
                        <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password" class="mt-2 block w-full rounded-md border border-stone-300 px-3 py-2 text-sm text-stone-900 shadow-sm outline-none transition focus:border-[#173F35] focus:ring-2 focus:ring-[#173F35]">
                        <p class="mt-2 text-xs text-stone-500">Use pelo menos 8 caracteres.</p>
                        @error('password', 'passwordUpdate') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-stone-900">Confirmar nova senha</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password" class="mt-2 block w-full rounded-md border border-stone-300 px-3 py-2 text-sm text-stone-900 shadow-sm outline-none transition focus:border-[#173F35] focus:ring-2 focus:ring-[#173F35]">
                    </div>

                    <div class="flex justify-end border-t border-stone-200 pt-6">
                        <button type="submit" class="rounded-md bg-[#173F35] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0f2b24] focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2">Atualizar senha</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-layouts.admin>
