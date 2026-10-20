@props([
    'tinted' => false,
])

<section {{ $attributes->class(['py-[clamp(4rem,8vw,7rem)]', 'border-t border-mist-200 bg-mist-75' => $tinted, 'bg-white' => ! $tinted]) }}>
    {{ $slot }}
</section>
