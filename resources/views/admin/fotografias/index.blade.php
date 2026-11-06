<x-layouts.admin title="Fotografias | Memórias Vivas">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-admin.page-header
            class="mb-6"
            eyebrow="Acervo"
            title="Fotografias cadastradas"
            description="Lista administrativa das fotografias ativas cadastradas no acervo."
        >
            <x-slot:actions>
                @can('restore', new \App\Models\ItemAcervo)
                    <x-catalog.button variant="secondary" icon="trash-2" :href="route('admin.fotografias.trashed')">Lixeira</x-catalog.button>
                @endcan
                <x-catalog.button icon="plus" :href="route('admin.fotografias.create')">Nova fotografia</x-catalog.button>
            </x-slot:actions>
        </x-admin.page-header>

        <x-admin.filtros-fotografias
            :action="route('admin.fotografias.index')"
            :filtros="$filtros"
            :status-options="$statusOptions"
            :visibilidades="$visibilidades"
            :total="$fotografias->total()"
            :search-error="$searchError"
            :ordenacao="$ordenacao"
        />

        <x-admin.table :paginator="$fotografias" :vazia="$fotografias->isEmpty()">
            <x-slot:vazio>
                @if ($filtros->possuiCriterios())
                    <x-admin.busca-sem-resultados
                        :action="route('admin.fotografias.index')"
                        mensagem="Nenhuma fotografia encontrada para os filtros aplicados"
                        dica="Revise o texto pesquisado ou remova algum filtro."
                        limpar="Limpar filtros"
                    />
                @else
                    <x-ui.empty-state icon="images" title="Nenhuma fotografia cadastrada" description="Quando houver fotografias ativas no acervo, elas aparecerão nesta listagem." />
                @endif
            </x-slot:vazio>

            <thead>
                <tr>
                    <th scope="col">Imagem</th>
                    <x-admin.coluna-ordenavel rotulo="Título" chave="titulo" :ordenacao="$ordenacao" :action="route('admin.fotografias.index')" :parametros="$filtros->parametros()" />
                    <x-admin.coluna-ordenavel rotulo="Data" chave="data" :ordenacao="$ordenacao" :action="route('admin.fotografias.index')" :parametros="$filtros->parametros()" />
                    <x-admin.coluna-ordenavel rotulo="Status" chave="status" :ordenacao="$ordenacao" :action="route('admin.fotografias.index')" :parametros="$filtros->parametros()" />
                    <x-admin.coluna-ordenavel rotulo="Visibilidade" chave="visibilidade" :ordenacao="$ordenacao" :action="route('admin.fotografias.index')" :parametros="$filtros->parametros()" />
                    <x-admin.coluna-ordenavel rotulo="Adicionada em" chave="adicionada" :ordenacao="$ordenacao" :action="route('admin.fotografias.index')" :parametros="$filtros->parametros()" class="whitespace-nowrap" />
                    <th scope="col" class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($fotografias as $fotografia)
                    @php $thumbnail = $fotografia->thumbnailAdministrativa(); @endphp
                    <tr id="fotografia-{{ $fotografia->id }}" class="relative">
                        <td>
                            @if ($thumbnail)
                                <img
                                    src="{{ route('admin.arquivos.show', $thumbnail) }}"
                                    alt="Miniatura de {{ $fotografia->titulo }}"
                                    width="{{ $thumbnail->width ?: 64 }}"
                                    height="{{ $thumbnail->height ?: 64 }}"
                                    loading="lazy"
                                    class="h-16 w-16 rounded-md bg-mist-100 object-cover"
                                >
                            @else
                                @php
                                    $originalDaLinha = $fotografia->arquivos->firstWhere('versao_arquivo', 'original');
                                    [$semImagem, $semImagemCor, $semImagemDica] = match (true) {
                                        (bool) $originalDaLinha?->derivacoesEmAndamento() => ['Gerando', 'text-[0.625rem] tracking-[0.06em] text-institutional', 'As versões otimizadas estão sendo geradas em segundo plano.'],
                                        (bool) $originalDaLinha?->derivacoesSemProgresso(),
                                        $originalDaLinha?->derivacoes_status === \App\Enums\StatusDerivacoes::Falhou => ['Falhou', 'text-[0.625rem] tracking-[0.06em] text-danger', 'A geração das versões otimizadas falhou. Abra a fotografia para regenerar.'],
                                        default => ['Sem imagem', 'text-xs tracking-[0.12em] text-ink-400', null],
                                    };
                                @endphp
                                <div @if ($semImagemDica) title="{{ $semImagemDica }}" @endif class="grid h-16 w-16 place-items-center rounded-md border border-dashed border-mist-300 bg-surface-muted font-semibold uppercase {{ $semImagemCor }}">
                                    {{ $semImagem }}
                                </div>
                            @endif
                        </td>
                        <td class="max-w-md">
                            <p class="truncate font-semibold text-fg" title="{{ $fotografia->titulo }}"><x-admin.link-linha :href="route('admin.fotografias.show', $fotografia)">{{ $fotografia->titulo }}</x-admin.link-linha></p>
                            <p class="mt-1 text-xs text-fg-subtle">#{{ $fotografia->id }}</p>
                        </td>
                        <td class="whitespace-nowrap text-ink-700">{{ $fotografia->dataHistorica()->label() }}</td>
                        <td class="whitespace-nowrap"><x-admin.status-fotografia :fotografia="$fotografia" /></td>
                        <td class="whitespace-nowrap"><x-ui.badge :tone="$fotografia->visibilidade->isPublico() ? 'heritage' : 'neutral'">{{ $fotografia->visibilidade->label() }}</x-ui.badge></td>
                        <td class="whitespace-nowrap text-fg-muted"><x-data-hora :valor="$fotografia->created_at" formato="curto" vazio="Não registrada" /></td>
                        <td>
                            <div class="relative z-10 flex justify-end gap-2">
                                <x-catalog.button variant="secondary" size="sm" :href="route('admin.fotografias.edit', $fotografia)">Editar</x-catalog.button>
                                @can('delete', $fotografia)
                                    <form
                                        method="POST"
                                        action="{{ route('admin.fotografias.destroy', $fotografia) }}"
                                        data-confirm-title="Confirmar exclusão"
                                        data-confirm-message="Excluir esta fotografia?"
                                        data-confirm-label="Excluir fotografia"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <x-catalog.button type="submit" variant="danger" size="sm">Excluir</x-catalog.button>
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
