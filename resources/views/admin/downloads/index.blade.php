<x-layouts.admin title="Relatório de downloads | Memórias Vivas">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-admin.page-header
            eyebrow="Leitura do acervo"
            title="Relatório de downloads"
            description="Entenda quais fotografias são consultadas, por quem e para quais finalidades."
        />

        @php
            // Campos de filtro e os ids dos controles: a mensagem de cada erro fica no resumo, ligada ao campo por aria-describedby.
            $camposFiltro = ['q' => 'download-search', 'de' => 'download-from', 'ate' => 'download-to'];
        @endphp
        <form method="GET" action="{{ route('admin.downloads.index') }}" class="mt-6 grid gap-4 rounded-lg border border-line bg-white p-5 shadow-card md:grid-cols-[minmax(0,1fr)_auto_auto_auto] md:items-end">
            <x-catalog.label for="download-search" text="Buscar registro">
                <x-catalog.control id="download-search" name="q" type="search" :value="$filtros['q']" maxlength="100" placeholder="Fotografia, número, nome ou e-mail" error="q" />
            </x-catalog.label>
            <x-catalog.label for="download-from" text="De">
                <x-catalog.control id="download-from" name="de" type="date" :value="$filtros['de']" error="de" />
            </x-catalog.label>
            <x-catalog.label for="download-to" text="Até">
                <x-catalog.control id="download-to" name="ate" type="date" :value="$filtros['ate']" error="ate" />
            </x-catalog.label>
            <div class="flex flex-wrap items-center gap-3">
                <x-catalog.button type="submit">Filtrar</x-catalog.button>
                <x-catalog.button variant="secondary" :href="route('admin.downloads.index')">Limpar</x-catalog.button>
            </div>
            @if ($errors->any())
                <x-ui.alert tone="danger" title="Confira as datas e tente novamente." class="md:col-span-4">
                    <ul>
                        @foreach ($camposFiltro as $campo => $id)
                            <x-catalog.error :field="$campo" :for="$id" as="li" />
                        @endforeach
                    </ul>
                </x-ui.alert>
            @endif
        </form>

        <div class="mt-6 grid gap-3 sm:grid-cols-3">
            <div class="rounded-lg border border-line border-l-4 border-l-institutional bg-white px-5 py-5 shadow-card">
                <p class="text-xs font-semibold tracking-wide text-ink-600 uppercase">Downloads encontrados</p>
                <p class="mt-2 text-3xl leading-none font-semibold tabular-nums text-institutional">{{ number_format($total, 0, ',', '.') }}</p>
                <p class="mt-2 text-xs text-fg-subtle">No período e na busca selecionados</p>
            </div>
            <div class="rounded-lg border border-line border-l-4 border-l-institutional-light bg-white px-5 py-5 shadow-card">
                <p class="text-xs font-semibold tracking-wide text-ink-600 uppercase">Fotografias consultadas</p>
                <p class="mt-2 text-3xl leading-none font-semibold tabular-nums text-institutional">{{ number_format($fotografiasDistintas, 0, ',', '.') }}</p>
                <p class="mt-2 text-xs text-fg-subtle">Registros diferentes</p>
            </div>
            <div class="rounded-lg border border-line border-l-4 border-l-heritage-700 bg-white px-5 py-5 shadow-card">
                <p class="text-xs font-semibold tracking-wide text-ink-600 uppercase">Download mais recente</p>
                <p class="mt-3 text-lg font-semibold text-institutional"><x-data-hora :valor="$ultimoDownload" formato="curto" vazio="Nenhum registro" /></p>
                <p class="mt-2 text-xs text-fg-subtle">Entre os resultados encontrados</p>
            </div>
        </div>

        <section class="mt-6 overflow-hidden rounded-lg bg-institutional text-white" aria-labelledby="download-trend-title">
            <div class="flex flex-wrap items-end justify-between gap-3 border-b border-white/15 px-5 py-5 sm:px-7">
                <div>
                    <p class="text-[0.68rem] font-bold tracking-[0.14em] text-institutional-muted uppercase">Ritmo de consulta</p>
                    <h2 id="download-trend-title" class="mt-2 text-[1.5rem] leading-tight font-semibold">Downloads ao longo do tempo</h2>
                </div>
                <p class="text-xs text-institutional-muted">{{ $periodoSerie }} · horário de Umuarama</p>
            </div>
            @if ($maiorDia === 0)
                <p class="px-5 py-12 text-center text-sm text-white/75">Ainda não há downloads neste intervalo.</p>
            @else
                <div class="overflow-x-auto px-5 pt-8 pb-6 sm:px-7">
                    <ol class="flex min-w-[38rem] items-end gap-3" aria-label="Downloads por intervalo">
                        @foreach ($serie as $intervalo)
                            <li class="flex min-w-0 flex-1 flex-col items-center text-center">
                                <span class="mb-2 text-xs font-bold tabular-nums text-white">{{ $intervalo['total'] }}</span>
                                <div class="flex h-36 w-full items-end border-b border-white/25" aria-hidden="true">
                                    <div class="mx-auto w-full max-w-9 rounded-t-sm {{ $intervalo['total'] ? 'bg-institutional-muted' : 'bg-white/20' }}" style="height: {{ $intervalo['total'] ? max(5, round($intervalo['total'] / $maiorDia * 100)) : 2 }}%"></div>
                                </div>
                                <span class="mt-2 min-h-8 text-[0.65rem] leading-4 text-white/75">{{ $intervalo['rotulo'] }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </section>

        <p class="mt-7 text-xs leading-5 text-fg-muted">Distribuição calculada sobre os {{ number_format($total, 0, ',', '.') }} {{ $total === 1 ? 'download encontrado' : 'downloads encontrados' }}. Os filtros acima também se aplicam aos percentuais.</p>
        <div class="mt-3 grid gap-4 lg:grid-cols-3">
            <section class="rounded-lg border border-line bg-white p-5 shadow-card sm:p-6" aria-labelledby="download-profiles-title">
                <div class="border-b border-line pb-4">
                    <p class="text-xs font-bold tracking-wide text-institutional uppercase">Quem consulta</p>
                    <h2 id="download-profiles-title" class="mt-1 text-[1.4rem] font-semibold text-institutional">Perfis dos solicitantes</h2>
                </div>
                @if ($perfisAgrupados->isEmpty())
                    <p class="py-10 text-sm text-fg-muted">Os perfis aparecerão quando houver downloads.</p>
                @else
                    <ol class="mt-5 space-y-5">
                        @foreach ($perfisAgrupados as $perfil)
                            @php($percentual = $perfil->total / $total * 100)
                            <li>
                                <div class="flex items-start justify-between gap-3 text-sm">
                                    <span class="min-w-0 font-medium text-ink-800">{{ $titulosPerfis[$perfil->perfil_download_id] ?? 'Não informado' }}</span>
                                    <span class="flex-none whitespace-nowrap font-bold tabular-nums text-institutional">{{ number_format($percentual, 1, ',', '.') }}% <span class="font-normal text-fg-subtle">({{ $perfil->total }})</span></span>
                                </div>
                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-mist-150" aria-hidden="true"><div class="h-full rounded-full bg-institutional-light" style="width: {{ $percentual }}%"></div></div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
            <section class="rounded-lg border border-line bg-white p-5 shadow-card sm:p-6" aria-labelledby="download-reasons-title">
                <div class="border-b border-line pb-4">
                    <p class="text-xs font-bold tracking-wide text-institutional uppercase">Como o acervo é usado</p>
                    <h2 id="download-reasons-title" class="mt-1 text-[1.4rem] font-semibold text-institutional">Motivos da consulta</h2>
                </div>
                @if ($motivosAgrupados->isEmpty())
                    <p class="py-10 text-sm text-fg-muted">Os motivos aparecerão quando houver downloads.</p>
                @else
                    <ol class="mt-5 space-y-5">
                        @foreach ($motivosAgrupados as $motivo)
                            @php($percentual = $motivo->total / $total * 100)
                            <li>
                                <div class="flex items-start justify-between gap-3 text-sm">
                                    <span class="min-w-0 font-medium text-ink-800">{{ $titulosMotivos[$motivo->motivo_download_id] ?? 'Não informado' }}</span>
                                    <span class="flex-none whitespace-nowrap font-bold tabular-nums text-institutional">{{ number_format($percentual, 1, ',', '.') }}% <span class="font-normal text-fg-subtle">({{ $motivo->total }})</span></span>
                                </div>
                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-mist-150" aria-hidden="true"><div class="h-full rounded-full bg-institutional-light" style="width: {{ $percentual }}%"></div></div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
            <section class="rounded-lg border border-line bg-white p-5 shadow-card sm:p-6" aria-labelledby="download-top-title">
                <div class="border-b border-line pb-4">
                    <p class="text-xs font-bold tracking-wide text-institutional uppercase">Fotografias em destaque</p>
                    <h2 id="download-top-title" class="mt-1 text-[1.4rem] font-semibold text-institutional">Mais baixadas</h2>
                </div>
                @if ($fotografiasAgrupadas->isEmpty())
                    <p class="py-10 text-sm text-fg-muted">As fotografias mais baixadas aparecerão aqui.</p>
                @else
                    <ol class="mt-5 space-y-5">
                        @foreach ($fotografiasAgrupadas as $posicao => $foto)
                            @php($item = $fotografiasRanking[$foto->item_acervo_id] ?? null)
                            @php($percentual = $foto->total / $total * 100)
                            <li>
                                <div class="flex items-start justify-between gap-4 text-sm">
                                    <div class="flex min-w-0 gap-3">
                                        <span class="w-5 flex-none text-xs font-bold tabular-nums text-ink-400">{{ str_pad((string) ($posicao + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                        @if ($item && ! $item->trashed())
                                            <a href="{{ route('admin.fotografias.show', $item) }}" class="min-w-0 font-medium text-institutional underline decoration-mist-300 underline-offset-2 hover:decoration-institutional">{{ $item->titulo }}</a>
                                        @else
                                            <span class="min-w-0 font-medium text-ink-700">{{ $foto->item_acervo_id === null ? 'Fotografias excluídas' : ($item?->titulo ?? 'Fotografia removida') }}</span>
                                        @endif
                                    </div>
                                    <span class="flex-none whitespace-nowrap font-bold tabular-nums text-institutional">{{ number_format($percentual, 1, ',', '.') }}% <span class="font-normal text-fg-subtle">({{ $foto->total }})</span></span>
                                </div>
                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-mist-150" aria-hidden="true"><div class="h-full rounded-full bg-institutional" style="width: {{ $percentual }}%"></div></div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        </div>

        <section class="mt-8" aria-labelledby="download-records-title">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <p class="text-xs font-bold tracking-wide text-institutional uppercase">Consulta detalhada</p>
                    <h2 id="download-records-title" class="mt-1 text-[1.4rem] font-semibold text-institutional">Todos os registros</h2>
                </div>
                <div class="flex flex-wrap items-center gap-4">
                    <p class="text-xs text-fg-subtle">{{ number_format($total, 0, ',', '.') }} {{ $total === 1 ? 'registro encontrado' : 'registros encontrados' }}</p>
                    <x-catalog.button variant="outline" size="sm" icon="download" :href="route('admin.downloads.export', array_filter($filtros))">Exportar CSV</x-catalog.button>
                </div>
            </div>
            <x-admin.table class="mt-4" table-class="min-w-[65rem]" :paginator="$registros" :vazia="$registros->isEmpty()">
                <x-slot:vazio>
                    @if ($filtros['q'] || $filtros['de'] || $filtros['ate'])
                        <x-ui.empty-state heading="h3" icon="search-x" title="Nenhum download encontrado para os filtros selecionados" description="Amplie o período ou revise o texto pesquisado.">
                            <x-slot:action>
                                <x-catalog.button variant="secondary" size="sm" :href="route('admin.downloads.index')">Limpar filtros</x-catalog.button>
                            </x-slot:action>
                        </x-ui.empty-state>
                    @else
                        <x-ui.empty-state heading="h3" icon="download" title="Nenhum download encontrado ainda" description="Os pedidos de download feitos no site público aparecerão aqui." />
                    @endif
                </x-slot:vazio>

                <thead>
                    <tr>
                        <th scope="col">Data</th>
                        <th scope="col">Fotografia</th>
                        <th scope="col">Solicitante</th>
                        <th scope="col">Perfil e motivo</th>
                        <th scope="col">Finalidade informada</th>
                        <th scope="col">Arquivo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($registros as $registro)
                        <tr>
                            <td class="whitespace-nowrap text-ink-700"><x-data-hora :valor="$registro->created_at" formato="curto" /></td>
                            <td class="max-w-64">
                                @if ($registro->itemAcervo && ! $registro->itemAcervo->trashed())
                                    <a href="{{ route('admin.fotografias.show', $registro->itemAcervo) }}" class="font-semibold text-institutional underline underline-offset-2">{{ $registro->itemAcervo->titulo }}</a>
                                @else
                                    <span class="font-semibold text-ink-800">{{ $registro->tituloFotografia() }}</span>
                                @endif
                                @php($codigoReferencia = $registro->itemAcervo?->codigo_referencia ?? $registro->codigo_referencia_item)
                                @if ($codigoReferencia)<span class="mt-1 block text-xs text-fg-muted">{{ $codigoReferencia }}</span>@endif
                                @if ($registro->fotografiaExcluida())
                                    <span class="mt-1 block text-xs text-fg-subtle">Excluída definitivamente</span>
                                @else
                                    <span class="mt-1 block text-xs text-fg-subtle">#{{ $registro->item_acervo_id }}</span>
                                @endif
                            </td>
                            <td class="max-w-52 text-ink-700">
                                <span class="block font-semibold text-fg">{{ $registro->nome ?: 'Não informado' }}</span>
                                @if ($registro->email)<span class="mt-1 block break-all text-xs">{{ $registro->email }}</span>@endif
                            </td>
                            <td class="max-w-48 text-ink-700">
                                <span class="block">{{ $registro->perfilDownload?->titulo ?? 'Perfil não informado' }}</span>
                                <span class="mt-1 block text-xs text-fg-subtle">{{ $registro->motivoDownload?->titulo ?? 'Motivo não informado' }}</span>
                            </td>
                            <td class="min-w-56 max-w-80 whitespace-pre-wrap break-words text-ink-700">{{ $registro->finalidade ?: 'Não informada' }}</td>
                            <td class="whitespace-nowrap text-ink-700">{{ $registro->rotuloVersaoEntregue() ?? 'Não informado' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </x-admin.table>
        </section>
    </div>
</x-layouts.admin>
