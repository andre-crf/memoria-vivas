@php($multiple = $slides->count() > 1)
<section
    class="relative isolate min-h-[min(36rem,calc(100svh-8rem))] overflow-hidden bg-institutional text-white md:min-h-[min(40rem,calc(100svh-8rem))]"
    x-data="heroCarousel({{ $slides->count() }})"
    @if ($multiple)
        tabindex="0"
        aria-roledescription="carrossel"
        aria-label="Fotografias históricas em destaque"
        @keydown.arrow-left.prevent="previous()"
        @keydown.arrow-right.prevent="next()"
    @endif
    data-hero-carousel
>
    @foreach ($slides as $slide)
        <div
            class="absolute inset-0 transition-opacity duration-500 ease-in-out motion-reduce:transition-none {{ $loop->first ? 'opacity-100' : 'opacity-0' }}"
            :class="active === {{ $loop->index }} ? 'opacity-100' : 'opacity-0'"
            :aria-hidden="active === {{ $loop->index }} ? 'false' : 'true'"
            :inert="active !== {{ $loop->index }}"
            aria-hidden="{{ $loop->first ? 'false' : 'true' }}"
            @if (! $loop->first) inert @endif
            data-hero-slide
        >
            <img
                src="{{ asset($slide['imagem']) }}"
                alt="{{ $slide['alt'] }}"
                class="absolute inset-0 size-full object-cover"
                style="object-position: {{ $slide['posicao'] ?? 'center' }}"
                loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                @if ($loop->first) fetchpriority="high" @endif
            >
            <div class="absolute inset-0 bg-ink-950/60" aria-hidden="true"></div>
        </div>
    @endforeach

    <x-public.container class="relative z-1 flex min-h-[min(36rem,calc(100svh-8rem))] flex-col justify-between gap-4 py-6 md:min-h-[min(40rem,calc(100svh-8rem))] md:gap-10 md:py-[clamp(2.5rem,6vw,5rem)]">
        <div class="max-w-[43rem]">
            <p class="text-[0.72rem] font-extrabold tracking-[0.12em] text-white/85 uppercase">Acervo público</p>
            <h1 class="mt-2 max-w-[42rem] font-serif text-[length:clamp(2rem,9vw,3rem)] leading-[1.05] font-semibold wrap-anywhere md:mt-4 md:text-[length:clamp(2.4rem,6vw,5rem)]">Memórias Vivas de Umuarama</h1>
            <p class="mt-3 max-w-[34rem] text-[0.95rem] leading-[1.5] text-white/90 md:mt-5 md:text-[1rem] md:leading-[1.7]">Conheça, preserve e compartilhe a memória de Umuarama.</p>
            <div class="mt-4 flex flex-wrap items-center gap-5 md:mt-7">
                <a href="{{ route('public.catalogo') }}" class="inline-flex min-h-11 items-center gap-2 rounded-md bg-white px-4 py-3 text-[0.83rem] font-bold text-institutional hover:bg-institutional-soft">
                    Explorar o catálogo <x-lucide-arrow-right class="size-4" aria-hidden="true" />
                </a>
                <a href="#sobre" class="hidden text-[0.84rem] font-bold text-white underline underline-offset-4 hover:text-institutional-soft min-[360px]:inline">Sobre o projeto</a>
            </div>
        </div>

        <div class="flex flex-wrap items-end justify-between gap-x-8 gap-y-3 border-t border-white/30 pt-3 md:gap-y-5 md:pt-5">
            <div class="min-w-0 max-w-[39rem]" aria-live="polite" aria-atomic="true">
                @foreach ($slides as $slide)
                    <div
                        x-show="active === {{ $loop->index }}"
                        @if (! $loop->first) style="display: none" @endif
                        data-hero-caption
                    >
                        @if (filled($slide['titulo'] ?? null))
                            <h2 class="font-serif text-[length:clamp(1.15rem,2.4vw,1.6rem)] leading-[1.2] font-semibold">{{ $slide['titulo'] }}</h2>
                        @endif
                        @if (filled($slide['descricao'] ?? null))
                            <p class="mt-1.5 text-[0.85rem] leading-[1.5] text-white/85">{{ $slide['descricao'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>

            @if ($multiple)
                <div class="flex flex-none items-center gap-3" aria-label="Navegação das fotografias" data-hero-controls x-cloak>
                    <button type="button" class="grid size-10 cursor-pointer place-items-center rounded-full border border-white/70 text-white hover:bg-white hover:text-institutional" @click="previous()" aria-label="Fotografia anterior" title="Fotografia anterior">
                        <x-lucide-chevron-left class="size-5" aria-hidden="true" />
                    </button>
                    <div class="flex items-center gap-2" aria-label="Selecionar fotografia">
                        @foreach ($slides as $slide)
                            <button
                                type="button"
                                class="size-2.5 cursor-pointer rounded-full border border-white transition-colors motion-reduce:transition-none"
                                :class="active === {{ $loop->index }} ? 'bg-white' : 'bg-transparent'"
                                :aria-current="active === {{ $loop->index }} ? 'true' : 'false'"
                                @click="goTo({{ $loop->index }})"
                                aria-label="Exibir fotografia {{ $loop->iteration }} de {{ $slides->count() }}"
                                title="Fotografia {{ $loop->iteration }}"
                            ></button>
                        @endforeach
                    </div>
                    <button type="button" class="grid size-10 cursor-pointer place-items-center rounded-full border border-white/70 text-white hover:bg-white hover:text-institutional" @click="next()" aria-label="Próxima fotografia" title="Próxima fotografia">
                        <x-lucide-chevron-right class="size-5" aria-hidden="true" />
                    </button>
                </div>
            @endif
        </div>
    </x-public.container>
</section>
