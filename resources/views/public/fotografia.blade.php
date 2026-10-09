<x-layouts.public :title="$fotografia->titulo.' | Memórias Vivas'">
    @php
        $imagem = $fotografia->arquivos->firstWhere('versao_arquivo', 'large')
            ?? $fotografia->arquivos->firstWhere('versao_arquivo', 'medium')
            ?? $fotografia->arquivos->firstWhere('versao_arquivo', 'thumbnail');
    @endphp

    <section class="public-section public-detail">
        <div class="public-container">
            <a href="{{ route('public.catalogo') }}" class="public-back-link">&larr; Voltar ao catálogo</a>
            <div class="public-detail__grid">
                <x-public.imagem
                    :arquivo="$imagem"
                    :alt="$fotografia->titulo"
                    loading="eager"
                    class="public-detail__image"
                />

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
