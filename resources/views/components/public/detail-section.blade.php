@props([
    'title',
])

<section {{ $attributes->class('min-w-0 border-t-2 border-institutional bg-mist-50 p-6') }}>
    <x-public.heading class="max-w-[42rem] text-[1.25rem] leading-[1.12] font-semibold">{{ $title }}</x-public.heading>
    {{ $slot }}
</section>
