@php
    $admin = auth()->user()->isAdmin();
    // emPreparacao: cartão sem destino porque o módulo ainda não existe. Usuários tem destino, mas só para o admin.
    $cards = [
        ['label' => 'Itens do acervo', 'value' => $totalItens, 'href' => route('admin.fotografias.index'), 'icon' => 'archive'],
        ['label' => 'Fotografias', 'value' => $totalFotografias, 'href' => route('admin.fotografias.index'), 'icon' => 'images'],
        ['label' => 'Arquivos digitais', 'value' => $totalArquivos, 'href' => null, 'icon' => 'file-image', 'emPreparacao' => true],
        ['label' => 'Usuários ativos', 'value' => $usuariosAtivos, 'href' => $admin ? route('admin.usuarios.index') : null, 'icon' => 'users'],
    ];
    $distribuicoes = [
        ['titulo' => 'Tipos de arquivos', 'itens' => $tiposArquivo, 'vazio' => 'Nenhum arquivo digital cadastrado.', 'barra' => 'bg-institutional'],
        ['titulo' => 'Itens por década', 'itens' => $itensPorDecada->take(6), 'vazio' => 'Ainda não há itens com data cadastrada.', 'barra' => 'bg-heritage-700'],
    ];

    $perfil = $admin ? 'Administrador' : 'Operador';
@endphp

<x-layouts.admin title="Admin | Memórias Vivas">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-admin.page-header
            class="mb-6"
            eyebrow="Dashboard"
            :title="'Olá, '.$perfil.'!'"
            description="Acompanhe o estado real da catalogação e acesse os fluxos administrativos já disponíveis."
        >
            @can('create', App\Models\ItemAcervo::class)
                <x-slot:actions>
                    <x-catalog.button icon="plus" :href="route('admin.fotografias.create')">Cadastrar fotografia</x-catalog.button>
                </x-slot:actions>
            @endcan
        </x-admin.page-header>

        {{-- Indicadores: a mesma superfície e o mesmo número dos cartões do relatório de downloads. --}}
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Indicadores do acervo">
            @foreach ($cards as $card)
                <article class="rounded-lg border border-line bg-white p-5 shadow-card">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-3xl leading-none font-semibold text-fg tabular-nums">{{ number_format($card['value'], 0, ',', '.') }}</p>
                            <p class="mt-2 text-sm font-medium text-ink-700">{{ $card['label'] }}</p>
                        </div>
                        <span class="grid size-11 flex-none place-items-center rounded-full bg-heritage-100 text-heritage-900" aria-hidden="true">
                            <x-dynamic-component :component="'lucide-'.$card['icon']" class="size-5" />
                        </span>
                    </div>

                    @if ($card['href'])
                        <a
                            href="{{ $card['href'] }}"
                            class="mt-5 inline-flex rounded text-sm font-semibold text-institutional hover:text-institutional-strong hover:underline hover:underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-institutional"
                        >
                            Ver todos<span class="sr-only">: {{ mb_strtolower($card['label']) }}</span>
                        </a>
                    @elseif ($card['emPreparacao'] ?? false)
                        <p class="mt-5 text-sm text-fg-subtle">Módulo em preparação</p>
                    @endif
                </article>
            @endforeach
        </section>

        @if ($resumoDownloads)
            <x-catalog.section class="mt-6" title="Downloads" title-id="dashboard-downloads-title" :description="'Pedidos de download feitos no site público nos últimos '.$resumoDownloads['dias'].' dias.'">
                <x-slot:actions>
                    <x-catalog.button variant="secondary" size="sm" icon="chart-column" :href="route('admin.downloads.index')">Ver relatório</x-catalog.button>
                </x-slot:actions>

                @if ($resumoDownloads['ultimo'] === null)
                    <div class="rounded-lg border border-dashed border-line-strong bg-surface-muted">
                        <x-ui.empty-state heading="h3" icon="download" title="Nenhum download registrado" description="Os pedidos de download feitos no site público aparecerão aqui." />
                    </div>
                @else
                    <dl class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <x-admin.info-block :label="'Últimos '.$resumoDownloads['dias'].' dias'">
                            <span class="text-2xl font-semibold text-fg tabular-nums">{{ number_format($resumoDownloads['total'], 0, ',', '.') }}</span>
                            <span class="text-sm text-fg-muted">{{ $resumoDownloads['total'] === 1 ? 'download' : 'downloads' }}</span>
                        </x-admin.info-block>
                        <x-admin.info-block label="Último download">
                            <x-data-hora :valor="$resumoDownloads['ultimo']" formato="curto" />
                        </x-admin.info-block>
                        <x-admin.info-block label="Perfil mais frequente" :empty="$resumoDownloads['perfil'] === null">
                            @if ($resumoDownloads['perfil'])
                                {{ $resumoDownloads['perfil']->rotulo }} <span class="text-fg-muted tabular-nums">({{ $resumoDownloads['perfil']->total }})</span>
                            @else
                                Sem downloads no período
                            @endif
                        </x-admin.info-block>
                        <x-admin.info-block label="Fotografia mais baixada" :empty="$resumoDownloads['fotografia'] === null">
                            @if ($resumoDownloads['fotografia']?->item && ! $resumoDownloads['fotografia']->item->trashed())
                                <a href="{{ route('admin.fotografias.show', $resumoDownloads['fotografia']->item) }}" class="rounded font-semibold text-institutional hover:text-institutional-strong hover:underline hover:underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-institutional">{{ $resumoDownloads['fotografia']->rotulo }}</a>
                                <span class="text-fg-muted tabular-nums">({{ $resumoDownloads['fotografia']->total }})</span>
                            @elseif ($resumoDownloads['fotografia'])
                                {{ $resumoDownloads['fotografia']->rotulo }} <span class="text-fg-muted tabular-nums">({{ $resumoDownloads['fotografia']->total }})</span>
                            @else
                                Sem downloads no período
                            @endif
                        </x-admin.info-block>
                    </dl>
                @endif
            </x-catalog.section>
        @endif

        <div class="mt-6 grid gap-6 xl:grid-cols-2">
            @foreach ($distribuicoes as $distribuicao)
                <x-catalog.section :title="$distribuicao['titulo']">
                    @if ($distribuicao['itens']->isEmpty())
                        <p class="text-sm leading-6 text-fg-muted">{{ $distribuicao['vazio'] }}</p>
                    @else
                        <ul class="space-y-4">
                            @foreach ($distribuicao['itens'] as $item)
                                <li>
                                    <div class="mb-2 flex items-center justify-between gap-4 text-sm">
                                        <span class="font-medium text-ink-700">{{ $item['label'] }}</span>
                                        <span class="text-fg-muted tabular-nums">{{ number_format($item['total'], 0, ',', '.') }} · {{ $item['percent'] }}%</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-mist-150" aria-hidden="true">
                                        <div class="h-2 rounded-full {{ $distribuicao['barra'] }}" style="width: {{ max($item['percent'], 3) }}%"></div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-catalog.section>
            @endforeach
        </div>

        <x-catalog.section class="mt-6" title="Itens mais recentes" description="Últimos registros cadastrados no acervo.">
            <x-slot:actions>
                <x-catalog.button variant="secondary" size="sm" :href="route('admin.fotografias.index')">Ver fotografias</x-catalog.button>
            </x-slot:actions>

            @if ($itensRecentes->isEmpty())
                <div class="rounded-lg border border-dashed border-line-strong bg-surface-muted">
                    <x-ui.empty-state heading="h3" title="Nenhum item cadastrado" description="Os itens cadastrados no acervo aparecerão aqui automaticamente." />
                </div>
            @else
                <ul class="-my-4 divide-y divide-line">
                    @foreach ($itensRecentes as $item)
                        @php($thumbnail = $item->thumbnailAdministrativa())
                        <li class="flex flex-col gap-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-center gap-4">
                                @if ($thumbnail)
                                    <img
                                        src="{{ route('admin.arquivos.show', $thumbnail) }}"
                                        alt="Miniatura de {{ $item->titulo }}"
                                        width="{{ $thumbnail->width ?: 64 }}"
                                        height="{{ $thumbnail->height ?: 64 }}"
                                        loading="lazy"
                                        class="h-16 w-16 shrink-0 rounded-md bg-mist-100 object-cover"
                                    >
                                @else
                                    <div class="grid h-16 w-16 shrink-0 place-items-center rounded-md bg-mist-100 text-lg font-semibold text-ink-700" aria-hidden="true">
                                        {{ strtoupper(substr($item->titulo, 0, 1)) }}
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <h3 class="font-semibold text-fg">{{ $item->titulo }}</h3>
                                    <p class="mt-1 text-sm text-fg-muted">
                                        {{ $item->dataHistorica()->label() }}
                                        @if ($item->autor)
                                            · {{ $item->autor->nome }}
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2 sm:justify-end">
                                <x-admin.status-fotografia :fotografia="$item" />
                                <x-ui.badge tone="heritage">{{ ucfirst($item->tipo_item) }}</x-ui.badge>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-catalog.section>
    </div>
</x-layouts.admin>
