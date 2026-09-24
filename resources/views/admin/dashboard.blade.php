@php
    $cards = [
        ['label' => 'Itens do acervo', 'value' => $totalItens, 'href' => route('admin.fotografias.index')],
        ['label' => 'Fotografias', 'value' => $totalFotografias, 'href' => route('admin.fotografias.index')],
        ['label' => 'Arquivos digitais', 'value' => $totalArquivos, 'href' => null],
        ['label' => 'Usuários ativos', 'value' => $usuariosAtivos, 'href' => null],
    ];

    $perfil = auth()->user()->isAdmin() ? 'Administrador' : 'Operador';
@endphp

<x-layouts.admin title="Admin | Memórias Vivas">
    <div class="mx-auto max-w-7xl px-6 py-8">
        <section class="mb-8">
            <p class="text-sm font-medium text-[#6B5E2E]">Dashboard</p>
            <h1 class="mt-2 text-4xl font-semibold text-stone-950">Olá, {{ $perfil }}!</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-stone-600">
                Acompanhe o estado real da catalogação e acesse os fluxos administrativos já disponíveis.
            </p>
        </section>

        <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($cards as $card)
                <article class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-3xl font-semibold text-stone-950">{{ number_format($card['value'], 0, ',', '.') }}</p>
                            <p class="mt-1 text-sm font-medium text-stone-700">{{ $card['label'] }}</p>
                        </div>
                        <span class="grid h-12 w-12 place-items-center rounded-full bg-[#E8E2C9] text-sm font-bold text-[#4A3F18]">
                            {{ strtoupper(substr($card['label'], 0, 1)) }}
                        </span>
                    </div>

                    @if ($card['href'])
                        <a
                            href="{{ $card['href'] }}"
                            class="mt-6 inline-flex text-sm font-semibold text-[#173F35] hover:text-[#0f2b24]"
                        >
                            Ver todos
                        </a>
                    @else
                        <p class="mt-6 text-sm font-medium text-stone-500">Módulo em preparação</p>
                    @endif
                </article>
            @endforeach
        </section>

        <section class="mt-6 grid gap-5 xl:grid-cols-2">
            <article class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-semibold text-stone-950">Tipos de arquivos</h2>
                @if ($tiposArquivo->isEmpty())
                    <p class="mt-5 text-sm leading-6 text-stone-600">Nenhum arquivo digital cadastrado.</p>
                @else
                    <div class="mt-6 space-y-4">
                        @foreach ($tiposArquivo as $item)
                            <div>
                                <div class="mb-2 flex items-center justify-between gap-4 text-sm">
                                    <span class="font-medium text-stone-700">{{ $item['label'] }}</span>
                                    <span class="text-stone-600">{{ number_format($item['total'], 0, ',', '.') }} · {{ $item['percent'] }}%</span>
                                </div>
                                <div class="h-2 rounded-full bg-stone-100">
                                    <div class="h-2 rounded-full bg-[#173F35]" style="width: {{ max($item['percent'], 3) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>

            <article class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-semibold text-stone-950">Itens por década</h2>
                @if ($itensPorDecada->isEmpty())
                    <p class="mt-5 text-sm leading-6 text-stone-600">Ainda não há itens com data cadastrada.</p>
                @else
                    <div class="mt-6 space-y-4">
                        @foreach ($itensPorDecada->take(6) as $item)
                            <div>
                                <div class="mb-2 flex items-center justify-between gap-4 text-sm">
                                    <span class="font-medium text-stone-700">{{ $item['label'] }}</span>
                                    <span class="text-stone-600">{{ number_format($item['total'], 0, ',', '.') }} · {{ $item['percent'] }}%</span>
                                </div>
                                <div class="h-2 rounded-full bg-stone-100">
                                    <div class="h-2 rounded-full bg-[#6B5E2E]" style="width: {{ max($item['percent'], 3) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>
        </section>

        <section class="mt-6 rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h2 class="text-2xl font-semibold text-stone-950">Itens mais recentes</h2>
                    <p class="mt-1 text-sm text-stone-600">Últimos registros cadastrados no acervo.</p>
                </div>
                <a
                    href="{{ route('admin.fotografias.index') }}"
                    class="inline-flex items-center justify-center rounded-md border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2"
                >
                    Ver fotografias
                </a>
            </div>

            @if ($itensRecentes->isEmpty())
                <div class="mt-8 rounded-lg border border-dashed border-stone-300 bg-stone-50 px-6 py-10 text-center">
                    <h3 class="font-semibold text-stone-950">Nenhum item cadastrado</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-stone-600">
                        Os itens cadastrados no acervo aparecerão aqui automaticamente.
                    </p>
                </div>
            @else
                <div class="mt-6 divide-y divide-stone-200">
                    @foreach ($itensRecentes as $item)
                        @php($thumbnail = $item->thumbnailAdministrativa())
                        <div class="flex flex-col gap-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-4">
                                @if ($thumbnail)
                                    <img
                                        src="{{ route('admin.arquivos.show', $thumbnail) }}"
                                        alt="Miniatura de {{ $item->titulo }}"
                                        width="{{ $thumbnail->width ?: 64 }}"
                                        height="{{ $thumbnail->height ?: 64 }}"
                                        loading="lazy"
                                        class="h-16 w-16 shrink-0 rounded-md bg-stone-100 object-cover"
                                    >
                                @else
                                    <div class="grid h-16 w-16 shrink-0 place-items-center rounded-md bg-stone-100 text-lg font-semibold text-stone-950">
                                        {{ strtoupper(substr($item->titulo, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <h3 class="font-semibold text-stone-950">{{ $item->titulo }}</h3>
                                    <p class="mt-1 text-sm text-stone-600">
                                        {{ $item->dataHistorica()->label() }}
                                        @if ($item->autor)
                                            · {{ $item->autor->nome }}
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2 sm:justify-end">
                                <span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-semibold text-stone-700">
                                    {{ $item->statusLabel() }}
                                </span>
                                <span class="rounded-full bg-[#E8E2C9] px-3 py-1 text-xs font-semibold text-[#4A3F18]">
                                    {{ ucfirst($item->tipo_item) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-layouts.admin>
