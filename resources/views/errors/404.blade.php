<x-layouts.public title="Página não encontrada | Memórias Vivas">
    <section class="min-h-[60vh] bg-mist-25" aria-labelledby="not-found-title">
        <x-public.container class="flex min-h-[60vh] flex-col items-start justify-center py-20">
            <x-public.eyebrow>Erro 404</x-public.eyebrow>
            <x-public.heading as="h1" id="not-found-title" class="mt-[0.85rem] max-w-[45rem] text-[length:clamp(2.4rem,6vw,4.5rem)] leading-[1.05]">Esta página não foi encontrada</x-public.heading>
            <p class="mt-4 max-w-[38rem] text-[1rem] leading-[1.7] text-ink-600">O endereço pode ter mudado ou não estar disponível. Volte ao início ou explore o catálogo público.</p>
            <div class="flex flex-wrap items-center gap-5">
                <x-public.button :href="route('public.home')" class="mt-6">Ir para o início</x-public.button>
                <a class="mt-6 text-[0.85rem] font-bold text-institutional underline-offset-[0.2em]" href="{{ route('public.catalogo') }}">Abrir o catálogo</a>
            </div>
        </x-public.container>
    </section>
</x-layouts.public>
