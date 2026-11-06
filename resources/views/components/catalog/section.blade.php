@props([
    'title' => null,
    'description' => null,
    // Sem preenchimento interno: tabelas e listas ocupam a largura toda do bloco (painéis de detalhes).
    'flush' => false,
    // Id do título: a seção passa a ser rotulada por ele (aria-labelledby).
    'titleId' => null,
])

{{-- Bloco padrão dos formulários e das páginas de detalhes do admin. O slot "actions" fica à direita do título; sem título nem ações, é só o cartão. --}}
<section {{ $attributes->merge($title && $titleId ? ['aria-labelledby' => $titleId] : [])->class(['rounded-lg border border-mist-200 bg-white shadow-card', 'p-5 sm:p-6' => ! $flush, 'overflow-hidden' => $flush]) }}>
    @if ($title || isset($actions))
        <header @class([
            'flex items-start justify-between gap-4 border-b border-mist-150',
            'mb-5 pb-4' => ! $flush,
            'px-5 py-4 sm:px-6' => $flush,
        ])>
            <div class="min-w-0">
                @if ($title)
                    <h2 @if ($titleId) id="{{ $titleId }}" @endif class="text-section font-semibold text-ink-950">{{ $title }}</h2>
                @endif
                @if ($description)
                    <p class="mt-1 text-[0.82rem] leading-normal text-ink-500">{{ $description }}</p>
                @endif
            </div>
            {{ $actions ?? '' }}
        </header>
    @endif

    {{ $slot }}
</section>
