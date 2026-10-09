<x-layouts.public title="Catálogo | Memórias Vivas">
    <section class="public-section public-catalog">
        <div class="public-container">
            <p class="public-eyebrow">Catálogo</p>
            <h1>Pesquisar no acervo</h1>
            @if ($termo !== '')
                <p class="public-search-notice">
                    A pesquisa por “{{ $termo }}” será disponibilizada em uma próxima etapa. Por enquanto, veja todas as fotografias públicas.
                </p>
            @else
                <p class="public-section__intro">Explore as fotografias revisadas e disponibilizadas pelo acervo.</p>
            @endif

            @forelse ($fotografias as $fotografia)
                @if ($loop->first)
                    <div class="public-catalog-grid" aria-label="Fotografias do acervo">
                @endif

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

                @if ($loop->last)
                    </div>
                @endif
            @empty
                <div class="public-empty-state">
                    <strong>Nenhuma fotografia pública disponível</strong>
                    <span>Novos registros aparecerão aqui conforme forem revisados e publicados.</span>
                </div>
            @endforelse

            @if ($fotografias->hasPages())
                <nav class="public-pagination" aria-label="Paginação do catálogo">
                    {{ $fotografias->onEachSide(1)->links() }}
                </nav>
            @endif
        </div>
    </section>
</x-layouts.public>
