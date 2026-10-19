@props(['title' => 'Memórias Vivas', 'searchError' => null])

<x-layouts.app :title="$title ?? 'Memórias Vivas'">
    <div class="public-shell">
        <a class="public-skip-link" href="#public-main">Pular para o conteúdo</a>

        <header class="public-header" aria-label="Cabeçalho público">
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
                    <div class="public-search-group">
                        <form method="GET" action="{{ route('public.catalogo') }}" class="public-search" role="search">
                            <label class="sr-only" for="public-search">Buscar no catálogo</label>
                            @php($searchValue = request()->query('q', ''))
                            <input id="public-search" name="q" type="search" value="{{ is_string($searchValue) ? $searchValue : '' }}" maxlength="200" @if ($searchError) aria-invalid="true" aria-describedby="catalog-search-error" @endif placeholder="Buscar no catálogo" autocomplete="off">
                            <button type="submit">Buscar</button>
                        </form>
                        @if ($searchError)
                            <p id="catalog-search-error" class="public-search-error" role="alert">{{ $searchError }}</p>
                        @endif
                    </div>

                    <details class="public-menu">
                        <summary>Menu</summary>
                        <nav class="public-nav public-nav--menu" aria-label="Navegação pública">
                            <a href="{{ route('public.home') }}" @if (request()->routeIs('public.home')) aria-current="page" @endif>Início</a>
                            <a href="{{ route('public.home') }}#sobre">Sobre o acervo</a>
                            <a href="{{ route('public.catalogo') }}" @if (request()->routeIs('public.catalogo', 'public.fotografias.*')) aria-current="page" @endif>Catálogo</a>
                            <a href="{{ route('login') }}">Área administrativa</a>
                        </nav>
                    </details>

                    <nav class="public-nav public-nav--desktop" aria-label="Navegação pública">
                        <a href="{{ route('public.home') }}" @if (request()->routeIs('public.home')) aria-current="page" @endif>Início</a>
                        <a href="{{ route('public.home') }}#sobre">Sobre o acervo</a>
                        <a href="{{ route('public.catalogo') }}" @if (request()->routeIs('public.catalogo', 'public.fotografias.*')) aria-current="page" @endif>Catálogo</a>
                        <a href="{{ route('login') }}">Área administrativa</a>
                    </nav>
                </div>
            </div>
        </header>

        <main id="public-main" tabindex="-1">{{ $slot }}</main>

        <footer class="public-footer">
            <div class="public-container public-footer__content">
                <div class="public-footer__identity">
                    <a href="{{ route('public.home') }}" class="public-footer__brand">
                        <img src="{{ asset('images/memorias-vivas-logo.jpg') }}" alt="" width="64" height="64">
                        <span><strong>Memórias Vivas</strong><small>Acervo de Umuarama</small></span>
                    </a>
                    <p>Preservar, organizar e compartilhar memórias.<br>As histórias da nossa cidade, mais perto de você.</p>
                </div>
                <nav class="public-footer__links" aria-label="Navegação do rodapé">
                    <h2>Conheça o acervo</h2>
                    <a href="{{ route('public.home') }}">Início</a>
                    <a href="{{ route('public.catalogo') }}">Catálogo de fotografias</a>
                    <a href="{{ route('public.home') }}#sobre">Sobre o projeto</a>
                </nav>
                <div class="public-footer__city">
                    <h2>Nossa memória, nossa cidade</h2>
                    <p>Umuarama · Paraná</p>
                    <a href="https://www.umuarama.pr.gov.br/">Portal da Prefeitura <span aria-hidden="true">&nearr;</span></a>
                </div>
            </div>
            <div class="public-footer__illustration" aria-hidden="true"></div>
            <div class="public-footer__bottom">
                <div class="public-container">
                    <span>&copy; {{ date('Y') }} Memórias Vivas de Umuarama</span>
                    <a href="{{ route('login') }}">Área administrativa</a>
                </div>
            </div>
        </footer>
    </div>
</x-layouts.app>
