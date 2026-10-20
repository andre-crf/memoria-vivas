@props([
    'eyebrow',
    'title',
    'lead',
    'tinted' => true,
])

{{-- Hero das páginas institucionais (Sobre, Como colaborar). O slot recebe os botões. --}}
<section {{ $attributes->class(['border-b border-mist-200', 'bg-institutional-soft' => $tinted, 'bg-mist-100' => ! $tinted]) }}>
    <x-public.container class="grid grid-cols-1 items-center gap-0 py-9 md:min-h-[32rem] md:grid-cols-[minmax(0,1.4fr)_minmax(14rem,0.6fr)] md:gap-10 md:py-16 lg:gap-16">
        <div class="max-w-[48rem]">
            <x-public.eyebrow>{{ $eyebrow }}</x-public.eyebrow>
            <x-public.heading as="h1" class="mt-[0.85rem] max-w-[46rem] text-[length:clamp(2.25rem,9vw,3.5rem)] leading-[1.04] font-semibold md:text-[length:clamp(2.8rem,6vw,5rem)]">{{ $title }}</x-public.heading>
            <p class="mt-[1.3rem] max-w-[40rem] text-[1rem] leading-[1.75] text-ink-600 md:text-[1.05rem]">{{ $lead }}</p>
            <div class="mt-[1.8rem] flex flex-wrap items-center gap-5">
                {{ $slot }}
            </div>
        </div>
        <div class="hidden justify-items-center gap-4 border-l border-mist-400 py-8 pl-10 text-center text-institutional md:grid">
            <img src="{{ asset('images/memorias-vivas-logo.png') }}" alt="Memórias Vivas de Umuarama" width="180" height="180" class="size-[clamp(9rem,16vw,12rem)] rounded-full border-[0.55rem] border-white object-cover shadow-[0_1rem_2rem_rgb(23_62_99/0.12)]">
            <p class="font-serif text-[1.1rem] leading-[1.35] font-bold">Memórias Vivas<br><span class="font-sans text-[0.72rem] font-[650]">Acervo de Umuarama</span></p>
        </div>
    </x-public.container>
</section>
