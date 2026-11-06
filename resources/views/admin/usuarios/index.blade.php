<x-layouts.admin title="Usuários | Memórias Vivas">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-admin.page-header
            class="mb-6"
            eyebrow="Administração"
            title="Usuários"
            description="Gerencie as contas com acesso à área administrativa."
        >
            <x-slot:actions>
                <x-catalog.button icon="plus" :href="route('admin.usuarios.create')">Novo usuário</x-catalog.button>
            </x-slot:actions>
        </x-admin.page-header>

        <x-admin.busca-listagem
            :action="route('admin.usuarios.index')"
            :termo="$termo"
            placeholder="Nome ou e-mail"
            :total="$usuarios->total()"
            singular="usuário encontrado"
            plural="usuários encontrados"
            :search-error="$searchError"
            :ordenacao="$ordenacao"
        />

        <x-admin.table table-class="min-w-[56rem]" :paginator="$usuarios" :vazia="$usuarios->isEmpty()">
            <x-slot:vazio>
                @if ($termo !== '')
                    <x-admin.busca-sem-resultados :action="route('admin.usuarios.index')" mensagem="Nenhum usuário encontrado para a pesquisa" />
                @else
                    <x-ui.empty-state icon="users" title="Nenhum usuário cadastrado" description="Quando houver usuários cadastrados, eles aparecerão nesta listagem." />
                @endif
            </x-slot:vazio>

            <thead>
                <tr>
                    <x-admin.coluna-ordenavel rotulo="Nome" chave="nome" :ordenacao="$ordenacao" :action="route('admin.usuarios.index')" :parametros="$parametros" />
                    <x-admin.coluna-ordenavel rotulo="E-mail" chave="email" :ordenacao="$ordenacao" :action="route('admin.usuarios.index')" :parametros="$parametros" />
                    <x-admin.coluna-ordenavel rotulo="Perfil" chave="perfil" :ordenacao="$ordenacao" :action="route('admin.usuarios.index')" :parametros="$parametros" />
                    <x-admin.coluna-ordenavel rotulo="Situação" chave="situacao" :ordenacao="$ordenacao" :action="route('admin.usuarios.index')" :parametros="$parametros" />
                    <th scope="col" class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($usuarios as $usuario)
                    <tr id="usuario-{{ $usuario->id }}" class="relative">
                        <td class="min-w-56 max-w-sm">
                            <p class="truncate font-semibold text-fg"><x-admin.link-linha :href="route('admin.usuarios.edit', $usuario)">{{ $usuario->nome }}</x-admin.link-linha></p>
                            <p class="mt-1 text-xs text-fg-subtle">#{{ $usuario->id }}</p>
                        </td>
                        <td class="text-ink-700">{{ $usuario->email }}</td>
                        <td class="whitespace-nowrap text-ink-700">{{ $usuario->isAdmin() ? 'Administrador' : 'Operador' }}</td>
                        <td class="whitespace-nowrap">
                            <x-ui.badge :tone="$usuario->ativo() ? 'success' : 'neutral'">{{ $usuario->ativo() ? 'Ativo' : 'Inativo' }}</x-ui.badge>
                        </td>
                        <td>
                            <div class="relative z-10 flex justify-end gap-2">
                                <x-catalog.button variant="secondary" size="sm" :href="route('admin.usuarios.edit', $usuario)">Editar</x-catalog.button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
    </div>
</x-layouts.admin>
