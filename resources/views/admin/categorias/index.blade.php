<x-layouts.admin title="Categorias | Memórias Vivas">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-admin.page-header
            class="mb-6"
            eyebrow="Catalogação"
            title="Categorias"
            description="Categorias utilizadas para organizar e classificar os itens do acervo."
        >
            <x-slot:actions>
                <x-catalog.button icon="plus" :href="route('admin.categorias.create')">Nova categoria</x-catalog.button>
            </x-slot:actions>
        </x-admin.page-header>

        <x-admin.busca-listagem
            :action="route('admin.categorias.index')"
            :termo="$termo"
            placeholder="Título ou descrição"
            :total="$categorias->total()"
            singular="categoria encontrada"
            plural="categorias encontradas"
            :search-error="$searchError"
            :ordenacao="$ordenacao"
        />

        <x-admin.table table-class="min-w-[60rem]" :paginator="$categorias" :vazia="$categorias->isEmpty()">
            <x-slot:vazio>
                @if ($termo !== '')
                    <x-admin.busca-sem-resultados :action="route('admin.categorias.index')" mensagem="Nenhuma categoria encontrada para a pesquisa" />
                @else
                    <x-ui.empty-state icon="folder-open" title="Nenhuma categoria cadastrada" description="Quando houver categorias cadastradas, elas aparecerão nesta listagem." />
                @endif
            </x-slot:vazio>

            <thead>
                <tr>
                    <x-admin.coluna-ordenavel rotulo="Título" chave="titulo" :ordenacao="$ordenacao" :action="route('admin.categorias.index')" :parametros="$parametros" />
                    <th scope="col">Descrição</th>
                    <x-admin.coluna-ordenavel rotulo="Itens associados" chave="itens" :ordenacao="$ordenacao" :action="route('admin.categorias.index')" :parametros="$parametros" />
                    <th scope="col" class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($categorias as $categoria)
                    <tr id="categoria-{{ $categoria->id }}" class="relative">
                        <td class="min-w-56 max-w-sm">
                            <p class="truncate font-semibold text-fg"><x-admin.link-linha :href="route('admin.categorias.edit', $categoria)">{{ $categoria->titulo }}</x-admin.link-linha></p>
                            <p class="mt-1 text-xs text-fg-subtle">#{{ $categoria->id }}</p>
                        </td>
                        <td class="min-w-64 max-w-lg text-ink-700">{{ $categoria->descricao ?: 'Sem descrição' }}</td>
                        <td class="whitespace-nowrap"><x-ui.badge tone="heritage">{{ $categoria->itens_acervo_count }}</x-ui.badge></td>
                        <td>
                            <div class="relative z-10 flex justify-end gap-2">
                                <x-catalog.button variant="secondary" size="sm" :href="route('admin.categorias.edit', $categoria)">Editar</x-catalog.button>
                                <form
                                    method="POST"
                                    action="{{ route('admin.categorias.destroy', $categoria) }}"
                                    data-confirm-title="Confirmar exclusão"
                                    data-confirm-message="Tem certeza que deseja excluir esta categoria? As associações com os itens do acervo serão removidas."
                                    data-confirm-label="Excluir categoria"
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
