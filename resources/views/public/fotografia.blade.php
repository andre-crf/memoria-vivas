<x-layouts.public :title="$fotografia->titulo.' | Memórias Vivas'">
    <section class="public-section public-photo-detail">
        <div class="public-container">
            <a href="{{ route('public.catalogo') }}" class="public-back-link">← Voltar ao catálogo</a>

            <div class="public-photo-detail__heading">
                <p class="public-eyebrow">Fotografia do acervo</p>
                <h1>{{ $fotografia->titulo }}</h1>
                <p>{{ $fotografia->dataHistorica()->label() }}</p>
            </div>

            @php
                $imagem = $fotografia->arquivos->firstWhere('versao_arquivo', 'large')
                    ?? $fotografia->arquivos->firstWhere('versao_arquivo', 'medium')
                    ?? $fotografia->arquivos->firstWhere('versao_arquivo', 'thumbnail');
            @endphp

            <x-public.imagem
                :arquivo="$imagem"
                :alt="$fotografia->titulo"
                loading="eager"
                class="public-photo-detail__image"
            />
        </div>
    </section>
</x-layouts.public>
