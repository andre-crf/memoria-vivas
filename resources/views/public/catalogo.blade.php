<x-layouts.public title="Catálogo | Memórias Vivas" :catalog-query="$filtros->parametros()" :search-error="$searchError ?? null">
    @php
        $select = 'w-full min-w-0 rounded-md border border-mist-400 bg-white px-3 py-[0.7rem] font-medium text-ink-950 focus:border-institutional focus:outline-3 focus:outline-institutional/20';
    @endphp

    <x-public.section>
        <x-public.container>
            <x-public.eyebrow>Catálogo</x-public.eyebrow>
            <x-public.heading as="h1" class="mt-[0.8rem] max-w-[42rem] text-[length:clamp(2rem,5vw,3.5rem)] leading-[1.1]">Pesquisar no acervo</x-public.heading>
            @if ($filtros->termo !== '')
                <p class="mt-4 max-w-[42rem] text-[1rem] leading-[1.7] text-ink-600">Resultados para “{{ $filtros->termo }}”.</p>
            @else
                <p class="mt-4 max-w-[42rem] text-[1rem] leading-[1.7] text-ink-600">Explore as fotografias revisadas e disponibilizadas pelo acervo.</p>
            @endif

            <form method="GET" action="{{ route('public.catalogo') }}" class="mt-8 grid gap-4 rounded-lg border border-mist-200 bg-mist-50 p-5">
                @if ($filtros->termo !== '')
                    <input type="hidden" name="q" value="{{ $filtros->termo }}">
                @endif

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <label class="grid gap-[0.4rem] text-[0.78rem] font-[750] text-institutional">
                        <span>Década histórica</span>
                        <select name="decada" class="{{ $select }}">
                            <option value="">Todas as décadas</option>
                            @foreach ($decadas as $decada)
                                <option value="{{ $decada }}" @selected($filtros->decada === $decada)>Década de {{ $decada }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="grid gap-[0.4rem] text-[0.78rem] font-[750] text-institutional">
                        <span>Categoria</span>
                        <select name="categoria" class="{{ $select }}">
                            <option value="">Todas as categorias</option>
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria->id }}" @selected($filtros->categoriaId === $categoria->id)>{{ $categoria->titulo }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="grid gap-[0.4rem] text-[0.78rem] font-[750] text-institutional">
                        <span>Assunto</span>
                        <select name="assunto" class="{{ $select }}">
                            <option value="">Todos os assuntos</option>
                            @foreach ($assuntos as $assunto)
                                <option value="{{ $assunto->id }}" @selected($filtros->assuntoId === $assunto->id)>{{ $assunto->titulo }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <div class="flex flex-wrap items-center gap-4">
                    <x-public.button type="submit">Aplicar filtros</x-public.button>
                    @if ($filtros->possuiClassificacoes())
                        <x-public.back-link :href="route('public.catalogo', $filtros->parametrosDaPesquisa())">Limpar filtros</x-public.back-link>
                    @endif
                </div>
            </form>

            @if ($fotografias->isEmpty())
                <x-public.empty-state class="mt-8" :title="$filtros->possuiCriterios() ? 'Nenhuma fotografia encontrada para esta busca' : 'Nenhuma fotografia pública disponível'">
                    @if ($filtros->possuiCriterios())
                        @if ($filtros->termo !== '' && $filtros->possuiClassificacoes())
                            <span>Não encontramos resultados para “{{ $filtros->termo }}” com os critérios selecionados.</span>
                        @elseif ($filtros->termo !== '')
                            <span>Não encontramos resultados para “{{ $filtros->termo }}”.</span>
                        @else
                            <span>Nenhuma fotografia corresponde aos filtros selecionados.</span>
                        @endif
                        <x-public.back-link :href="route('public.catalogo')">Limpar pesquisa e filtros</x-public.back-link>
                    @else
                        <span>Novos registros aparecerão aqui conforme forem revisados e publicados.</span>
                    @endif
                </x-public.empty-state>
            @else
                <div class="mt-10 grid grid-cols-1 gap-4 md:grid-cols-2 md:gap-6 lg:grid-cols-3" aria-label="Fotografias do acervo">
                    @php
                        $parametrosDetalhe = [
                            ...$filtros->parametros(),
                            'page' => $fotografias->currentPage(),
                        ];
                    @endphp
                    @foreach ($fotografias as $fotografia)
                        @php
                            $imagem = $fotografia->arquivos->firstWhere('versao_arquivo', 'thumbnail')
                                ?? $fotografia->arquivos->firstWhere('versao_arquivo', 'medium')
                                ?? $fotografia->arquivos->firstWhere('versao_arquivo', 'large');
                        @endphp

                        <article class="min-w-0 overflow-hidden rounded-[0.6rem] border border-mist-200 bg-white shadow-[0_10px_28px_rgb(24_42_63/0.07)] transition-[translate,box-shadow] duration-160 ease-[ease] hover:-translate-y-0.5 hover:shadow-[0_16px_34px_rgb(24_42_63/0.12)]">
                            <a class="block h-full focus-visible:outline-institutional/45" href="{{ route('public.fotografias.show', ['fotografia' => $fotografia, ...$parametrosDetalhe]) }}" aria-label="Ver detalhes de {{ $fotografia->titulo }}">
                                <x-public.imagem
                                    :arquivo="$imagem"
                                    :alt="$fotografia->titulo"
                                    class="aspect-4/3"
                                />
                                <span class="grid gap-[0.4rem] p-4">
                                    <strong class="font-serif text-[1.05rem] leading-[1.3] wrap-anywhere text-institutional">{{ $fotografia->titulo }}</strong>
                                    <span class="text-[0.8rem] text-ink-500">{{ $fotografia->dataHistorica()->label() }}</span>
                                </span>
                            </a>
                        </article>
                    @endforeach
                </div>
            @endif

            @if ($fotografias->hasPages())
                <nav class="mt-10" aria-label="Paginação do catálogo">
                    {{ $fotografias->onEachSide(1)->links() }}
                </nav>
            @endif
        </x-public.container>
    </x-public.section>
</x-layouts.public>
