<x-layouts.public title="Sobre nós | Memórias Vivas">
    @php($h2 = 'max-w-[42rem] text-[length:clamp(1.8rem,4vw,3rem)] leading-[1.12] font-semibold')

    <x-public.page-hero
        eyebrow="Sobre o projeto"
        title="Umuarama também se conta pelas suas memórias."
        lead="Memórias Vivas reúne fotografias e informações do acervo para preservar histórias da cidade e aproximá-las de quem deseja conhecer, pesquisar e compartilhar."
    >
        <x-public.button :href="route('public.catalogo')">Explorar o catálogo <span aria-hidden="true">&rarr;</span></x-public.button>
        <x-public.text-link :href="route('public.colaborar')">Conheça formas de colaborar</x-public.text-link>
    </x-public.page-hero>

    <x-public.section aria-labelledby="about-purpose-title">
        <x-public.container class="grid grid-cols-1 gap-6 md:grid-cols-2 md:gap-16">
            <div>
                <x-public.eyebrow>Nosso propósito</x-public.eyebrow>
                <x-public.heading id="about-purpose-title" class="mt-[0.8rem] {{ $h2 }}">Uma fotografia ganha sentido quando sua história pode ser encontrada.</x-public.heading>
            </div>
            <div class="grid max-w-160 gap-4 text-[1rem] leading-[1.75] text-ink-600">
                <p>O projeto organiza registros fotográficos e as informações que ajudam a compreendê-los: datas, lugares, pessoas, acontecimentos e temas relacionados.</p>
                <p>Assim, cada item publicado pode ser consultado em seu contexto, e as memórias de Umuarama ficam mais acessíveis para diferentes gerações.</p>
            </div>
        </x-public.container>
    </x-public.section>

    <x-public.section tinted aria-labelledby="about-process-title">
        <x-public.container>
            <div class="grid grid-cols-1 items-end gap-6 md:grid-cols-[minmax(0,1fr)_minmax(16rem,0.7fr)] md:gap-x-12 md:gap-y-4">
                <x-public.eyebrow class="md:col-span-full">Do acervo à consulta</x-public.eyebrow>
                <x-public.heading id="about-process-title" class="-mt-4 md:mt-[0.8rem] {{ $h2 }}">Cuidado em cada etapa</x-public.heading>
                <p class="max-w-[28rem] text-[0.95rem] leading-[1.7] text-ink-600">Os registros passam por organização e revisão antes de serem apresentados ao público.</p>
            </div>
            <x-public.steps class="mt-8 md:mt-12" :items="[
                ['Preservar', 'Manter o arquivo original e preparar versões adequadas para consulta digital.'],
                ['Organizar', 'Associar informações descritivas que ajudam a identificar e contextualizar cada fotografia.'],
                ['Compartilhar', 'Publicar os registros revisados em um catálogo aberto para explorar e pesquisar.'],
            ]" />
        </x-public.container>
    </x-public.section>

    <x-public.invite eyebrow="Memória em movimento" title="Toda consulta pode revelar uma nova história." title-id="about-invite-title">
        <x-slot:action>
            <x-public.button :href="route('public.catalogo')" class="flex-none">Ver fotografias do acervo <span aria-hidden="true">&rarr;</span></x-public.button>
        </x-slot:action>
    </x-public.invite>
</x-layouts.public>
