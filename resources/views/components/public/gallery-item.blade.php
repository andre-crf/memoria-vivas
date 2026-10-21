@props([
    'fotografia',
    'href',
])

@php
    $versoes = collect(['thumbnail', 'medium', 'large'])
        ->map(fn (string $versao) => $fotografia->arquivos->firstWhere('versao_arquivo', $versao))
        ->filter()
        ->values();
    $imagem = $versoes->first();
    $srcset = $versoes
        ->filter(fn ($arquivo) => $arquivo->width)
        ->map(fn ($arquivo) => route('publico.imagens.show', $arquivo).' '.$arquivo->width.'w')
        ->join(', ');
    $sizes = '(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw';
    $proporcao = $imagem?->width && $imagem?->height ? $imagem->width / $imagem->height : null;
    // Panoramas ficariam baixos demais para título e data: ganham altura mínima, sem corte.
    $panorama = $proporcao !== null && $proporcao > 2;
    $resumo = $fotografia->legenda
        ? Str::limit(trim(preg_replace('/\s+/u', ' ', $fotografia->legenda)), 140)
        : null;
    $zoom = 'motion-safe:transition-transform motion-safe:duration-500 motion-safe:group-hover:scale-[1.03] motion-safe:group-focus-visible:scale-[1.03]';
@endphp

<a
    href="{{ $href }}"
    x-data="{ indisponivel: {{ $imagem ? 'false' : 'true' }} }"
    x-init="$nextTick(() => { const foto = $refs.foto; if (foto && foto.complete && foto.naturalWidth === 0) indisponivel = true })"
    {{ $attributes->class('group relative block overflow-hidden rounded-md bg-mist-120 focus-visible:outline-3 focus-visible:outline-offset-3 focus-visible:outline-institutional') }}
>
    @if ($imagem)
        @if ($panorama)
            <div class="relative aspect-2/1 overflow-hidden" x-show="! indisponivel">
                <img src="{{ route('publico.imagens.show', $imagem) }}" srcset="{{ $srcset }}" sizes="{{ $sizes }}" alt="" aria-hidden="true" loading="lazy" decoding="async" class="absolute -inset-8 h-[calc(100%+4rem)] w-[calc(100%+4rem)] max-w-none object-cover blur-xl">
                <div class="absolute inset-0 bg-night-950/10" aria-hidden="true"></div>
                <img x-ref="foto" src="{{ route('publico.imagens.show', $imagem) }}" srcset="{{ $srcset }}" sizes="{{ $sizes }}" alt="{{ $fotografia->titulo }}" width="{{ $imagem->width }}" height="{{ $imagem->height }}" loading="lazy" decoding="async" x-on:error="indisponivel = true" class="relative size-full object-contain {{ $zoom }}">
            </div>
        @else
            <img x-ref="foto" x-show="! indisponivel" src="{{ route('publico.imagens.show', $imagem) }}" srcset="{{ $srcset }}" sizes="{{ $sizes }}" alt="{{ $fotografia->titulo }}" @if ($imagem->width && $imagem->height) width="{{ $imagem->width }}" height="{{ $imagem->height }}" @endif loading="lazy" decoding="async" x-on:error="indisponivel = true" class="block h-auto w-full {{ $zoom }}">
        @endif
    @endif

    <div @if ($imagem) x-show="indisponivel" x-cloak @endif class="grid aspect-4/3 place-content-center justify-items-center gap-2 pb-12 text-ink-500" role="img" aria-label="Imagem indisponível">
        <x-lucide-image-off class="size-8" aria-hidden="true" />
        <span class="text-[0.78rem] font-semibold">Imagem indisponível</span>
    </div>

    {{-- Gradiente de baixo para cima: o topo da foto continua limpo; no hover/foco ele se intensifica. --}}
    <div class="pointer-events-none absolute inset-x-0 bottom-0 h-3/5 bg-linear-to-t from-night-950/85 via-night-950/30 to-transparent" aria-hidden="true"></div>
    <div class="pointer-events-none absolute inset-x-0 bottom-0 h-3/5 bg-linear-to-t from-night-950/45 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100 group-focus-visible:opacity-100 motion-reduce:transition-none" aria-hidden="true"></div>

    <div class="absolute inset-x-0 bottom-0 p-4 text-white">
        <h2 class="line-clamp-2 font-serif text-[1.12rem] leading-[1.2] font-medium text-balance wrap-anywhere">{{ $fotografia->titulo }}</h2>
        <p class="mt-1 text-[0.78rem] text-white/80">{{ $fotografia->dataHistorica()->label() }}</p>
        @if ($resumo)
            {{-- Só a partir do tablet: no celular título e data bastam, e o toque abre direto a fotografia. --}}
            <div class="hidden grid-rows-[0fr] opacity-0 transition-[grid-template-rows,opacity] duration-300 group-hover:grid-rows-[1fr] group-hover:opacity-100 group-focus-visible:grid-rows-[1fr] group-focus-visible:opacity-100 motion-reduce:transition-none md:grid">
                <p class="min-h-0 overflow-hidden"><span class="mt-2 line-clamp-2 block text-[0.8rem] leading-[1.45] text-white/85">{{ $resumo }}</span></p>
            </div>
        @endif
    </div>
</a>
