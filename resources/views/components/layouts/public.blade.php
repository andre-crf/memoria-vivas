<x-layouts.app :title="$title ?? 'Memórias Vivas'">
    <div class="public-shell">
        <header class="public-header">
            <div class="public-container public-header__inner">
                <a href="{{ route('public.home') }}" class="public-brand">
                    <span class="public-brand__mark" aria-hidden="true">MV</span>
                    <span>
                        <strong>Memórias Vivas</strong>
                        <small>Acervo de Umuarama</small>
                    </span>
                </a>

                <nav class="public-nav" aria-label="Navegação pública">
                    <a href="{{ route('public.home') }}" aria-current="page">Início</a>
                    <a href="#sobre">Sobre o acervo</a>
                    <a href="{{ route('login') }}">Área administrativa</a>
                </nav>
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
