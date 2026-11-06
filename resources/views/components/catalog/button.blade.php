@props([
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'loading' => false,
])

@php
    // primary: ação principal · secondary · outline: contorno institucional · danger: destrutiva, discreta · ghost: sem fundo.
    $variants = [
        'primary' => 'bg-institutional text-white hover:bg-institutional-strong',
        'secondary' => 'border border-mist-300 bg-white text-ink-800 hover:border-mist-500 hover:bg-mist-50',
        'outline' => 'border border-institutional bg-white text-institutional hover:bg-institutional-soft',
        'danger' => 'border border-danger/35 bg-white text-danger hover:border-danger hover:bg-error-soft',
        // Só para a confirmação final de uma ação destrutiva (diálogo de confirmação); nas telas, use danger.
        'danger-solid' => 'bg-danger text-white hover:bg-error-strong',
        'ghost' => 'text-ink-700 hover:bg-mist-150 hover:text-ink-950',
    ];
    $variant = array_key_exists($variant, $variants) ? $variant : 'primary';
    // A cor do contorno de foco fica numa classe só: duas cores no mesmo botão dependeriam da ordem do CSS.
    $focusColor = str_starts_with($variant, 'danger') ? 'focus-visible:outline-danger' : 'focus-visible:outline-institutional';

    $classes = [
        // relative: contém o texto sr-only do carregamento; sem isso ele escapa de contêineres com rolagem horizontal.
        'group/button relative inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md font-semibold whitespace-nowrap transition-colors',
        'focus-visible:outline-2 focus-visible:outline-offset-2',
        'disabled:cursor-not-allowed disabled:opacity-55 aria-disabled:cursor-not-allowed aria-disabled:opacity-55 aria-busy:cursor-wait',
        'min-h-10 px-4 text-ui' => $size !== 'sm',
        'min-h-8 px-3 text-[0.75rem]' => $size === 'sm',
        $variants[$variant],
        $focusColor,
    ];

    $isLink = $attributes->has('href');
    $disabled = in_array($attributes->get('disabled'), [true, '', 'disabled', 'true'], true);
    // Carregamento pela prop (servidor) ou por um aria-busy dinâmico do Alpine (x-bind:aria-busy="enviando").
    $showSpinner = $loading || $attributes->has('x-bind:aria-busy') || $attributes->has(':aria-busy');

    if ($isLink && ($disabled || $loading)) {
        // Link desabilitado: sem href não navega nem recebe foco; o papel de link continua anunciado.
        $tag = 'a';
        $bag = $attributes->except(['href', 'disabled'])->merge(['role' => 'link', 'aria-disabled' => 'true']);
    } elseif ($isLink) {
        $tag = 'a';
        $bag = $attributes;
    } else {
        // type="button" por padrão: dentro de um formulário, só envia quem pedir type="submit".
        $tag = 'button';
        $bag = $attributes->merge(['type' => 'button'] + ($loading ? ['disabled' => true] : []));
    }

    if ($loading) {
        $bag = $bag->merge(['aria-busy' => 'true']);
    }
@endphp

<{{ $tag }} {{ $bag->class($classes) }}>
    @if ($showSpinner)
        <x-lucide-loader-circle class="hidden size-4 flex-none animate-spin group-aria-busy/button:inline-block motion-reduce:animate-none" aria-hidden="true" />
    @endif
    @if ($icon)
        <x-dynamic-component :component="'lucide-'.$icon" :class="'size-4 flex-none'.($showSpinner ? ' group-aria-busy/button:hidden' : '')" aria-hidden="true" />
    @endif
    {{ $slot }}
    @if ($showSpinner)
        {{-- Só faz parte do nome acessível durante o carregamento. --}}
        <span class="sr-only hidden group-aria-busy/button:inline">(processando)</span>
    @endif
</{{ $tag }}>
