@props([
    'arquivo',
    'alt',
    'loading' => 'lazy',
])

<div
    x-data="{ indisponivel: false }"
    {{ $attributes->class('public-image') }}
>
    <img
        x-show="! indisponivel"
        x-on:error="indisponivel = true"
        src="{{ route('publico.imagens.show', $arquivo) }}"
        alt="{{ $alt }}"
        loading="{{ $loading }}"
        @if ($arquivo->width) width="{{ $arquivo->width }}" @endif
        @if ($arquivo->height) height="{{ $arquivo->height }}" @endif
    >

    <div x-cloak x-show="indisponivel" class="public-image__fallback" role="img" aria-label="Imagem indisponível">
        <span aria-hidden="true">MV</span>
        <strong>Imagem indisponível</strong>
    </div>
</div>
