<x-layouts.public :title="$fotografia->titulo.' | Memórias Vivas'">
    @php
        $imagem = $fotografia->arquivos->firstWhere('versao_arquivo', 'large')
            ?? $fotografia->arquivos->firstWhere('versao_arquivo', 'medium')
            ?? $fotografia->arquivos->firstWhere('versao_arquivo', 'thumbnail');
    @endphp

    <section class="public-section public-detail">
        <div class="public-container">
            <a href="{{ $retornoCatalogo }}" class="public-back-link">&larr; Voltar ao catálogo</a>
            <div class="public-detail__grid">
                <div class="public-detail__viewer" x-data="publicPhotoViewer()">
                    <button type="button" class="public-detail__image public-detail__expand" x-ref="trigger" @click="open()" :disabled="!available" aria-haspopup="dialog" aria-label="Ampliar fotografia: {{ $fotografia->titulo }}" title="Ver imagem em tela cheia">
                        <img
                            src="{{ route('publico.imagens.show', $imagem) }}"
                            alt="{{ $fotografia->titulo }}"
                            @if ($imagem->width) width="{{ $imagem->width }}" @endif
                            @if ($imagem->height) height="{{ $imagem->height }}" @endif
                            loading="eager"
                            x-on:error="available = false; $el.hidden = true"
                        >
                        <span x-show="!available">Imagem indisponível</span>
                        <span class="public-detail__expand-icon" x-show="available" aria-hidden="true">&#x26F6;</span>
                    </button>
                    <dialog class="public-image-viewer" x-ref="viewer" aria-labelledby="viewer-title" @close="restore()">
                        <div class="public-image-viewer__toolbar">
                            <h2 id="viewer-title">{{ $fotografia->titulo }}</h2>
                            <div>
                                <button type="button" @click="zoomed = !zoomed" :aria-pressed="zoomed" :aria-label="zoomed ? 'Ajustar imagem à tela' : 'Ver imagem no tamanho original'" :title="zoomed ? 'Ajustar à tela' : 'Tamanho original'"><span aria-hidden="true" x-text="zoomed ? '−' : '+'">+</span></button>
                                <button type="button" x-ref="close" @click="close()" aria-label="Fechar visualização" title="Fechar (Esc)"><span aria-hidden="true">&times;</span></button>
                            </div>
                        </div>
                        <div class="public-image-viewer__viewport" :class="{ 'is-zoomed': zoomed }">
                            <div class="public-image-viewer__surface">
                                <img src="{{ route('publico.imagens.show', $imagem) }}" alt="{{ $fotografia->titulo }}" @if ($imagem->width) width="{{ $imagem->width }}" @endif @if ($imagem->height) height="{{ $imagem->height }}" @endif loading="lazy">
                            </div>
                        </div>
                    </dialog>
                </div>

                <div class="public-detail__content">
                    <p class="public-eyebrow">Fotografia do acervo</p>
                    <h1>{{ $fotografia->titulo }}</h1>
                    <p class="public-detail__date">{{ $fotografia->dataHistorica()->label() }}</p>
                    @if ($fotografia->legenda)
                        <div class="public-detail__caption">{{ $fotografia->legenda }}</div>
                    @endif
                    <dl class="public-detail__metadata">
                        <div><dt>Precisão da data</dt><dd>{{ $fotografia->tipo_data->label() }}</dd></div>
                        @if ($fotografia->local_atual)
                            <div><dt>Local atual</dt><dd>{{ $fotografia->local_atual }}</dd></div>
                        @endif
                        @if ($fotografia->local_epoca)
                            <div><dt>Local na época</dt><dd>{{ $fotografia->local_epoca }}</dd></div>
                        @endif
                        @if ($fotografia->evento)
                            <div><dt>Evento</dt><dd>{{ $fotografia->evento }}</dd></div>
                        @endif
                        @if ($fotografia->cedente)
                            <div><dt>Cedente</dt><dd>{{ $fotografia->cedente }}</dd></div>
                        @endif
                        @if ($fotografia->autor)
                            <div><dt>Autoria</dt><dd>{{ $fotografia->autor->nome }}</dd></div>
                        @endif
                    </dl>
                </div>
            </div>

            @if (
                $fotografia->categorias->isNotEmpty()
                || $fotografia->assuntos->isNotEmpty()
                || $fotografia->palavrasChave->isNotEmpty()
                || $fotografia->pessoas->isNotEmpty()
                || $fotografia->colecoes->isNotEmpty()
                || $fotografia->conjuntosContextuais->isNotEmpty()
            )
                <div class="public-detail__sections">
                    @if ($fotografia->categorias->isNotEmpty() || $fotografia->assuntos->isNotEmpty() || $fotografia->palavrasChave->isNotEmpty())
                        <section class="public-detail__section">
                            <h2>Classificações</h2>
                            <dl class="public-detail__groups">
                                @if ($fotografia->categorias->isNotEmpty())
                                    <div>
                                        <dt>Categorias</dt>
                                        <dd>
                                            <ul>
                                                @foreach ($fotografia->categorias as $categoria)
                                                    <li>{{ $categoria->titulo }}</li>
                                                @endforeach
                                            </ul>
                                        </dd>
                                    </div>
                                @endif
                                @if ($fotografia->assuntos->isNotEmpty())
                                    <div>
                                        <dt>Assuntos</dt>
                                        <dd>
                                            <ul>
                                                @foreach ($fotografia->assuntos as $assunto)
                                                    <li>{{ $assunto->titulo }}</li>
                                                @endforeach
                                            </ul>
                                        </dd>
                                    </div>
                                @endif
                                @if ($fotografia->palavrasChave->isNotEmpty())
                                    <div>
                                        <dt>Palavras-chave</dt>
                                        <dd>
                                            <ul>
                                                @foreach ($fotografia->palavrasChave as $palavraChave)
                                                    <li>{{ $palavraChave->termo }}</li>
                                                @endforeach
                                            </ul>
                                        </dd>
                                    </div>
                                @endif
                            </dl>
                        </section>
                    @endif

                    @if ($fotografia->pessoas->isNotEmpty())
                        <section class="public-detail__section">
                            <h2>Pessoas identificadas</h2>
                            <ul class="public-detail__list">
                                @foreach ($fotografia->pessoas as $pessoa)
                                    <li>{{ $pessoa->nome }}</li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if ($fotografia->colecoes->isNotEmpty() || $fotografia->conjuntosContextuais->isNotEmpty())
                        <section class="public-detail__section">
                            <h2>Coleções e contextos</h2>
                            <dl class="public-detail__groups">
                                @if ($fotografia->colecoes->isNotEmpty())
                                    <div>
                                        <dt>Coleções</dt>
                                        <dd>
                                            <ul>
                                                @foreach ($fotografia->colecoes as $colecao)
                                                    <li>{{ $colecao->titulo }}</li>
                                                @endforeach
                                            </ul>
                                        </dd>
                                    </div>
                                @endif
                                @if ($fotografia->conjuntosContextuais->isNotEmpty())
                                    <div>
                                        <dt>Conjuntos contextuais</dt>
                                        <dd>
                                            <ul>
                                                @foreach ($fotografia->conjuntosContextuais as $conjunto)
                                                    <li>{{ $conjunto->titulo }}</li>
                                                @endforeach
                                            </ul>
                                        </dd>
                                    </div>
                                @endif
                            </dl>
                        </section>
                    @endif
                </div>
            @endif
        </div>
    </section>
</x-layouts.public>
