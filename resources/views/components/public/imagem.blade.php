@props([
    'arquivo',
    'alt',
    'loading' => 'lazy',
])

<div
    x-data="{ indisponivel: false }"
    {{ $attributes->class('relative overflow-hidden bg-mist-120') }}
>
    <img
        x-show="! indisponivel"
        class="size-full object-cover"
        x-on:error="indisponivel = true"
        src="{{ route('publico.imagens.show', $arquivo) }}"
        alt="{{ $alt }}"
        loading="{{ $loading }}"
        @if ($arquivo->width) width="{{ $arquivo->width }}" @endif
        @if ($arquivo->height) height="{{ $arquivo->height }}" @endif
    >

    <div x-cloak x-show="indisponivel" class="grid size-full min-h-[inherit] place-content-center justify-items-center gap-2 bg-mist-120 text-center text-ink-600" role="img" aria-label="Imagem indisponível">
        <span class="grid size-12 place-items-center rounded-full border border-mist-500 font-serif font-bold text-institutional" aria-hidden="true">MV</span>
        <strong class="text-[0.78rem]">Imagem indisponível</strong>
    </div>
</div>
