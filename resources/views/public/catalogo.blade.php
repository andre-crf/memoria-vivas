<x-layouts.public title="Catálogo | Memórias Vivas" :catalog-query="$filtros->parametros()" :search-error="$searchError ?? null">
    @php
        $parametros = $filtros->parametros();
        $semParametro = fn (string ...$chaves): string => route('public.catalogo', collect($parametros)->except($chaves)->all());
        $parametrosDaNavegacao = collect($parametros)->except(['decada', 'page'])->all();
        $linkDaDecada = fn (?string $decada): string => route('public.catalogo', array_filter([
            ...$parametrosDaNavegacao,
            'decada' => $decada,
        ], static fn (string|int|null $valor): bool => $valor !== null && $valor !== ''));
        // Chips só dos filtros já aplicados (na URL), nunca de seleções não confirmadas no drawer.
        $filtrosAplicados = array_filter([
            'decada' => $filtros->decada !== null ? 'Década de '.$filtros->decada : null,
            'categoria' => $filtros->categoriaId !== null ? $categorias->firstWhere('id', $filtros->categoriaId)?->titulo : null,
            'assunto' => $filtros->assuntoId !== null ? $assuntos->firstWhere('id', $filtros->assuntoId)?->titulo : null,
        ]);
        $total = $fotografias->total();
        $parametrosDetalhe = [...$parametros, 'page' => $fotografias->currentPage()];
        $select = 'w-full min-w-0 rounded-md border border-mist-400 bg-white px-3 py-[0.7rem] text-[0.9rem] text-ink-950 focus:border-institutional focus:outline-3 focus:outline-institutional/20';
        $label = 'grid gap-2 text-[0.8rem] font-semibold text-ink-800';
    @endphp

    <section class="bg-white">
        <x-public.container class="pt-10 pb-14 md:pt-12">
            <x-public.eyebrow>Acervo fotográfico</x-public.eyebrow>
            <x-public.heading as="h1" class="mt-3 text-[clamp(2rem,4.5vw,3.25rem)] leading-[1.08] font-medium tracking-[-0.01em]">Explore o acervo</x-public.heading>
            <p class="mt-3 max-w-160 text-[1rem] leading-[1.65] text-ink-600">Descubra fotografias, lugares e momentos preservados na memória de Umuarama.</p>

            <div x-data="drawer" class="mt-7">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                    <x-public.search-form :catalog-query="$parametros" :search-error="$searchError ?? null" class="flex-1" />
                    <button
                        type="button"
                        @click="open()"
                        aria-haspopup="dialog"
                        aria-controls="catalog-filters"
                        class="inline-flex h-12 flex-none cursor-pointer items-center justify-center gap-2 rounded-md border border-mist-300 bg-white px-5 text-[0.85rem] font-semibold text-institutional transition-colors hover:border-institutional hover:bg-mist-50"
                    >
                        <x-lucide-sliders-horizontal class="size-4" aria-hidden="true" />
                        Filtros
                        @if ($filtrosAplicados)
                            <span class="grid min-w-5 place-items-center rounded-full bg-institutional px-1.5 text-[0.7rem] leading-5 text-white">{{ count($filtrosAplicados) }}<span class="sr-only"> aplicados</span></span>
                        @endif
                    </button>
                </div>

                @if ($filtrosAplicados)
                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <span class="sr-only">Filtros aplicados:</span>
                        @foreach ($filtrosAplicados as $chave => $rotulo)
                            <a href="{{ $semParametro($chave) }}" class="inline-flex items-center gap-1.5 rounded-full border border-institutional-muted bg-institutional-soft py-1 pr-2 pl-3 text-[0.8rem] text-institutional transition-colors hover:border-institutional">
                                {{ $rotulo }}
                                <x-lucide-x class="size-3.5" aria-hidden="true" />
                                <span class="sr-only">(remover filtro)</span>
                            </a>
                        @endforeach
                        <a href="{{ route('public.catalogo', $filtros->parametrosDaPesquisa()) }}" class="ml-1 text-[0.8rem] font-semibold text-institutional underline decoration-mist-500 underline-offset-4 hover:decoration-institutional">Limpar todos</a>
                    </div>
                @endif

                @if ($decadas !== [])
                    <nav class="mt-7" aria-label="Navegação por décadas">
                        <h2 class="text-[0.8rem] font-semibold tracking-[0.04em] text-ink-700 uppercase">Navegue por décadas</h2>
                        <div class="mt-3 -mx-1 overflow-x-auto px-1 pb-1">
                            <ul role="list" class="flex min-w-max gap-2">
                                <li>
                                    <a
                                        href="{{ $linkDaDecada(null) }}"
                                        @if ($filtros->decada === null) aria-current="page" @endif
                                        class="inline-flex min-h-10 items-center rounded-full border px-4 text-[0.82rem] font-semibold transition-colors {{ $filtros->decada === null ? 'border-institutional bg-institutional text-white' : 'border-mist-300 bg-white text-institutional hover:border-institutional hover:bg-institutional-soft' }}"
                                    >Todas</a>
                                </li>
                                @foreach ($decadas as $decada)
                                    <li>
                                        <a
                                            href="{{ $linkDaDecada($decada) }}"
                                            @if ($filtros->decada === $decada) aria-current="page" @endif
                                            class="inline-flex min-h-10 items-center rounded-full border px-4 text-[0.82rem] font-semibold transition-colors {{ $filtros->decada === $decada ? 'border-institutional bg-institutional text-white' : 'border-mist-300 bg-white text-institutional hover:border-institutional hover:bg-institutional-soft' }}"
                                        >{{ $decada }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </nav>
                @endif

                <dialog
                    id="catalog-filters"
                    x-ref="drawer"
                    aria-labelledby="catalog-filters-title"
                    @close="restore()"
                    @click.self="close()"
                    class="fixed inset-y-0 right-0 left-auto m-0 h-dvh max-h-none w-full max-w-none bg-white p-0 text-ink-950 shadow-[0_0_48px_rgb(17_24_22/0.25)] backdrop:bg-night-950/45 open:flex open:flex-col sm:w-[min(24rem,100vw)]"
                >
                    <form method="GET" action="{{ route('public.catalogo') }}" class="flex min-h-0 flex-1 flex-col">
                        <div class="flex items-center justify-between gap-4 border-b border-mist-200 px-5 py-4">
                            <h2 id="catalog-filters-title" class="font-serif text-[1.35rem] font-medium text-institutional">Filtrar fotografias</h2>
                            <button type="button" @click="close()" class="grid size-10 cursor-pointer place-items-center rounded-full text-ink-600 hover:bg-mist-150 hover:text-ink-950" aria-label="Fechar filtros">
                                <x-lucide-x class="size-5" aria-hidden="true" />
                            </button>
                        </div>

                        <div class="grid min-h-0 flex-1 content-start gap-5 overflow-y-auto px-5 py-6">
                            @if ($filtros->termo !== '')
                                <input type="hidden" name="q" value="{{ $filtros->termo }}">
                            @endif

                            <label class="{{ $label }}">
                                <span>Década histórica</span>
                                <select name="decada" class="{{ $select }}">
                                    <option value="">Todas as décadas</option>
                                    @foreach ($decadas as $decada)
                                        <option value="{{ $decada }}" @selected($filtros->decada === $decada)>Década de {{ $decada }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="{{ $label }}">
                                <span>Categoria</span>
                                <select name="categoria" class="{{ $select }}">
                                    <option value="">Todas as categorias</option>
                                    @foreach ($categorias as $categoria)
                                        <option value="{{ $categoria->id }}" @selected($filtros->categoriaId === $categoria->id)>{{ $categoria->titulo }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="{{ $label }}">
                                <span>Assunto</span>
                                <select name="assunto" class="{{ $select }}">
                                    <option value="">Todos os assuntos</option>
                                    @foreach ($assuntos as $assunto)
                                        <option value="{{ $assunto->id }}" @selected($filtros->assuntoId === $assunto->id)>{{ $assunto->titulo }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>

                        <div class="flex items-center justify-between gap-3 border-t border-mist-200 px-5 py-4">
                            <a href="{{ route('public.catalogo', $filtros->parametrosDaPesquisa()) }}" class="text-[0.85rem] font-semibold text-institutional underline decoration-mist-500 underline-offset-4 hover:decoration-institutional">Limpar filtros</a>
                            <x-public.button type="submit">Aplicar filtros</x-public.button>
                        </div>
                    </form>
                </dialog>
            </div>

            @if ($fotografias->isNotEmpty() || $filtros->termo !== '')
                <div class="mt-10 flex flex-wrap items-baseline justify-between gap-x-6 gap-y-2 border-b border-mist-200 pb-3 text-[0.85rem] text-ink-600">
                    <p><strong class="font-semibold text-ink-950">{{ number_format($total, 0, ',', '.') }}</strong> {{ $total === 1 ? 'fotografia encontrada' : 'fotografias encontradas' }}</p>
                    @if ($filtros->termo !== '')
                        <p>
                            <span>Resultados para “{{ $filtros->termo }}”.</span>
                            <a href="{{ $semParametro('q') }}" class="ml-1 font-semibold text-institutional underline decoration-mist-500 underline-offset-4 hover:decoration-institutional">Limpar pesquisa</a>
                        </p>
                    @endif
                </div>
            @endif

            @if ($fotografias->isEmpty())
                <div class="mt-10 grid max-w-xl justify-items-start gap-3">
                    <x-lucide-search-x class="size-8 text-mist-600" aria-hidden="true" />
                    @if ($filtros->possuiCriterios())
                        <h2 class="font-serif text-[1.5rem] font-medium text-institutional">Nenhuma fotografia encontrada para esta busca</h2>
                        <p class="text-[0.95rem] leading-[1.65] text-ink-600">
                            @if ($filtros->termo !== '' && $filtros->possuiClassificacoes())
                                Não encontramos resultados para “{{ $filtros->termo }}” com os critérios selecionados.
                            @elseif ($filtros->termo !== '')
                                Não encontramos resultados para “{{ $filtros->termo }}”.
                            @else
                                Nenhuma fotografia corresponde aos filtros selecionados.
                            @endif
                            Tente outros termos ou ajuste os filtros.
                        </p>
                        <x-public.button :href="route('public.catalogo')" class="mt-2">Limpar pesquisa e filtros</x-public.button>
                    @else
                        <h2 class="font-serif text-[1.5rem] font-medium text-institutional">Nenhuma fotografia pública disponível</h2>
                        <p class="text-[0.95rem] leading-[1.65] text-ink-600">Novos registros aparecerão aqui conforme forem revisados e publicados.</p>
                    @endif
                </div>
            @else
                {{-- Masonry com CSS columns: cada foto mantém a proporção; a ordem (e o Tab) desce cada coluna. --}}
                <ul role="list" class="mt-6 columns-1 gap-4 sm:columns-2 lg:columns-3 lg:gap-5" aria-label="Fotografias do acervo">
                    @foreach ($fotografias as $fotografia)
                        <li class="mb-4 break-inside-avoid lg:mb-5">
                            <x-public.gallery-item :fotografia="$fotografia" :href="route('public.fotografias.show', ['fotografia' => $fotografia, ...$parametrosDetalhe])" />
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($fotografias->hasPages())
                <nav class="mt-12" aria-label="Paginação do catálogo">
                    {{ $fotografias->onEachSide(1)->links() }}
                </nav>
            @endif
        </x-public.container>
    </section>
</x-layouts.public>
