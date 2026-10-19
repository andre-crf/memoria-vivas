<x-layouts.public :title="$fotografia->titulo.' | Memórias Vivas'">
    @php($imagem = $fotografia->arquivos->firstWhere('versao_arquivo', 'large') ?? $fotografia->arquivos->firstWhere('versao_arquivo', 'medium') ?? $fotografia->arquivos->firstWhere('versao_arquivo', 'thumbnail'))
    <section class="public-section public-photo-detail">
        <div class="public-container">
            <a href="{{ route('public.catalogo') }}" class="public-text-link">&larr; Voltar ao catálogo</a>
            <div class="public-photo-detail__grid">
                <div class="public-photo-detail__viewer" x-data="publicPhotoViewer()">
                    <button type="button" class="public-photo-detail__image public-photo-detail__expand" x-ref="trigger" @click="open()" :disabled="!available" aria-haspopup="dialog" aria-label="Ampliar fotografia: {{ $fotografia->titulo }}" title="Ver imagem em tela cheia">
                        <img src="{{ route('publico.imagens.show', $imagem) }}" alt="{{ $fotografia->titulo }}" width="{{ $imagem->width ?: 1200 }}" height="{{ $imagem->height ?: 800 }}" x-on:error="available = false; $el.hidden = true">
                        <span>Imagem indisponível</span>
                        <span class="public-photo-detail__expand-icon" x-show="available" aria-hidden="true">&#x26F6;</span>
                    </button>
                    <dialog class="public-image-viewer" x-ref="viewer" aria-labelledby="viewer-title" @close="restore()">
                        <div class="public-image-viewer__toolbar">
                            <h2 id="viewer-title">{{ $fotografia->titulo }}</h2>
                            <div>
                                <button type="button" @click="zoomed = !zoomed" :aria-pressed="zoomed" :aria-label="zoomed ? 'Ajustar imagem à tela' : 'Ver imagem no tamanho original'" :title="zoomed ? 'Ajustar à tela' : 'Tamanho original'">
                                    <span aria-hidden="true" x-text="zoomed ? '−' : '+'">+</span>
                                </button>
                                <button type="button" x-ref="close" @click="close()" aria-label="Fechar visualização" title="Fechar (Esc)"><span aria-hidden="true">&times;</span></button>
                            </div>
                        </div>
                        <div class="public-image-viewer__viewport" :class="{ 'is-zoomed': zoomed }">
                            <div class="public-image-viewer__surface">
                                <img src="{{ route('publico.imagens.show', $imagem) }}" alt="{{ $fotografia->titulo }}" width="{{ $imagem->width ?: 1200 }}" height="{{ $imagem->height ?: 800 }}" loading="lazy">
                            </div>
                        </div>
                    </dialog>
                </div>
                <div class="public-photo-detail__content">
                    <p class="public-eyebrow">Fotografia do acervo</p>
                    <h1>{{ $fotografia->titulo }}</h1>
                    <p class="public-section__intro">{{ $fotografia->dataHistorica()->label() }}</p>
                    @if ($fotografia->legenda)
                        <p class="public-photo-detail__caption">{{ $fotografia->legenda }}</p>
                    @endif
                    <dl>
                        @foreach (['Autor' => $fotografia->autor?->nome, 'Local na época' => $fotografia->local_epoca, 'Local atual' => $fotografia->local_atual, 'Evento' => $fotografia->evento] as $label => $value)
                            @if ($value)
                                <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
                            @endif
                        @endforeach
                    </dl>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
