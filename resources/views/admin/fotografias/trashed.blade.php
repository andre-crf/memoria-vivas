<x-layouts.admin title="Lixeira de fotografias | Memórias Vivas">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-admin.page-header
            class="mb-6"
            eyebrow="Acervo"
            title="Lixeira de fotografias"
            description="Fotografias excluídas logicamente e disponíveis para restauração administrativa."
            :back-href="route('admin.fotografias.index')"
            back-label="Voltar para fotografias"
        />

        <x-ui.alert tone="warning" title="Atenção:" class="mb-6">
            <p>A exclusão permanente remove o registro e seus vínculos definitivamente. Essa operação é irreversível.</p>
        </x-ui.alert>

        <x-admin.filtros-fotografias
            :action="route('admin.fotografias.trashed')"
            :filtros="$filtros"
            :status-options="$statusOptions"
            :visibilidades="$visibilidades"
            :total="$fotografias->total()"
            :search-error="$searchError"
            :ordenacao="$ordenacao"
        />

        <x-admin.table table-class="min-w-[72rem]" :paginator="$fotografias" :vazia="$fotografias->isEmpty()">
            <x-slot:vazio>
                @if ($filtros->possuiCriterios())
                    <x-admin.busca-sem-resultados
                        :action="route('admin.fotografias.trashed')"
                        mensagem="Nenhuma fotografia encontrada para os filtros aplicados"
                        dica="Revise o texto pesquisado ou remova algum filtro."
                        limpar="Limpar filtros"
                    />
                @else
                    <x-ui.empty-state icon="trash-2" title="Nenhuma fotografia na lixeira" description="Quando uma fotografia for excluída logicamente, ela aparecerá aqui para possível restauração." />
                @endif
            </x-slot:vazio>

            <thead>
                <tr>
                    <th scope="col">Imagem</th>
                    <x-admin.coluna-ordenavel rotulo="Título" chave="titulo" :ordenacao="$ordenacao" :action="route('admin.fotografias.trashed')" :parametros="$filtros->parametros()" />
                    <x-admin.coluna-ordenavel rotulo="Data" chave="data" :ordenacao="$ordenacao" :action="route('admin.fotografias.trashed')" :parametros="$filtros->parametros()" />
                    <x-admin.coluna-ordenavel rotulo="Status" chave="status" :ordenacao="$ordenacao" :action="route('admin.fotografias.trashed')" :parametros="$filtros->parametros()" />
                    <x-admin.coluna-ordenavel rotulo="Visibilidade" chave="visibilidade" :ordenacao="$ordenacao" :action="route('admin.fotografias.trashed')" :parametros="$filtros->parametros()" />
                    <x-admin.coluna-ordenavel rotulo="Exclusão" chave="exclusao" :ordenacao="$ordenacao" :action="route('admin.fotografias.trashed')" :parametros="$filtros->parametros()" />
                    <th scope="col" class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($fotografias as $fotografia)
                    @php($imagem = $fotografia->imagemAdministrativa(['thumbnail', 'medium', 'large', 'original']))
                    {{-- Sem link na linha: fotografias excluídas não têm página de detalhes. --}}
                    <tr id="fotografia-excluida-{{ $fotografia->id }}">
                        <td>
                            @if ($imagem)
                                <img
                                    src="{{ route('admin.arquivos.show', $imagem) }}"
                                    alt="Miniatura de {{ $fotografia->titulo }}"
                                    width="64"
                                    height="64"
                                    loading="lazy"
                                    class="h-16 w-16 rounded-md bg-mist-100 object-cover"
                                >
                            @else
                                <div class="grid h-16 w-16 place-items-center rounded-md border border-dashed border-mist-300 bg-surface-muted px-1 text-center text-[10px] font-semibold tracking-[0.08em] text-ink-400 uppercase">
                                    Sem imagem disponível
                                </div>
                            @endif
                        </td>
                        <td class="min-w-64 max-w-md">
                            <p class="truncate font-semibold text-fg" title="{{ $fotografia->titulo }}">{{ $fotografia->titulo }}</p>
                            <p class="mt-1 text-xs text-fg-subtle">#{{ $fotografia->id }}</p>
                        </td>
                        <td class="whitespace-nowrap text-ink-700">{{ $fotografia->dataHistorica()->label() }}</td>
                        <td class="whitespace-nowrap"><x-admin.status-fotografia :fotografia="$fotografia" /></td>
                        <td class="whitespace-nowrap"><x-ui.badge :tone="$fotografia->visibilidade->isPublico() ? 'heritage' : 'neutral'">{{ $fotografia->visibilidade->label() }}</x-ui.badge></td>
                        <td class="whitespace-nowrap text-ink-700">
                            <p><x-data-hora :valor="$fotografia->deleted_at" formato="curto" vazio="Não registrada" /></p>
                            <p class="mt-1 text-xs text-fg-subtle">{{ $fotografia->excluidoPor?->nome ?: 'Usuário não registrado' }}</p>
                        </td>
                        <td>
                            <div class="flex justify-end gap-2">
                                @can('restore', $fotografia)
                                    <form
                                        method="POST"
                                        action="{{ route('admin.fotografias.restore', $fotografia->id) }}"
                                        data-confirm-title="Confirmar restauração"
                                        data-confirm-message="Restaurar esta fotografia?"
                                        data-confirm-label="Restaurar fotografia"
                                        data-confirm-variant="primary"
                                    >
                                        @csrf
                                        @method('PATCH')
                                        <x-catalog.button type="submit" variant="outline" size="sm" icon="rotate-ccw">Restaurar</x-catalog.button>
                                    </form>
                                @endcan

                                @can('forceDelete', $fotografia)
                                    <form
                                        method="POST"
                                        action="{{ route('admin.fotografias.force-destroy', $fotografia->id) }}"
                                        data-confirm-title="Confirmar exclusão permanente"
                                        data-confirm-message="Excluir permanentemente esta fotografia? Esta operação é irreversível."
                                        data-confirm-label="Excluir permanentemente"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <x-catalog.button type="submit" variant="danger" size="sm">Excluir permanentemente</x-catalog.button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
    </div>
</x-layouts.admin>
