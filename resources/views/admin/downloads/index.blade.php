<x-layouts.admin title="Relatório de downloads | Memórias Vivas">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-admin.page-header
            eyebrow="Leitura do acervo"
            title="Relatório de downloads"
            description="Entenda quais fotografias são consultadas, por quem e para quais finalidades."
        >
            {{-- Nas duas abas: exporta todos os registros dos filtros aplicados. --}}
            <x-slot:actions>
                <x-catalog.button variant="outline" icon="download" :href="route('admin.downloads.export', array_filter($filtros))">Exportar CSV</x-catalog.button>
            </x-slot:actions>
        </x-admin.page-header>

        @php
            // Campos de filtro e os ids dos controles: a mensagem de cada erro fica no resumo, ligada ao campo por aria-describedby.
            $camposFiltro = ['q' => 'download-search', 'de' => 'download-from', 'ate' => 'download-to'];
            // Endereço de uma aba com os filtros informados; a aba padrão fica sem parâmetro.
            $urlAba = fn (string $chave, array $parametros): string => route('admin.downloads.index', array_filter(
                [...$parametros, 'aba' => $chave === array_key_first($abas) ? null : $chave],
                fn ($valor): bool => $valor !== null && $valor !== '',
            ));
            $temFiltro = $filtros['q'] !== '' || $filtros['de'] !== '' || $filtros['ate'] !== '';
        @endphp
        <form method="GET" action="{{ route('admin.downloads.index') }}" class="mt-6 grid gap-4 rounded-lg border border-line bg-white p-5 shadow-card md:grid-cols-[minmax(0,1fr)_auto_auto_auto] md:items-end">
            {{-- Filtrar mantém a aba aberta; os filtros valem para as duas. --}}
            @if ($aba !== array_key_first($abas))
                <input type="hidden" name="aba" value="{{ $aba }}">
            @endif
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
                <x-catalog.button variant="secondary" :href="$urlAba($aba, [])">Limpar</x-catalog.button>
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

        {{-- Abas como navegação entre páginas (links com aria-current), não role="tablist": cada aba é um endereço próprio. --}}
        <nav class="mt-6 border-b border-line" aria-label="Seções do relatório">
            <ul class="-mb-px flex gap-6">
                @foreach ($abas as $chave => $rotulo)
                    <li>
                        <a
                            href="{{ $urlAba($chave, $filtros) }}"
                            @if ($aba === $chave) aria-current="page" @endif
                            class="inline-flex items-center gap-2 rounded-t border-b-2 border-transparent px-1 py-3 text-sm font-semibold text-fg-muted transition-colors hover:border-line-strong hover:text-fg focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-institutional aria-[current=page]:border-institutional aria-[current=page]:text-institutional"
                        >
                            <x-dynamic-component :component="$chave === 'registros' ? 'lucide-list' : 'lucide-chart-column'" class="size-4" aria-hidden="true" />
                            {{ $rotulo }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        @if ($aba === 'estatisticas')
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

            @php
                // Painéis de distribuição: percentuais sobre os downloads encontrados.
                $paineisUso = [
                    ['id' => 'download-profiles-title', 'eyebrow' => 'Quem consulta', 'titulo' => 'Perfis dos solicitantes', 'grupos' => $perfisAgrupados, 'vazio' => 'Os perfis aparecerão quando houver downloads.'],
                    ['id' => 'download-reasons-title', 'eyebrow' => 'Como o acervo é usado', 'titulo' => 'Motivos da consulta', 'grupos' => $motivosAgrupados, 'vazio' => 'Os motivos aparecerão quando houver downloads.'],
                ];
                $paineisOrigem = [
                    ['id' => 'download-countries-title', 'eyebrow' => 'De onde vêm', 'titulo' => 'Países', 'grupos' => $paisesAgrupados, 'vazio' => 'Os países aparecerão quando houver downloads com origem informada.', 'base' => $total],
                    ['id' => 'download-states-title', 'eyebrow' => 'No Brasil', 'titulo' => 'Estados', 'grupos' => $estadosAgrupados, 'vazio' => 'Nenhum download do Brasil no período.', 'base' => $estadosAgrupados->sum('total')],
                    ['id' => 'download-cities-title', 'eyebrow' => 'No Brasil', 'titulo' => 'Cidades mais frequentes', 'grupos' => $municipiosAgrupados, 'vazio' => 'Nenhum download do Brasil no período.', 'base' => $estadosAgrupados->sum('total')],
                ];
            @endphp

            <p class="mt-7 text-xs leading-5 text-fg-muted">Distribuição calculada sobre os {{ number_format($total, 0, ',', '.') }} {{ $total === 1 ? 'download encontrado' : 'downloads encontrados' }}. Os filtros acima também se aplicam aos percentuais.</p>
            <div class="mt-3 grid gap-4 lg:grid-cols-3">
                @foreach ($paineisUso as $painel)
                    @include('admin.downloads._distribuicao', ['painel' => $painel + ['base' => $total]])
                @endforeach
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
                                @php $percentual = $foto->total / $total * 100; @endphp
                                <li>
                                    <div class="flex items-start justify-between gap-4 text-sm">
                                        <div class="flex min-w-0 gap-3">
                                            <span class="w-5 flex-none text-xs font-bold tabular-nums text-ink-400">{{ str_pad((string) ($posicao + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                            @if ($foto->item && ! $foto->item->trashed())
                                                <a href="{{ route('admin.fotografias.show', $foto->item) }}" class="min-w-0 font-medium text-institutional underline decoration-mist-300 underline-offset-2 hover:decoration-institutional">{{ $foto->rotulo }}</a>
                                            @else
                                                <span class="min-w-0 font-medium text-ink-700">{{ $foto->rotulo }}</span>
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

            <section class="mt-8" aria-labelledby="download-origin-title">
                <p class="text-xs font-bold tracking-wide text-institutional uppercase">Origem dos solicitantes</p>
                <h2 id="download-origin-title" class="mt-1 text-[1.4rem] font-semibold text-institutional">Localidade informada no download</h2>
                <p class="mt-2 text-xs leading-5 text-fg-muted">Estados e cidades consideram só os downloads feitos do Brasil. Downloads anteriores à coleta da localidade aparecem como “Não informado”.</p>
                <div class="mt-3 grid gap-4 lg:grid-cols-3">
                    @foreach ($paineisOrigem as $painel)
                        @include('admin.downloads._distribuicao', ['painel' => $painel + ['nivel' => 'h3']])
                    @endforeach
                </div>

                <section class="mt-4 overflow-hidden rounded-lg border border-line bg-white shadow-card" aria-labelledby="download-profiles-country-title">
                    <div class="border-b border-line px-5 py-4 sm:px-6">
                        <h3 id="download-profiles-country-title" class="text-[1.4rem] font-semibold text-institutional">Perfis por país</h3>
                        <p class="mt-1 text-xs text-fg-muted">Os cinco países com mais downloads e o perfil de quem baixou.</p>
                    </div>
                    @if ($perfisPorPais->isEmpty())
                        <x-ui.empty-state heading="h4" icon="globe" title="Nenhum download no período" description="O cruzamento entre perfil e país aparecerá quando houver downloads." />
                    @else
                        <x-admin.aviso-rolagem-tabela />
                        <div class="overflow-x-auto">
                            <table class="admin-table w-full min-w-[36rem] text-left text-sm">
                                <thead>
                                    <tr>
                                        <th scope="col">País</th>
                                        <th scope="col" class="text-right">Downloads</th>
                                        <th scope="col">Perfis</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($perfisPorPais as $linha)
                                        <tr>
                                            <th scope="row" class="px-5 py-4 text-left font-semibold text-fg">{{ $linha->rotulo }}</th>
                                            <td class="text-right tabular-nums text-ink-700">{{ number_format($linha->total, 0, ',', '.') }}</td>
                                            <td class="text-ink-700">
                                                <ul class="flex flex-wrap gap-x-4 gap-y-1">
                                                    @foreach ($linha->perfis as $perfil)
                                                        <li>{{ $perfil->rotulo }} <span class="tabular-nums text-fg-subtle">({{ $perfil->total }})</span></li>
                                                    @endforeach
                                                </ul>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            </section>
        @endif

        @if ($aba === 'registros')
            <section class="mt-6" aria-labelledby="download-records-title">
                <div class="flex flex-wrap items-end justify-between gap-2">
                    <div>
                        <p class="text-xs font-bold tracking-wide text-institutional uppercase">Consulta detalhada</p>
                        <h2 id="download-records-title" class="mt-1 text-[1.4rem] font-semibold text-institutional">Todos os registros</h2>
                    </div>
                    <p class="text-xs text-fg-subtle">{{ number_format($total, 0, ',', '.') }} {{ $total === 1 ? 'registro encontrado' : 'registros encontrados' }}</p>
                </div>
                <x-admin.table class="mt-4" table-class="min-w-[65rem]" :paginator="$registros" :vazia="$registros->isEmpty()">
                    <x-slot:vazio>
                        @if ($temFiltro)
                            <x-ui.empty-state heading="h3" icon="search-x" title="Nenhum download encontrado para os filtros selecionados" description="Amplie o período ou revise o texto pesquisado.">
                                <x-slot:action>
                                    <x-catalog.button variant="secondary" size="sm" :href="$urlAba($aba, [])">Limpar filtros</x-catalog.button>
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
                                <td class="min-w-56 max-w-64">
                                    @if ($registro->itemAcervo && ! $registro->itemAcervo->trashed())
                                        <a href="{{ route('admin.fotografias.show', $registro->itemAcervo) }}" class="font-semibold text-institutional underline underline-offset-2">{{ $registro->itemAcervo->titulo }}</a>
                                    @else
                                        <span class="font-semibold text-ink-800">{{ $registro->tituloFotografia() }}</span>
                                    @endif
                                    @php $codigoReferencia = $registro->itemAcervo?->codigo_referencia ?? $registro->codigo_referencia_item; @endphp
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
                                    @php
                                        $origem = match (true) {
                                            $registro->municipio !== null => $registro->municipio->nome.'/'.$registro->estado?->sigla,
                                            $registro->estado !== null => $registro->estado->sigla.', '.$registro->pais?->nome,
                                            default => $registro->pais?->nome,
                                        };
                                    @endphp
                                    <span class="mt-1 flex items-center gap-1 text-xs text-fg-subtle">
                                        <x-lucide-map-pin class="size-3 flex-none" aria-hidden="true" />
                                        <span><span class="sr-only">Origem: </span>{{ $origem ?? 'Origem não informada' }}</span>
                                    </span>
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
        @endif
    </div>
</x-layouts.admin>
