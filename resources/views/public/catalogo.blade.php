<x-layouts.public title="Catálogo | Memórias Vivas">
    <section class="public-section public-catalog">
        <div class="public-container">
            <p class="public-eyebrow">Catálogo</p>
            <h1>Pesquisar no acervo</h1>
            @if ($termo !== '')
                <p class="public-section__intro">Busca por “{{ $termo }}”. Os registros públicos serão disponibilizados nesta área.</p>
            @else
                <p class="public-section__intro">Os registros públicos do acervo serão disponibilizados nesta área conforme forem revisados e publicados.</p>
            @endif
            <div class="public-empty-state">
                <strong>Nenhum item público disponível ainda</strong>
                <span>O catálogo está sendo preparado pela equipe do projeto.</span>
            </div>
        </div>
    </section>
</x-layouts.public>
