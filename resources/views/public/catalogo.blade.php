<x-layouts.public title="Catálogo | Memórias Vivas" :catalog-query="$filtros->parametros()">
    <section class="public-section public-catalog">
        <div class="public-container">
            <p class="public-eyebrow">Catálogo</p>
            <h1>Pesquisar no acervo</h1>
            @if ($filtros->termo !== '')
                <p class="public-section__intro">Resultados para “{{ $filtros->termo }}”.</p>
            @else
                <p class="public-section__intro">Explore as fotografias revisadas e disponibilizadas pelo acervo.</p>
            @endif

            <form method="GET" action="{{ route('public.catalogo') }}" class="public-catalog-filters">
                @if ($filtros->termo !== '')
                    <input type="hidden" name="q" value="{{ $filtros->termo }}">
                @endif

                <div class="public-catalog-filters__fields">
                    <label>
                        <span>Década histórica</span>
                        <select name="decada">
                            <option value="">Todas as décadas</option>
                            @foreach ($decadas as $decada)
                                <option value="{{ $decada }}" @selected($filtros->decada === $decada)>Década de {{ $decada }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span>Categoria</span>
                        <select name="categoria">
                            <option value="">Todas as categorias</option>
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria->id }}" @selected($filtros->categoriaId === $categoria->id)>{{ $categoria->titulo }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span>Assunto</span>
                        <select name="assunto">
                            <option value="">Todos os assuntos</option>
                            @foreach ($assuntos as $assunto)
                                <option value="{{ $assunto->id }}" @selected($filtros->assuntoId === $assunto->id)>{{ $assunto->titulo }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <div class="public-catalog-filters__actions">
                    <button type="submit" class="public-button">Aplicar filtros</button>
                    @if ($filtros->possuiClassificacoes())
                        <a href="{{ route('public.catalogo', $filtros->parametrosDaPesquisa()) }}" class="public-back-link">Limpar filtros</a>
                    @endif
                </div>
            </form>

            @if ($fotografias->isEmpty())
                <div class="public-empty-state">
                    @if ($filtros->possuiCriterios())
                        <strong>Nenhuma fotografia encontrada</strong>
                        @if ($filtros->termo !== '' && $filtros->possuiClassificacoes())
                            <span>Não encontramos resultados para “{{ $filtros->termo }}” com os critérios selecionados.</span>
                        @elseif ($filtros->termo !== '')
                            <span>Não encontramos resultados para “{{ $filtros->termo }}”.</span>
                        @else
                            <span>Nenhuma fotografia corresponde aos filtros selecionados.</span>
                        @endif
                        <a href="{{ route('public.catalogo') }}" class="public-back-link">Limpar pesquisa e filtros</a>
                    @else
                        <strong>Nenhuma fotografia pública disponível</strong>
                        <span>Novos registros aparecerão aqui conforme forem revisados e publicados.</span>
                    @endif
                </div>
            @else
                <div class="public-catalog-grid" aria-label="Fotografias do acervo">
                    @php
                        $parametrosDetalhe = [
                            ...$filtros->parametros(),
                            'page' => $fotografias->currentPage(),
                        ];
                    @endphp
                    @foreach ($fotografias as $fotografia)
                        @php
                            $imagem = $fotografia->arquivos->firstWhere('versao_arquivo', 'thumbnail')
                                ?? $fotografia->arquivos->firstWhere('versao_arquivo', 'medium')
                                ?? $fotografia->arquivos->firstWhere('versao_arquivo', 'large');
                        @endphp

                        <article class="public-catalog-card">
                            <a href="{{ route('public.fotografias.show', ['fotografia' => $fotografia, ...$parametrosDetalhe]) }}" aria-label="Ver detalhes de {{ $fotografia->titulo }}">
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
