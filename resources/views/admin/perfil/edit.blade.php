<x-layouts.admin title="Meu perfil | Memórias Vivas">
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
        <x-admin.page-header
            class="mb-6"
            eyebrow="Minha conta"
            title="Meu perfil"
            description="Consulte seus dados de acesso e mantenha suas informações pessoais atualizadas."
        />

        @php
            // Erros em chaves sem campo nesta tela (ex.: tentativa de enviar perfil ou situação) só aparecem no resumo.
            $errosDadosSemCampo = collect($errors->profileUpdate->getMessages())->except(['nome', 'email'])->flatten();
            $errosSenhaSemCampo = collect($errors->passwordUpdate->getMessages())->except(['current_password', 'password'])->flatten();
        @endphp

        <div class="grid gap-6 lg:grid-cols-2 lg:items-start">
            <x-catalog.section title="Dados pessoais" description="Atualize o nome e o e-mail associados à sua conta.">
                @if ($errors->profileUpdate->any())
                    <x-ui.alert tone="danger" title="Os dados pessoais não foram atualizados." class="mb-5">
                        <p>Verifique os campos informados.</p>
                        @if ($errosDadosSemCampo->isNotEmpty())
                            <ul>
                                @foreach ($errosDadosSemCampo as $erro)
                                    <li>{{ $erro }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </x-ui.alert>
                @endif

                <form method="POST" action="{{ route('admin.perfil.update') }}" class="grid gap-5">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-1.5">
                        <x-catalog.label for="nome" text="Nome" required>
                            <x-catalog.control id="nome" name="nome" type="text" :value="old('nome', $usuario->nome)" required maxlength="255" autocomplete="name" autofocus error="nome" error-bag="profileUpdate" />
                        </x-catalog.label>
                        <x-catalog.error field="nome" bag="profileUpdate" as="p" />
                    </div>

                    <div class="grid gap-1.5">
                        <x-catalog.label for="email" text="E-mail" required>
                            <x-catalog.control id="email" name="email" type="email" :value="old('email', $usuario->email)" required maxlength="255" autocomplete="email" error="email" error-bag="profileUpdate" />
                        </x-catalog.label>
                        <x-catalog.error field="email" bag="profileUpdate" as="p" />
                    </div>

                    <dl class="grid gap-3 sm:grid-cols-2">
                        <x-admin.info-block label="Perfil">{{ $usuario->isAdmin() ? 'Administrador' : 'Operador' }}</x-admin.info-block>
                        <x-admin.info-block label="Situação">{{ $usuario->ativo() ? 'Ativo' : 'Inativo' }}</x-admin.info-block>
                    </dl>

                    <div class="flex flex-col-reverse gap-3 border-t border-line pt-5 sm:flex-row sm:justify-end">
                        <x-catalog.button type="submit">Salvar dados pessoais</x-catalog.button>
                    </div>
                </form>
            </x-catalog.section>

            <x-catalog.section title="Segurança" description="Confirme sua senha atual antes de definir uma nova senha.">
                @if ($errors->passwordUpdate->any())
                    <x-ui.alert tone="danger" title="A senha não foi atualizada." class="mb-5">
                        <p>Verifique os campos informados.</p>
                        @if ($errosSenhaSemCampo->isNotEmpty())
                            <ul>
                                @foreach ($errosSenhaSemCampo as $erro)
                                    <li>{{ $erro }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </x-ui.alert>
                @endif

                <form method="POST" action="{{ route('admin.perfil.password.update') }}" class="grid gap-5">
                    @csrf
                    @method('PUT')

                    <x-catalog.password id="current_password" label="Senha atual" autocomplete="current-password" required error-bag="passwordUpdate" />

                    <x-catalog.password id="password" label="Nova senha" autocomplete="new-password" required requisitos minlength="8" error-bag="passwordUpdate" />

                    <x-catalog.password id="password_confirmation" label="Confirmar nova senha" autocomplete="new-password" required minlength="8" error-bag="passwordUpdate" />

                    <div class="flex flex-col-reverse gap-3 border-t border-line pt-5 sm:flex-row sm:justify-end">
                        <x-catalog.button type="submit">Atualizar senha</x-catalog.button>
                    </div>
                </form>
            </x-catalog.section>
        </div>
    </div>
</x-layouts.admin>
