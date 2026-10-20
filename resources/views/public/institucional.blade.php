<x-layouts.public :title="$page['title'] . ' | Memórias Vivas'">
    <section class="min-h-[60vh] border-b border-mist-200 bg-linear-115/srgb from-institutional-soft via-mist-25 via-58% to-white">
        <x-public.container class="grid grid-cols-1 items-center gap-10 py-[clamp(5rem,12vw,9rem)] md:grid-cols-[minmax(0,1.3fr)_minmax(16rem,0.7fr)] md:gap-[clamp(2rem,7vw,6rem)]">
            <div>
                <x-public.eyebrow>{{ $page['eyebrow'] }}</x-public.eyebrow>
                <x-public.heading as="h1" class="mt-[0.9rem] max-w-[48rem] text-[length:clamp(2.6rem,6vw,5.2rem)] leading-[1.03] font-semibold tracking-[-0.035em]">{{ $page['heading'] }}</x-public.heading>
                <p class="mt-[1.4rem] max-w-[39rem] text-[length:clamp(1rem,1.7vw,1.2rem)] leading-[1.75] text-ink-600">{{ $page['intro'] }}</p>
            </div>

            <aside class="flex items-start gap-4 border-t-2 border-institutional bg-white/82 px-[1.4rem] py-[1.35rem] shadow-[0_16px_38px_rgb(24_42_63/0.07)]" aria-label="Conteúdo da página">
                <span class="grid size-8 flex-none place-items-center rounded-full bg-institutional-soft font-serif text-[1.15rem] font-bold text-institutional" aria-hidden="true">i</span>
                <div>
                    <h2 class="text-[0.85rem] font-[750] text-institutional">Conteúdo em preparação</h2>
                    <p class="mt-[0.45rem] text-[0.82rem] leading-[1.65] text-ink-600">{{ $page['notice'] }}</p>
                </div>
            </aside>
        </x-public.container>
    </section>
</x-layouts.public>
