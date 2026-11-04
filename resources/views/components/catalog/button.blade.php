@props([
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
])

@php
    $classes = [
        'inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md font-semibold whitespace-nowrap transition-colors disabled:cursor-wait disabled:opacity-55 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-institutional',
        'min-h-10 px-4 text-[0.8rem]' => $size === 'md',
        'min-h-8 px-3 text-[0.75rem]' => $size === 'sm',
        'bg-institutional text-white hover:bg-institutional-strong' => $variant === 'primary',
        'border border-mist-300 bg-white text-ink-800 hover:border-mist-500 hover:bg-mist-50' => $variant === 'secondary',
    ];
@endphp

@if ($attributes->has('href'))
    <a {{ $attributes->class($classes) }}>
        @if ($icon) <x-dynamic-component :component="'lucide-'.$icon" class="size-4 flex-none" aria-hidden="true" /> @endif
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => 'button'])->class($classes) }}>
        @if ($icon) <x-dynamic-component :component="'lucide-'.$icon" class="size-4 flex-none" aria-hidden="true" /> @endif
        {{ $slot }}
    </button>
@endif
