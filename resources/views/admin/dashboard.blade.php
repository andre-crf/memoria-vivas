@php
    $cards = [
        ['label' => 'Itens do acervo', 'value' => $totalItens, 'href' => route('admin.fotografias.index')],
        ['label' => 'Fotografias', 'value' => $totalFotografias, 'href' => route('admin.fotografias.index')],
        ['label' => 'Arquivos digitais', 'value' => $totalArquivos, 'href' => null],
        ['label' => 'Usuários ativos', 'value' => $usuariosAtivos, 'href' => null],
    ];

    $perfil = auth()->user()->isAdmin() ? 'Administrador' : 'Operador';
@endphp

<x-layouts.admin title="Admin | Memórias Vivas" active="dashboard">
    <div class="mx-auto max-w-7xl">
        <section class="mb-8">
            <p class="text-sm font-medium text-[#3F7E94]">Dashboard</p>
            <h1 class="mt-2 font-serif text-4xl font-semibold text-[#173F7A]">Olá, {{ $perfil }}!</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-[#55709B]">
                Acompanhe o estado real da catalogação e acesse os fluxos administrativos já disponíveis.
            </p>
        </section>

        <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($cards as $card)
                <article class="rounded-lg border border-[#D8E2EF] bg-white p-5 shadow-sm shadow-[#173F7A]/5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-serif text-3xl font-semibold text-[#173F7A]">{{ number_format($card['value'], 0, ',', '.') }}</p>
                            <p class="mt-1 text-sm font-medium text-[#294B77]">{{ $card['label'] }}</p>
                        </div>
                        <span class="grid h-12 w-12 place-items-center rounded-full bg-[#DCEEEF] text-sm font-bold text-[#287C7C]">
                            {{ strtoupper(substr($card['label'], 0, 1)) }}
                        </span>
                    </div>

                    @if ($card['href'])
                        <a
                            href="{{ $card['href'] }}"
                            class="mt-6 inline-flex text-sm font-semibold text-[#173F7A] hover:text-[#0E2A52]"
                        >
                            Ver todos
                        </a>
                    @else
                        <p class="mt-6 text-sm font-medium text-[#7A8DA8]">Módulo em preparação</p>
                    @endif
                </article>
            @endforeach
        </section>

        <section class="mt-6 grid gap-5 xl:grid-cols-2">
            <article class="rounded-lg border border-[#D8E2EF] bg-white p-6 shadow-sm shadow-[#173F7A]/5">
                <h2 class="font-serif text-2xl font-semibold text-[#173F7A]">Tipos de arquivos</h2>
                @if ($tiposArquivo->isEmpty())
                    <p class="mt-5 text-sm leading-6 text-[#55709B]">Nenhum arquivo digital cadastrado.</p>
                @else
                    <div class="mt-6 space-y-4">
                        @foreach ($tiposArquivo as $item)
                            <div>
                                <div class="mb-2 flex items-center justify-between gap-4 text-sm">
                                    <span class="font-medium text-[#294B77]">{{ $item['label'] }}</span>
                                    <span class="text-[#55709B]">{{ number_format($item['total'], 0, ',', '.') }} · {{ $item['percent'] }}%</span>
                                </div>
                                <div class="h-2 rounded-full bg-[#E8EEF6]">
                                    <div class="h-2 rounded-full bg-[#3FA39B]" style="width: {{ max($item['percent'], 3) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>

            <article class="rounded-lg border border-[#D8E2EF] bg-white p-6 shadow-sm shadow-[#173F7A]/5">
                <h2 class="font-serif text-2xl font-semibold text-[#173F7A]">Itens por década</h2>
                @if ($itensPorDecada->isEmpty())
                    <p class="mt-5 text-sm leading-6 text-[#55709B]">Ainda não há itens com data cadastrada.</p>
                @else
                    <div class="mt-6 space-y-4">
                        @foreach ($itensPorDecada->take(6) as $item)
                            <div>
                                <div class="mb-2 flex items-center justify-between gap-4 text-sm">
                                    <span class="font-medium text-[#294B77]">{{ $item['label'] }}</span>
                                    <span class="text-[#55709B]">{{ number_format($item['total'], 0, ',', '.') }} · {{ $item['percent'] }}%</span>
                                </div>
                                <div class="h-2 rounded-full bg-[#E8EEF6]">
                                    <div class="h-2 rounded-full bg-[#7F65D8]" style="width: {{ max($item['percent'], 3) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>
        </section>

        <section class="mt-6 rounded-lg border border-[#D8E2EF] bg-white p-6 shadow-sm shadow-[#173F7A]/5">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h2 class="font-serif text-2xl font-semibold text-[#173F7A]">Itens mais recentes</h2>
                    <p class="mt-1 text-sm text-[#55709B]">Últimos registros cadastrados no acervo.</p>
                </div>
                <a
                    href="{{ route('admin.fotografias.index') }}"
                    class="inline-flex items-center justify-center rounded-md border border-[#D8E2EF] px-4 py-2 text-sm font-semibold text-[#173F7A] transition hover:bg-[#F4F8FC] focus:outline-none focus:ring-2 focus:ring-[#173F7A] focus:ring-offset-2"
                >
                    Ver fotografias
                </a>
            </div>

            @if ($itensRecentes->isEmpty())
                <div class="mt-8 rounded-lg border border-dashed border-[#C8D6E8] bg-[#F8FBFE] px-6 py-10 text-center">
                    <h3 class="font-semibold text-[#173F7A]">Nenhum item cadastrado</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-[#55709B]">
                        Os itens cadastrados no acervo aparecerão aqui automaticamente.
                    </p>
                </div>
            @else
                <div class="mt-6 divide-y divide-[#D8E2EF]">
                    @foreach ($itensRecentes as $item)
                        <div class="flex flex-col gap-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-4">
                                <div class="grid h-16 w-16 shrink-0 place-items-center rounded-md bg-[#E6EEF8] font-serif text-lg font-semibold text-[#173F7A]">
                                    {{ strtoupper(substr($item->titulo, 0, 1)) }}
                                </div>
                                <div>
                                    <h3 class="font-semibold text-[#173F7A]">{{ $item->titulo }}</h3>
                                    <p class="mt-1 text-sm text-[#55709B]">
                                        {{ $item->dataHistorica()->label() }}
                                        @if ($item->autor)
                                            · {{ $item->autor->nome }}
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2 sm:justify-end">
                                <span class="rounded-full bg-[#E8EEF6] px-3 py-1 text-xs font-semibold text-[#294B77]">
                                    {{ $item->statusLabel() }}
                                </span>
                                <span class="rounded-full bg-[#DCEEEF] px-3 py-1 text-xs font-semibold text-[#287C7C]">
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
