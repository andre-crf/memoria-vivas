<x-layouts.admin title="Palavras-chave | Memórias Vivas">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-admin.page-header
            class="mb-6"
            eyebrow="Catalogação"
            title="Palavras-chave"
            description="Termos específicos utilizados para indexação e recuperação dos itens do acervo."
        >
            <x-slot:actions>
                <x-catalog.button icon="plus" :href="route('admin.palavras-chave.create')">Nova palavra-chave</x-catalog.button>
            </x-slot:actions>
        </x-admin.page-header>

        <x-admin.busca-listagem
            :action="route('admin.palavras-chave.index')"
            :termo="$termo"
            placeholder="Termo"
            :total="$palavrasChave->total()"
            singular="palavra-chave encontrada"
            plural="palavras-chave encontradas"
            :search-error="$searchError"
            :ordenacao="$ordenacao"
        />

        <x-admin.table table-class="min-w-[42rem]" :paginator="$palavrasChave" :vazia="$palavrasChave->isEmpty()">
            <x-slot:vazio>
                @if ($termo !== '')
                    <x-admin.busca-sem-resultados :action="route('admin.palavras-chave.index')" mensagem="Nenhuma palavra-chave encontrada para a pesquisa" />
                @else
                    <x-ui.empty-state icon="tags" title="Nenhuma palavra-chave cadastrada" description="Quando houver palavras-chave cadastradas, elas aparecerão nesta listagem." />
                @endif
            </x-slot:vazio>

            <thead>
                <tr>
                    <x-admin.coluna-ordenavel rotulo="Termo" chave="termo" :ordenacao="$ordenacao" :action="route('admin.palavras-chave.index')" :parametros="$parametros" />
                    <x-admin.coluna-ordenavel rotulo="Itens associados" chave="itens" :ordenacao="$ordenacao" :action="route('admin.palavras-chave.index')" :parametros="$parametros" />
                    <th scope="col" class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($palavrasChave as $palavraChave)
                    <tr id="palavra-chave-{{ $palavraChave->id }}" class="relative">
                        <td class="min-w-56 max-w-lg">
                            <p class="truncate font-semibold text-fg"><x-admin.link-linha :href="route('admin.palavras-chave.edit', $palavraChave)">{{ $palavraChave->termo }}</x-admin.link-linha></p>
                            <p class="mt-1 text-xs text-fg-subtle">#{{ $palavraChave->id }}</p>
                        </td>
                        <td class="whitespace-nowrap"><x-ui.badge tone="heritage">{{ $palavraChave->itens_acervo_count }}</x-ui.badge></td>
                        <td>
                            <div class="relative z-10 flex justify-end gap-2">
                                <x-catalog.button variant="secondary" size="sm" :href="route('admin.palavras-chave.edit', $palavraChave)">Editar</x-catalog.button>
                                <form
                                    method="POST"
                                    action="{{ route('admin.palavras-chave.destroy', $palavraChave) }}"
                                    data-confirm-title="Confirmar exclusão"
                                    data-confirm-message="Tem certeza que deseja excluir esta palavra-chave? As associações com os itens do acervo serão removidas."
                                    data-confirm-label="Excluir palavra-chave"
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
