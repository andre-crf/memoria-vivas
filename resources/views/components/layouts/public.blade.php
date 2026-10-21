@props([
    'title' => 'Memórias Vivas',
    'catalogQuery' => [],
    'searchError' => null,
])

@php
    $pesquisaAtual = (string) ($catalogQuery['q'] ?? '');
    $navigation = [
        ['label' => 'Início', 'href' => route('public.home'), 'active' => request()->routeIs('public.home')],
        ['label' => 'Sobre o acervo', 'href' => route('public.home') . '#sobre', 'active' => false],
        ['label' => 'Sobre nós', 'href' => route('public.sobre'), 'active' => request()->routeIs('public.sobre')],
        [
            'label' => 'Como colaborar',
            'href' => route('public.colaborar'),
            'active' => request()->routeIs('public.colaborar'),
        ],
        ['label' => 'Contato', 'href' => route('public.contato'), 'active' => request()->routeIs('public.contato')],
        [
            'label' => 'Catálogo',
            'href' => route('public.catalogo'),
            'active' => request()->routeIs('public.catalogo', 'public.fotografias.*'),
        ],
        ['label' => 'Área administrativa', 'href' => route('login'), 'active' => false],
    ];
    $navClasses = 'text-[0.82rem] font-[650] text-ink-600';
    $footerLink =
        'wrap-anywhere hover:underline hover:underline-offset-4 focus-visible:outline-white focus-visible:outline-offset-5';
@endphp

<x-layouts.app :title="$title">
    <div class="public-shell min-h-screen bg-paper text-ink-950">
        <a class="fixed top-3 left-3 z-50 translate-y-[-180%] rounded-md bg-institutional px-[0.9rem] py-[0.7rem] text-[0.8rem] font-[750] text-white transition-transform duration-140 ease-[ease] focus:translate-y-0 motion-reduce:transition-none"
            href="#public-main">Pular para o conteúdo</a>

        <header class="relative z-20 border-b border-mist-200 bg-white" aria-label="Cabeçalho público">
            <x-public.container
                class="flex min-h-20 flex-col items-start justify-center gap-6 py-4 lg:flex-row lg:items-center lg:justify-between lg:py-0">
                <a href="{{ route('public.home') }}" class="inline-flex min-w-0 items-center gap-3 text-institutional"
                    data-testid="public-brand">
                    <img src="{{ asset('images/memorias-vivas-logo.png') }}" alt="Memórias Vivas de Umuarama"
                        width="48" height="48"
                        class="size-13 flex-none rounded-full border-2 border-mist-200 object-cover">
                    <span>
                        <strong class="block font-serif text-[1.1rem] leading-[1.15]">Memórias Vivas</strong>
                        <small
                            class="mt-[0.18rem] block text-[0.68rem] font-bold tracking-[0.08em] text-ink-500 uppercase">Acervo
                            de Umuarama</small>
                    </span>
                </a>

                <div
                    class="flex w-full min-w-0 flex-wrap items-stretch justify-start gap-3 md:flex-nowrap md:items-center md:justify-between md:gap-5 lg:w-auto lg:justify-end">
                    <div class="min-w-0 flex-[1_1_15rem] md:flex-initial">
                        <form method="GET" action="{{ route('public.catalogo') }}"
                            class="flex w-full min-w-0 items-center rounded-md border border-mist-300 bg-white focus-within:border-institutional focus-within:shadow-[0_0_0_3px_rgb(23_62_99/0.14)] md:min-w-[min(18rem,34vw)]"
                            role="search">
                            <label class="sr-only" for="public-search">Buscar no catálogo</label>
                            <input id="public-search" name="q" type="search" value="{{ $pesquisaAtual }}"
                                maxlength="200"
                                @if ($searchError) aria-invalid="true" aria-describedby="catalog-search-error" @endif
                                placeholder="Buscar no catálogo" autocomplete="off"
                                class="min-w-0 flex-1 bg-transparent px-[0.7rem] py-[0.58rem] text-[0.78rem] text-ink-950 outline-none">
                            @foreach (['decada', 'categoria', 'assunto'] as $parametro)
                                @if (isset($catalogQuery[$parametro]))
                                    <input type="hidden" name="{{ $parametro }}" value="{{ $catalogQuery[$parametro] }}">
                                @endif
                            @endforeach
                            <button type="submit" aria-label="Buscar no catálogo"
                                class="border-l border-mist-300 px-[0.7rem] py-[0.58rem] text-[0.75rem] font-[750] text-institutional">Buscar</button>
                        </form>
                        @if ($searchError)
                            <p id="catalog-search-error"
                                class="mt-2 max-w-88 text-[0.8rem] leading-normal text-error-strong" role="alert">
                                {{ $searchError }}</p>
                        @endif
                    </div>

                    <details class="relative md:hidden" data-testid="public-menu">
                        <summary aria-label="Abrir navegação pública"
                            class="cursor-pointer list-none rounded-md border border-mist-500 px-3 py-[0.55rem] text-[0.78rem] font-bold text-institutional focus-visible:outline-offset-2 [&::-webkit-details-marker]:hidden">
                            Menu</summary>
                        <nav class="absolute top-[calc(100%+0.5rem)] right-0 z-10 grid min-w-52 justify-start gap-x-4 gap-y-3 rounded-md border border-mist-200 bg-white p-[0.35rem] shadow-[0_14px_30px_rgb(24_42_63/0.16)] {{ $navClasses }}"
                            aria-label="Navegação pública">
                            @foreach ($navigation as $item)
                                <x-public.nav-link :href="$item['href']" :active="$item['active']"
                                    variant="menu">{{ $item['label'] }}</x-public.nav-link>
                            @endforeach
                        </nav>
                    </details>

                    <nav class="hidden flex-wrap justify-start gap-5 md:flex lg:justify-end {{ $navClasses }}"
                        aria-label="Navegação pública">
                        @foreach ($navigation as $item)
                            <x-public.nav-link :href="$item['href']"
                                :active="$item['active']">{{ $item['label'] }}</x-public.nav-link>
                        @endforeach
                    </nav>
                </div>
            </x-public.container>
        </header>

        <main id="public-main" tabindex="-1">{{ $slot }}</main>

        <footer class="relative isolate overflow-hidden bg-institutional-light text-white">
            <x-public.container
                class="grid min-h-[clamp(14rem,18vw,18rem)] grid-cols-1 gap-8 py-8 sm:grid-cols-2 sm:pb-12 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,0.8fr)_minmax(0,1fr)] lg:gap-12">
                <div class="sm:col-span-full lg:col-auto">
                    <a href="{{ route('public.home') }}" class="inline-flex items-center gap-4 {{ $footerLink }}">
                        <img src="{{ asset('images/memorias-vivas-logo.png') }}" alt="" width="64"
                            height="64" class="size-16 flex-none rounded-full border-2 border-white object-cover">
                        <span><strong class="block font-serif text-[1.3rem] leading-[1.2] sm:text-[1.5rem]">Memórias
                                Vivas</strong><small class="mt-[0.3rem] block text-[0.75rem]">Acervo de
                                Umuarama</small></span>
                    </a>
                    <p class="mt-5 text-[0.88rem] leading-[1.8]">Preservar, organizar e compartilhar memórias.<br>As
                        histórias da nossa cidade, mais perto de você.</p>
                    <a href="https://www.instagram.com/memorias.vivas.de.umu/" target="_blank" rel="noopener" class="mt-4 inline-flex items-center gap-2 text-[0.88rem] font-semibold {{ $footerLink }}">
                        <x-icons.instagram class="size-5 flex-none" aria-hidden="true" />
                        <span><span class="sr-only">Instagram: </span>@memorias.vivas.de.umu<span class="sr-only"> (abre em nova aba)</span></span>
                    </a>
                </div>
                <nav class="grid content-start justify-items-start gap-[0.8rem]" aria-label="Navegação do rodapé">
                    <h2 class="mb-[0.4rem] text-[0.92rem] font-[750]">Conheça o acervo</h2>
                    <a href="{{ route('public.home') }}"
                        class="text-[0.85rem] leading-normal {{ $footerLink }}">Início</a>
                    <a href="{{ route('public.catalogo') }}"
                        class="text-[0.85rem] leading-normal {{ $footerLink }}">Catálogo de fotografias</a>
                    <a href="{{ route('public.home') }}#sobre"
                        class="text-[0.85rem] leading-normal {{ $footerLink }}">Sobre o projeto</a>
                </nav>
                <div>
                    <h2 class="mb-[0.4rem] text-[0.92rem] font-[750]">Nossa memória, nossa cidade</h2>
                    <p class="my-4 text-[0.88rem]">Umuarama · Paraná</p>
                    <a href="https://www.umuarama.pr.gov.br/"
                        class="text-[0.85rem] leading-normal {{ $footerLink }}">Portal da Prefeitura <span
                            aria-hidden="true">&nearr;</span></a>
                </div>
            </x-public.container>
            {{-- A URL vem do asset(): em url() no CSS ela seria resolvida contra o servidor do Vite, que não serve public/. --}}
            <div class="pointer-events-none absolute inset-x-0 top-0 bottom-14 -z-1 bg-size-[100%_auto] bg-bottom bg-no-repeat opacity-12"
                style="background-image: url('{{ asset('images/umuarama-panorama-horizontal.png') }}')"
                aria-hidden="true"></div>
            <div class="border-t border-white/20 bg-institutional">
                <x-public.container
                    class="flex min-h-14 flex-wrap items-center justify-between gap-4 py-4 text-[0.75rem]">
                    <span>&copy; {{ date('Y') }} Memórias Vivas de Umuarama</span>
                    <a href="{{ route('login') }}" class="{{ $footerLink }}">Área administrativa</a>
                </x-public.container>
            </div>
        </footer>
    </div>
</x-layouts.app>
