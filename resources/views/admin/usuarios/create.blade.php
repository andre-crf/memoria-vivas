<x-layouts.admin title="Novo usuário | Memórias Vivas">
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        <x-admin.page-header
            class="mb-6"
            eyebrow="Administração"
            title="Novo usuário"
            description="Cadastre uma conta ativa com senha inicial e perfil administrativo."
        />

        <x-catalog.section>
            @if ($errors->any())
                <x-ui.alert tone="danger" title="Verifique os dados informados." class="mb-5">
                    <p>Corrija os campos indicados abaixo.</p>
                </x-ui.alert>
            @endif

            <form method="POST" action="{{ route('admin.usuarios.store') }}" class="grid gap-5">
                @csrf

                <div class="grid gap-1.5">
                    <x-catalog.label for="nome" text="Nome" required>
                        <x-catalog.control id="nome" name="nome" type="text" :value="old('nome')" required maxlength="255" autofocus error="nome" />
                    </x-catalog.label>
                    <x-catalog.error field="nome" as="p" />
                </div>

                <div class="grid gap-1.5">
                    <x-catalog.label for="email" text="E-mail" required>
                        <x-catalog.control id="email" name="email" type="email" :value="old('email')" required maxlength="255" error="email" />
                    </x-catalog.label>
                    <x-catalog.error field="email" as="p" />
                </div>

                <div class="grid gap-1.5">
                    <x-catalog.label for="role" text="Perfil" required>
                        <x-catalog.control as="select" id="role" name="role" required error="role">
                            <option value="operador" @selected(old('role', 'operador') === 'operador')>Operador</option>
                            <option value="admin" @selected(old('role') === 'admin')>Administrador</option>
                        </x-catalog.control>
                    </x-catalog.label>
                    <x-catalog.error field="role" as="p" />
                </div>

                <x-catalog.password id="password" label="Senha inicial" autocomplete="new-password" required requisitos minlength="8" />

                <x-catalog.password id="password_confirmation" label="Confirmar senha inicial" autocomplete="new-password" required minlength="8" />

                <div class="flex flex-col-reverse gap-3 border-t border-line pt-5 sm:flex-row sm:justify-end">
                    <x-catalog.button variant="secondary" :href="route('admin.usuarios.index')">Cancelar</x-catalog.button>
                    <x-catalog.button type="submit">Cadastrar usuário</x-catalog.button>
                </div>
            </form>
        </x-catalog.section>
    </div>
</x-layouts.admin>
