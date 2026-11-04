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

    <x-public.section id="instituicoes" aria-labelledby="about-institutions-title">
        <x-public.container>
            <div class="grid gap-5 md:grid-cols-[minmax(0,1fr)_minmax(16rem,0.85fr)] md:items-end md:gap-12">
                <div>
                    <x-public.eyebrow>Referências institucionais</x-public.eyebrow>
                    <x-public.heading id="about-institutions-title" class="mt-[0.8rem] {{ $h2 }}">Memórias construídas em conjunto</x-public.heading>
                </div>
                <p class="max-w-[32rem] text-[0.95rem] leading-[1.7] text-ink-600">
                    Instituições e acervos que participaram de ações documentadas do Memórias Vivas em Umuarama.
                    <a href="https://umuarama.pr.gov.br/noticias/comunicao-social/exposio-rostos-e-memria-de-trabalho-e-trabalhadores-conta-histria-da-cidade" class="font-semibold text-institutional underline underline-offset-4 hover:text-institutional-strong" target="_blank" rel="noopener noreferrer">Conheça um desses registros<span class="sr-only"> (abre em nova aba)</span></a>.
                </p>
            </div>

            <ul class="mt-9 grid gap-7 md:mt-12 md:grid-cols-3 md:gap-8" aria-label="Instituições e acervos relacionados ao projeto">
                @foreach (config('institucional.referencias') as $instituicao)
                    <li class="min-w-0 border-t border-mist-400 pt-5">
                        <div class="flex h-40 items-end">
                            @if (! empty($instituicao['logo']) && is_file(public_path($instituicao['logo'])))
                                <img src="{{ asset($instituicao['logo']) }}" alt="" width="{{ $instituicao['logo_largura'] }}" height="{{ $instituicao['logo_altura'] }}" loading="lazy" class="max-h-40 h-auto w-auto max-w-full object-contain object-bottom">
                            @else
                                <p class="pb-1 text-[0.72rem] font-extrabold tracking-[0.12em] text-ink-500 uppercase">{{ $instituicao['sigla'] }}</p>
                            @endif
                        </div>
                        <h3 class="mt-5 max-w-[20rem] font-serif text-[1.35rem] leading-[1.2] font-semibold text-institutional wrap-anywhere">{{ $instituicao['nome'] }}</h3>
                        <p class="mt-3 max-w-[22rem] text-[0.9rem] leading-[1.7] text-ink-600">{{ $instituicao['descricao'] }}</p>
                        <a href="{{ $instituicao['url'] }}" class="mt-5 inline-flex items-center gap-2 text-[0.82rem] font-bold text-institutional underline underline-offset-4 hover:text-institutional-strong" target="_blank" rel="noopener noreferrer">
                            {{ $instituicao['sigla'] === 'CEDHU' ? 'Conhecer o acervo' : 'Visitar instituição' }}
                            <x-lucide-arrow-up-right class="size-4 flex-none" aria-hidden="true" />
                            <span class="sr-only">(abre em nova aba)</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-public.container>
    </x-public.section>

    <x-public.invite eyebrow="Memória em movimento" title="Toda consulta pode revelar uma nova história." title-id="about-invite-title">
        <x-slot:action>
            <x-public.button :href="route('public.catalogo')" class="flex-none">Ver fotografias do acervo <span aria-hidden="true">&rarr;</span></x-public.button>
        </x-slot:action>
    </x-public.invite>
</x-layouts.public>
