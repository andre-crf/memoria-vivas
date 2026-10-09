<x-layouts.public title="Catálogo | Memórias Vivas">
    <section class="public-section public-catalog">
        <div class="public-container">
            <p class="public-eyebrow">Catálogo</p>
            <h1>Pesquisar no acervo</h1>
            @if ($termo !== '')
                <p class="public-section__intro">Resultados para “{{ $termo }}”.</p>
            @else
                <p class="public-section__intro">Explore as fotografias revisadas e disponibilizadas pelo acervo.</p>
            @endif

            @if ($fotografias->isEmpty())
                <div class="public-empty-state">
                    @if ($termo !== '')
                        <strong>Nenhuma fotografia encontrada</strong>
                        <span>Não encontramos resultados para “{{ $termo }}”.</span>
                        <a href="{{ route('public.catalogo') }}" class="public-back-link">Limpar pesquisa</a>
                    @else
                        <strong>Nenhuma fotografia pública disponível</strong>
                        <span>Novos registros aparecerão aqui conforme forem revisados e publicados.</span>
                    @endif
                </div>
            @else
                <div class="public-catalog-grid" aria-label="Fotografias do acervo">
                    @foreach ($fotografias as $fotografia)
                        @php
                            $imagem = $fotografia->arquivos->firstWhere('versao_arquivo', 'thumbnail')
                                ?? $fotografia->arquivos->firstWhere('versao_arquivo', 'medium')
                                ?? $fotografia->arquivos->firstWhere('versao_arquivo', 'large');
                        @endphp

                        <article class="public-catalog-card">
                            <a href="{{ route('public.fotografias.show', $fotografia) }}" aria-label="Ver detalhes de {{ $fotografia->titulo }}">
                                <x-public.imagem
                                    :arquivo="$imagem"
                                    :alt="$fotografia->titulo"
                                    class="public-catalog-card__image"
                                />
                                <span class="public-catalog-card__content">
                                    <strong>{{ $fotografia->titulo }}</strong>
                                    <span>{{ $fotografia->dataHistorica()->label() }}</span>
                                </span>
                            </a>
                        </article>
                    @endforeach
                </div>
            @endif

            @if ($fotografias->hasPages())
                <nav class="public-pagination" aria-label="Paginação do catálogo">
                    {{ $fotografias->onEachSide(1)->links() }}
                </nav>
            @endif
        </div>
    </section>
</x-layouts.public>
