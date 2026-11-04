@props([
    'active' => false,
    'variant' => 'bar',
])

<a
    @if ($active) aria-current="page" @endif
    {{ $attributes->class([
        'relative after:absolute after:inset-x-0 after:bottom-0 after:h-0.5 after:origin-left after:scale-x-0 after:bg-institutional after:transition-transform after:duration-140 after:ease-[ease] hover:text-institutional hover:after:scale-x-100 aria-[current=page]:font-[750] aria-[current=page]:text-institutional aria-[current=page]:after:scale-x-100 motion-reduce:after:transition-none',
        'py-2' => $variant === 'bar',
        'px-3 py-[0.65rem] aria-[current=page]:rounded-[0.2rem] aria-[current=page]:bg-institutional-soft' => $variant === 'menu',
    ]) }}
>{{ $slot }}</a>
