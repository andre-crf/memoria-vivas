<x-layouts.public title="Página não encontrada | Memórias Vivas">
    <section class="public-not-found" aria-labelledby="not-found-title">
        <div class="public-container public-not-found__inner">
            <p class="public-eyebrow">Erro 404</p>
            <h1 id="not-found-title">Esta página não foi encontrada</h1>
            <p>O endereço pode ter mudado ou não estar disponível. Volte ao início ou explore o catálogo público.</p>
            <div class="public-not-found__actions">
                <a class="public-button" href="{{ route('public.home') }}">Ir para o início</a>
                <a class="public-not-found__link" href="{{ route('public.catalogo') }}">Abrir o catálogo</a>
            </div>
        </div>
    </section>
</x-layouts.public>
