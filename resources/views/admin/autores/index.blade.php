<x-layouts.admin title="Autores | Memórias Vivas">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-admin.page-header
            class="mb-6"
            eyebrow="Catalogação"
            title="Autores"
            description="Gerencie os autores associados às fotografias do acervo."
        >
            <x-slot:actions>
                <x-catalog.button icon="plus" :href="route('admin.autores.create')">Novo autor</x-catalog.button>
            </x-slot:actions>
        </x-admin.page-header>

        <x-admin.busca-listagem
            :action="route('admin.autores.index')"
            :termo="$termo"
            placeholder="Nome ou observação"
            :total="$autores->total()"
            singular="autor encontrado"
            plural="autores encontrados"
            :search-error="$searchError"
            :ordenacao="$ordenacao"
        />

        <x-admin.table :paginator="$autores" :vazia="$autores->isEmpty()">
            <x-slot:vazio>
                @if ($termo !== '')
                    <x-admin.busca-sem-resultados :action="route('admin.autores.index')" mensagem="Nenhum autor encontrado para a pesquisa" />
                @else
                    <x-ui.empty-state icon="users" title="Nenhum autor cadastrado" description="Quando houver autores cadastrados, eles aparecerão nesta listagem." />
                @endif
            </x-slot:vazio>

            <thead>
                <tr>
                    <x-admin.coluna-ordenavel rotulo="Nome" chave="nome" :ordenacao="$ordenacao" :action="route('admin.autores.index')" :parametros="$parametros" />
                    <x-admin.coluna-ordenavel rotulo="Tipo" chave="tipo" :ordenacao="$ordenacao" :action="route('admin.autores.index')" :parametros="$parametros" />
                    <x-admin.coluna-ordenavel rotulo="Itens associados" chave="itens" :ordenacao="$ordenacao" :action="route('admin.autores.index')" :parametros="$parametros" />
                    <th scope="col" class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($autores as $autor)
                    <tr id="autor-{{ $autor->id }}" class="relative">
                        <td class="max-w-sm">
                            <p class="truncate font-semibold text-fg"><x-admin.link-linha :href="route('admin.autores.edit', $autor)">{{ $autor->nome }}</x-admin.link-linha></p>
                            <p class="mt-1 text-xs text-fg-subtle">#{{ $autor->id }}</p>
                        </td>
                        <td class="whitespace-nowrap text-ink-700">{{ $autor->tipo === 'pessoa' ? 'Pessoa' : 'Instituição' }}</td>
                        <td class="whitespace-nowrap"><x-ui.badge tone="heritage">{{ $autor->itens_acervo_count }}</x-ui.badge></td>
                        <td>
                            <div class="relative z-10 flex justify-end gap-2">
                                <x-catalog.button variant="secondary" size="sm" :href="route('admin.autores.edit', $autor)">Editar</x-catalog.button>
                                <form
                                    method="POST"
                                    action="{{ route('admin.autores.destroy', $autor) }}"
                                    data-confirm-title="Confirmar exclusão"
                                    data-confirm-message="{{ $autor->itens_acervo_count > 0
                                        ? "Este autor está associado a {$autor->itens_acervo_count} itens do acervo. Ao excluir o autor, essas associações serão removidas, mas os itens serão mantidos. Deseja continuar?"
                                        : "Este autor não possui itens associados. Deseja realmente excluí-lo?" }}"
                                    data-confirm-label="Excluir autor"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <x-catalog.button type="submit" variant="danger" size="sm">Excluir</x-catalog.button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
    </div>
</x-layouts.admin>
