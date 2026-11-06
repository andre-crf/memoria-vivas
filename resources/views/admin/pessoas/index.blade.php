<x-layouts.admin title="Pessoas | Memórias Vivas">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-admin.page-header
            class="mb-6"
            eyebrow="Catalogação"
            title="Pessoas identificadas"
            description="Pessoas identificadas nos itens do acervo e suas observações complementares."
        >
            <x-slot:actions>
                <x-catalog.button icon="plus" :href="route('admin.pessoas.create')">Nova pessoa</x-catalog.button>
            </x-slot:actions>
        </x-admin.page-header>

        <x-admin.busca-listagem
            :action="route('admin.pessoas.index')"
            :termo="$termo"
            placeholder="Nome ou observação"
            :total="$pessoas->total()"
            singular="pessoa encontrada"
            plural="pessoas encontradas"
            :search-error="$searchError"
            :ordenacao="$ordenacao"
        />

        <x-admin.table table-class="min-w-[60rem]" :paginator="$pessoas" :vazia="$pessoas->isEmpty()">
            <x-slot:vazio>
                @if ($termo !== '')
                    <x-admin.busca-sem-resultados :action="route('admin.pessoas.index')" mensagem="Nenhuma pessoa encontrada para a pesquisa" />
                @else
                    <x-ui.empty-state icon="users" title="Nenhuma pessoa cadastrada" description="Quando houver pessoas identificadas, elas aparecerão nesta listagem." />
                @endif
            </x-slot:vazio>

            <thead>
                <tr>
                    <x-admin.coluna-ordenavel rotulo="Nome" chave="nome" :ordenacao="$ordenacao" :action="route('admin.pessoas.index')" :parametros="$parametros" />
                    <th scope="col">Observação</th>
                    <x-admin.coluna-ordenavel rotulo="Itens associados" chave="itens" :ordenacao="$ordenacao" :action="route('admin.pessoas.index')" :parametros="$parametros" />
                    <th scope="col" class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pessoas as $pessoa)
                    <tr id="pessoa-{{ $pessoa->id }}" class="relative">
                        <td class="min-w-56 max-w-sm">
                            <p class="truncate font-semibold text-fg"><x-admin.link-linha :href="route('admin.pessoas.edit', $pessoa)">{{ $pessoa->nome }}</x-admin.link-linha></p>
                            <p class="mt-1 text-xs text-fg-subtle">#{{ $pessoa->id }}</p>
                        </td>
                        <td class="min-w-64 max-w-lg text-ink-700">{{ $pessoa->observacao ?: 'Sem observação' }}</td>
                        <td class="whitespace-nowrap"><x-ui.badge tone="heritage">{{ $pessoa->itens_acervo_count }}</x-ui.badge></td>
                        <td>
                            <div class="relative z-10 flex justify-end gap-2">
                                <x-catalog.button variant="secondary" size="sm" :href="route('admin.pessoas.edit', $pessoa)">Editar</x-catalog.button>
                                <form
                                    method="POST"
                                    action="{{ route('admin.pessoas.destroy', $pessoa) }}"
                                    data-confirm-title="Confirmar exclusão"
                                    data-confirm-message="{{ $pessoa->itens_acervo_count > 0
                                        ? sprintf(
                                            'Esta pessoa está associada a %d %s do acervo. Ao excluí-la, essas associações serão removidas, mas os itens serão mantidos. Deseja continuar?',
                                            $pessoa->itens_acervo_count,
                                            $pessoa->itens_acervo_count === 1 ? 'item' : 'itens',
                                        )
                                        : 'Esta pessoa não possui itens associados. Deseja realmente excluí-la?' }}"
                                    data-confirm-label="Excluir pessoa"
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
