@props([
    'tone' => 'neutral',
    'icon' => null,
])

@php
    // Tons suaves com texto escuro (contraste de 5,6:1 a 9,8:1). Quem usa decide o tom: o componente não conhece estados do acervo.
    $tones = [
        'neutral' => 'bg-mist-150 text-ink-700',
        'heritage' => 'bg-heritage-100 text-heritage-900',
        'info' => 'bg-institutional-soft text-institutional',
        'success' => 'bg-success-soft text-success',
        'warning' => 'bg-warning-soft text-warning-ink',
        'danger' => 'bg-error-soft text-danger',
    ];
@endphp

{{-- O texto vem sempre pelo slot: a cor reforça, mas nunca é a única informação. --}}
<span {{ $attributes->class([
    'inline-flex max-w-full items-center gap-1 rounded-full px-2.5 py-1 text-xs leading-none font-semibold ring-1 ring-current/15 ring-inset',
    $tones[$tone] ?? $tones['neutral'],
]) }}>
    @if ($icon)
        <x-dynamic-component :component="'lucide-'.$icon" class="-ml-0.5 size-3.5 flex-none" aria-hidden="true" />
    @endif
    <span class="min-w-0 truncate">{{ $slot }}</span>
</span>
