@props([
    'eyebrow',
    'title',
    'titleId',
])

{{-- Faixa de chamada no fim das páginas institucionais. O slot "action" recebe o botão. --}}
<section {{ $attributes->class('bg-institutional-soft') }} aria-labelledby="{{ $titleId }}">
    <x-public.container class="flex flex-col items-start justify-between gap-8 py-12 md:flex-row md:items-center md:py-14">
        <div>
            <x-public.eyebrow>{{ $eyebrow }}</x-public.eyebrow>
            <x-public.heading :id="$titleId" class="mt-[0.8rem] max-w-[42rem] text-[length:clamp(1.8rem,3.5vw,2.5rem)] leading-[1.12] font-semibold">{{ $title }}</x-public.heading>
            {{ $slot }}
        </div>
        {{ $action }}
    </x-public.container>
</section>
