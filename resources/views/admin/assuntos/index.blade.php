<x-layouts.admin title="Assuntos | Memórias Vivas">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-admin.page-header
            class="mb-6"
            eyebrow="Catalogação"
            title="Assuntos"
            description="Assuntos utilizados na catalogação dos itens do acervo."
        >
            <x-slot:actions>
                <x-catalog.button icon="plus" :href="route('admin.assuntos.create')">Novo assunto</x-catalog.button>
            </x-slot:actions>
        </x-admin.page-header>

        <x-admin.busca-listagem
            :action="route('admin.assuntos.index')"
            :termo="$termo"
            placeholder="Título ou descrição"
            :total="$assuntos->total()"
            singular="assunto encontrado"
            plural="assuntos encontrados"
            :search-error="$searchError"
            :ordenacao="$ordenacao"
        />

        <x-admin.table :paginator="$assuntos" :vazia="$assuntos->isEmpty()">
            <x-slot:vazio>
                @if ($termo !== '')
                    <x-admin.busca-sem-resultados :action="route('admin.assuntos.index')" mensagem="Nenhum assunto encontrado para a pesquisa" />
                @else
                    <x-ui.empty-state icon="layers" title="Nenhum assunto cadastrado" description="Quando houver assuntos cadastrados, eles aparecerão nesta listagem." />
                @endif
            </x-slot:vazio>

            <thead>
                <tr>
                    <x-admin.coluna-ordenavel rotulo="Título" chave="titulo" :ordenacao="$ordenacao" :action="route('admin.assuntos.index')" :parametros="$parametros" />
                    <th scope="col">Descrição</th>
                    <x-admin.coluna-ordenavel rotulo="Itens associados" chave="itens" :ordenacao="$ordenacao" :action="route('admin.assuntos.index')" :parametros="$parametros" />
                    <th scope="col" class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($assuntos as $assunto)
                    <tr id="assunto-{{ $assunto->id }}" class="relative">
                        <td class="max-w-sm">
                            <p class="truncate font-semibold text-fg"><x-admin.link-linha :href="route('admin.assuntos.edit', $assunto)">{{ $assunto->titulo }}</x-admin.link-linha></p>
                            <p class="mt-1 text-xs text-fg-subtle">#{{ $assunto->id }}</p>
                        </td>
                        <td class="max-w-lg text-ink-700">{{ $assunto->descricao ?: 'Sem descrição' }}</td>
                        <td class="whitespace-nowrap"><x-ui.badge tone="heritage">{{ $assunto->itens_acervo_count }}</x-ui.badge></td>
                        <td>
                            <div class="relative z-10 flex justify-end gap-2">
                                <x-catalog.button variant="secondary" size="sm" :href="route('admin.assuntos.edit', $assunto)">Editar</x-catalog.button>
                                <form
                                    method="POST"
                                    action="{{ route('admin.assuntos.destroy', $assunto) }}"
                                    data-confirm-title="Confirmar exclusão"
                                    data-confirm-message="Tem certeza que deseja excluir este assunto? As associações com os itens do acervo serão removidas."
                                    data-confirm-label="Excluir assunto"
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
