<x-layouts.admin title="Editar usuário | Memórias Vivas">
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        <x-admin.page-header
            class="mb-6"
            eyebrow="Administração"
            title="Editar usuário"
            description="Atualize os dados da conta. Senhas não são alteradas nesta tela."
        />

        @php
            $editandoPropriaConta = auth()->user()->is($usuario);
            // Na própria conta, perfil e situação não têm campo: um erro nessas chaves só aparece no resumo.
            $errosSemCampo = collect($errors->getMessages())
                ->except($editandoPropriaConta ? ['nome', 'email'] : ['nome', 'email', 'role', 'status'])
                ->flatten();
        @endphp

        <x-catalog.section>
            @if ($errors->any())
                <x-ui.alert tone="danger" title="A operação não foi realizada." class="mb-5">
                    <p>Verifique os dados informados nos campos indicados abaixo.</p>
                    @if ($errosSemCampo->isNotEmpty())
                        <ul>
                            @foreach ($errosSemCampo as $erro)
                                <li>{{ $erro }}</li>
                            @endforeach
                        </ul>
                    @endif
                </x-ui.alert>
            @endif

            <form method="POST" action="{{ route('admin.usuarios.update', $usuario) }}" class="grid gap-5">
                @csrf
                @method('PUT')

                <div class="grid gap-1.5">
                    <x-catalog.label for="nome" text="Nome" required>
                        <x-catalog.control id="nome" name="nome" type="text" :value="old('nome', $usuario->nome)" required maxlength="255" autofocus error="nome" />
                    </x-catalog.label>
                    <x-catalog.error field="nome" as="p" />
                </div>

                <div class="grid gap-1.5">
                    <x-catalog.label for="email" text="E-mail" required>
                        <x-catalog.control id="email" name="email" type="email" :value="old('email', $usuario->email)" required maxlength="255" error="email" />
                    </x-catalog.label>
                    <x-catalog.error field="email" as="p" />
                </div>

                @if ($editandoPropriaConta)
                    <div class="grid gap-3">
                        <dl class="grid gap-3 sm:grid-cols-2">
                            <x-admin.info-block label="Perfil">{{ $usuario->isAdmin() ? 'Administrador' : 'Operador' }}</x-admin.info-block>
                            <x-admin.info-block label="Situação">{{ $usuario->ativo() ? 'Ativo' : 'Inativo' }}</x-admin.info-block>
                        </dl>
                        <p class="text-sm text-fg-muted">Você não pode alterar seu próprio perfil ou situação nesta área.</p>
                    </div>
                @else
                    <div class="grid gap-1.5">
                        <x-catalog.label for="role" text="Perfil" required>
                            <x-catalog.control as="select" id="role" name="role" required error="role">
                                <option value="operador" @selected(old('role', $usuario->role) === 'operador')>Operador</option>
                                <option value="admin" @selected(old('role', $usuario->role) === 'admin')>Administrador</option>
                            </x-catalog.control>
                        </x-catalog.label>
                        <x-catalog.error field="role" as="p" />
                    </div>

                    <div class="grid gap-1.5">
                        <x-catalog.label for="status" text="Situação" required>
                            <x-catalog.control as="select" id="status" name="status" required error="status">
                                <option value="ativo" @selected(old('status', $usuario->status) === 'ativo')>Ativo</option>
                                <option value="inativo" @selected(old('status', $usuario->status) === 'inativo')>Inativo</option>
                            </x-catalog.control>
                        </x-catalog.label>
                        <x-catalog.error field="status" as="p" />
                    </div>
                @endif

                <div class="flex flex-col-reverse gap-3 border-t border-line pt-5 sm:flex-row sm:justify-end">
                    <x-catalog.button variant="secondary" :href="route('admin.usuarios.index')">Cancelar</x-catalog.button>
                    <x-catalog.button type="submit">Salvar alterações</x-catalog.button>
                </div>
            </form>
        </x-catalog.section>
    </div>
</x-layouts.admin>
