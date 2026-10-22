@props([
    'title' => 'Memórias Vivas',
    'catalogQuery' => [],
    'searchError' => null,
])

@php
    // A área administrativa fica só no rodapé.
    $navigation = [
        ['label' => 'Início', 'href' => route('public.home'), 'active' => request()->routeIs('public.home')],
        [
            'label' => 'Catálogo',
            'href' => route('public.catalogo'),
            'active' => request()->routeIs('public.catalogo', 'public.fotografias.*'),
        ],
        ['label' => 'Sobre nós', 'href' => route('public.sobre'), 'active' => request()->routeIs('public.sobre')],
        [
            'label' => 'Como colaborar',
            'href' => route('public.colaborar'),
            'active' => request()->routeIs('public.colaborar'),
        ],
        ['label' => 'Contato', 'href' => route('public.contato'), 'active' => request()->routeIs('public.contato')],
    ];
    // A lupa leva à busca do catálogo, preservando a pesquisa e os filtros atuais.
    $searchHref = route('public.catalogo', $catalogQuery) . '#busca';
    $iconButton = 'grid size-11 flex-none place-items-center rounded-full text-ink-600 transition-colors hover:bg-mist-150 hover:text-institutional';
    $navClasses = 'text-[0.85rem] font-[650] text-ink-600';
    $footerLink =
        'wrap-anywhere hover:underline hover:underline-offset-4 focus-visible:outline-white focus-visible:outline-offset-5';
@endphp

<x-layouts.app :title="$title">
    <div class="public-shell min-h-screen bg-paper text-ink-950">
        <a class="fixed top-3 left-3 z-50 translate-y-[-180%] rounded-md bg-institutional px-[0.9rem] py-[0.7rem] text-[0.8rem] font-[750] text-white transition-transform duration-140 ease-[ease] focus:translate-y-0 motion-reduce:transition-none"
            href="#public-main">Pular para o conteúdo</a>

        <header class="relative z-20 border-b border-mist-200 bg-white" aria-label="Cabeçalho público" x-data="drawer">
            <x-public.container class="flex h-18 items-center justify-between gap-6">
                <a href="{{ route('public.home') }}" class="inline-flex min-w-0 items-center gap-3 text-institutional" data-testid="public-brand">
                    <img src="{{ asset('images/memorias-vivas-logo.png') }}" alt="Memórias Vivas de Umuarama" width="48" height="48" class="size-11 flex-none rounded-full border-2 border-mist-200 object-cover">
                    <span class="min-w-0">
                        <strong class="block truncate font-serif text-[1.1rem] leading-[1.15] font-medium">Memórias Vivas</strong>
                        <small class="mt-0.5 block truncate text-[0.66rem] font-bold tracking-[0.08em] text-ink-500 uppercase">Acervo de Umuarama</small>
                    </span>
                </a>

                <div class="flex flex-none items-center gap-1 lg:gap-6">
                    <nav class="hidden items-center gap-7 lg:flex {{ $navClasses }}" aria-label="Navegação pública">
                        @foreach ($navigation as $item)
                            <x-public.nav-link :href="$item['href']" :active="$item['active']">{{ $item['label'] }}</x-public.nav-link>
                        @endforeach
                    </nav>

                    <a href="{{ $searchHref }}" class="{{ $iconButton }} lg:size-10" aria-label="Pesquisar no acervo" title="Pesquisar no acervo">
                        <x-lucide-search class="size-5" aria-hidden="true" />
                    </a>

                    <button type="button" @click="open()" class="{{ $iconButton }} cursor-pointer lg:hidden" aria-haspopup="dialog" aria-controls="public-menu" aria-label="Abrir menu">
                        <x-lucide-menu class="size-5" aria-hidden="true" />
                    </button>
                </div>
            </x-public.container>

            {{-- Menu do celular e do tablet: abaixo de 1024px os links não cabem na barra. --}}
            <dialog
                id="public-menu"
                x-ref="drawer"
                data-testid="public-menu"
                aria-label="Menu"
                @close="restore()"
                @click.self="close()"
                class="fixed inset-y-0 right-0 left-auto m-0 h-dvh max-h-none w-full max-w-none bg-white p-0 text-ink-950 shadow-[0_0_48px_rgb(17_24_22/0.25)] backdrop:bg-night-950/45 open:flex open:flex-col sm:w-80"
            >
                <div class="flex h-18 flex-none items-center justify-between gap-4 border-b border-mist-200 px-5">
                    <span class="font-serif text-[1.1rem] font-medium text-institutional">Memórias Vivas</span>
                    <button type="button" @click="close()" class="{{ $iconButton }} cursor-pointer" aria-label="Fechar menu">
                        <x-lucide-x class="size-5" aria-hidden="true" />
                    </button>
                </div>
                <nav class="grid content-start gap-1 overflow-y-auto p-3 text-[0.95rem] font-[650] text-ink-700" aria-label="Navegação pública">
                    @foreach ($navigation as $item)
                        <x-public.nav-link :href="$item['href']" :active="$item['active']" variant="menu" class="flex min-h-11 items-center" x-on:click="close()">{{ $item['label'] }}</x-public.nav-link>
                    @endforeach
                </nav>
            </dialog>
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
                    <hr class="mt-4 w-full max-w-80 border-white/25" aria-hidden="true">
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
