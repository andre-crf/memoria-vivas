<x-layouts.public title="{{ $fotografia->titulo }} | Memórias Vivas">
    @php($imagem = $fotografia->imagemAdministrativa(['large', 'medium', 'thumbnail']))

    <section class="public-section public-detail">
        <div class="public-container">
            <a href="{{ route('public.home') }}" class="public-text-link">&larr; Voltar para o início</a>
            <div class="public-detail__grid">
                <div class="public-detail__image">
                    @if ($imagem)
                        <img src="{{ route('publico.imagens.show', $imagem) }}" alt="{{ $fotografia->titulo }}" width="1200" height="800">
                    @else
                        <span>Imagem indisponível</span>
                    @endif
                </div>
                <div class="public-detail__content">
                    <p class="public-eyebrow">Fotografia do acervo</p>
                    <h1>{{ $fotografia->titulo }}</h1>
                    <p class="public-detail__date">{{ $fotografia->dataHistorica()->label() }}</p>
                    @if ($fotografia->legenda)
                        <div class="public-detail__caption">{{ $fotografia->legenda }}</div>
                    @endif
                    @if ($fotografia->local_atual || $fotografia->local_epoca)
                        <dl class="public-detail__metadata">
                            @if ($fotografia->local_atual)
                                <div><dt>Local atual</dt><dd>{{ $fotografia->local_atual }}</dd></div>
                            @endif
                            @if ($fotografia->local_epoca)
                                <div><dt>Local na época</dt><dd>{{ $fotografia->local_epoca }}</dd></div>
                            @endif
                        </dl>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
