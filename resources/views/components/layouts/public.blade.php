@php
    $pesquisaAtual = request()->query('q');
    $pesquisaAtual = is_string($pesquisaAtual) ? trim($pesquisaAtual) : '';
@endphp

<x-layouts.app :title="$title ?? 'Memórias Vivas'">
    <div class="public-shell">
        <header class="public-header">
            <div class="public-container public-header__inner">
                <a href="{{ route('public.home') }}" class="public-brand">
                    <img
                        src="{{ asset('images/memorias-vivas-logo.jpg') }}"
                        alt="Memórias Vivas de Umuarama"
                        width="48"
                        height="48"
                        class="public-brand__logo"
                    >
                    <span>
                        <strong>Memórias Vivas</strong>
                        <small>Acervo de Umuarama</small>
                    </span>
                </a>

                <div class="public-header__tools">
                    <form method="GET" action="{{ route('public.catalogo') }}" class="public-search" role="search">
                        <label class="sr-only" for="public-search">Buscar no catálogo</label>
                        <input id="public-search" name="q" type="search" value="{{ $pesquisaAtual }}" placeholder="Buscar no catálogo" autocomplete="off">
                        <button type="submit">Buscar</button>
                    </form>

                    <details class="public-menu">
                        <summary>Menu</summary>
                        <nav class="public-nav public-nav--menu" aria-label="Navegação pública">
                            <a href="{{ route('public.home') }}" @if (request()->routeIs('public.home')) aria-current="page" @endif>Início</a>
                            <a href="{{ route('public.home') }}#sobre">Sobre o acervo</a>
                            <a href="{{ route('public.catalogo') }}" @if (request()->routeIs('public.catalogo')) aria-current="page" @endif>Catálogo</a>
                            <a href="{{ route('login') }}">Área administrativa</a>
                        </nav>
                    </details>

                    <nav class="public-nav public-nav--desktop" aria-label="Navegação pública">
                        <a href="{{ route('public.home') }}" @if (request()->routeIs('public.home')) aria-current="page" @endif>Início</a>
                        <a href="{{ route('public.home') }}#sobre">Sobre o acervo</a>
                        <a href="{{ route('public.catalogo') }}" @if (request()->routeIs('public.catalogo')) aria-current="page" @endif>Catálogo</a>
                        <a href="{{ route('login') }}">Área administrativa</a>
                    </nav>
                </div>
            </div>
        </header>

        <main>{{ $slot }}</main>

        <footer class="public-footer">
            <div class="public-container public-footer__inner">
                <span>Memórias Vivas de Umuarama</span>
                <span>Preservar, organizar e compartilhar memórias.</span>
            </div>
        </footer>
    </div>
</x-layouts.app>
