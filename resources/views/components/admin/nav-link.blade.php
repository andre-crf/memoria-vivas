@props([
    'active' => false,
    'variant' => 'bar',
])

<a
    @if ($active) aria-current="page" @endif
    {{ $attributes->class([
        'inline-flex flex-none items-center text-[0.8125rem] font-semibold text-ink-600 transition-[border-color,color] duration-160 ease-[ease] hover:text-institutional focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-institutional aria-[current=page]:text-institutional',
        'justify-center border-b-2 border-transparent aria-[current=page]:border-institutional' => $variant === 'bar',
        'min-h-[2.6rem] justify-start rounded px-3 hover:bg-institutional-soft aria-[current=page]:bg-institutional-soft' => $variant === 'menu',
    ]) }}
>{{ $slot }}</a>
