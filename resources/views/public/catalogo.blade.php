<x-layouts.public title="Catálogo | Memórias Vivas" :search-error="$searchError ?? null">
    <section class="public-section public-catalog">
        <div class="public-container">
            <p class="public-eyebrow">Catálogo</p>
            <h1>Pesquisar no acervo</h1>
            @if ($termo !== '')
                <p class="public-section__intro">Busca por “{{ $termo }}”.</p>
            @else
                <p class="public-section__intro">Explore as fotografias e conheça as histórias de Umuarama.</p>
            @endif
            @if (! $searchError)
                @if ($fotografias->isNotEmpty())
                    <p class="public-catalog__count" role="status">{{ $fotografias->total() }} {{ $fotografias->total() === 1 ? 'fotografia encontrada' : 'fotografias encontradas' }}</p>
                    <div class="public-catalog__grid">
                        @foreach ($fotografias as $fotografia)
                            @php($imagem = $fotografia->arquivos->firstWhere('versao_arquivo', 'thumbnail') ?? $fotografia->arquivos->firstWhere('versao_arquivo', 'medium') ?? $fotografia->arquivos->firstWhere('versao_arquivo', 'large'))
                            <article class="public-photo">
                                <a href="{{ route('public.fotografias.show', $fotografia->id) }}">
                                    <div class="public-photo__media">
                                        <img src="{{ route('publico.imagens.show', $imagem) }}" alt="{{ $fotografia->titulo }}" width="640" height="480" loading="lazy" onerror="this.hidden = true">
                                        <span>Imagem indisponível</span>
                                    </div>
                                    <div class="public-photo__body">
                                        <h2>{{ $fotografia->titulo }}</h2>
                                        <p>{{ $fotografia->dataHistorica()->label() }}</p>
                                        @if ($fotografia->local_epoca || $fotografia->local_atual)
                                            <p>{{ $fotografia->local_epoca ?: $fotografia->local_atual }}</p>
                                        @endif
                                    </div>
                                </a>
                            </article>
                        @endforeach
                    </div>
                    <div class="public-catalog__pagination">{{ $fotografias->links() }}</div>
                @else
                    <div class="public-empty-state" role="status">
                        <strong>{{ $termo !== '' ? 'Nenhuma fotografia encontrada para esta busca' : 'Nenhum item público disponível ainda' }}</strong>
                        <span>{{ $termo !== '' ? 'Tente outro título, local ou palavra da legenda.' : 'As fotografias aparecerão aqui conforme forem publicadas.' }}</span>
                        @if ($termo !== '')
                            <a href="{{ route('public.catalogo') }}" class="public-text-link">Limpar pesquisa</a>
                        @endif
                    </div>
                @endif
            @endif
        </div>
    </section>
</x-layouts.public>
