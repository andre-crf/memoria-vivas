<x-layouts.public title="Memórias Vivas | Acervo público">
    @php
        $h2 = 'mt-[0.8rem] text-[length:clamp(1.8rem,4vw,3rem)] leading-[1.12] font-semibold';
        $carouselControl = 'absolute bottom-2 z-1 grid size-10 place-items-center rounded-full border border-mist-500 bg-white text-[1.1rem] text-institutional hover:border-institutional hover:bg-institutional hover:text-white md:top-1/2 md:bottom-auto md:-translate-y-1/2';
    @endphp

    @if ($heroSlides->isNotEmpty())
        @include('public._hero-carousel', ['slides' => $heroSlides])
    @else
    <section class="border-b border-mist-200 bg-institutional-soft">
        <x-public.container class="grid grid-cols-1 items-center gap-8 md:min-h-[min(43rem,calc(100vh-5rem))] md:grid-cols-[minmax(0,1.35fr)_minmax(14rem,0.65fr)] md:gap-16">
            <div class="py-[clamp(4rem,10vw,8rem)]">
                <x-public.eyebrow>Acervo público</x-public.eyebrow>
                <x-public.heading as="h1" class="mt-[0.8rem] max-w-[42rem] text-[length:clamp(2.4rem,6vw,5rem)] leading-[1.05] font-semibold">Memórias Vivas de Umuarama</x-public.heading>
                <p class="mt-[1.35rem] max-w-[37rem] text-[1.08rem] leading-[1.7] text-ink-600">
                    Um espaço para conhecer, preservar e compartilhar registros da memória de Umuarama.
                </p>
                <div class="mt-8 flex flex-wrap items-center gap-5">
                    <x-public.button :href="route('public.catalogo')">Explorar o catálogo <span aria-hidden="true">&rarr;</span></x-public.button>
                    <x-public.text-link href="#sobre">Sobre o projeto</x-public.text-link>
                </div>
            </div>

            <div class="grid max-w-76 justify-items-start gap-5 border-t border-mist-500 pt-8 pb-12 text-left text-ink-600 md:justify-items-center md:border-t-0 md:border-l md:pb-8 md:pl-12 md:text-center" aria-label="Identidade visual do projeto">
                <img
                    src="{{ asset('images/memorias-vivas-logo.jpeg') }}"
                    alt="Logo Memórias Vivas de Umuarama"
                    width="180"
                    height="180"
                    class="size-[clamp(9rem,18vw,13rem)] rounded-full border-[0.7rem] border-white object-cover shadow-[0_1rem_2rem_rgb(23_62_99/0.12)]"
                >
                <p class="max-w-80 font-serif text-[1rem] leading-[1.5] md:max-w-64">Preservar o passado para ampliar o acesso às histórias da cidade.</p>
            </div>
        </x-public.container>
    </section>
    @endif

    <x-public.section id="sobre">
        <x-public.container class="grid grid-cols-1 gap-8 md:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] md:gap-16">
            <div>
                <x-public.eyebrow>O acervo</x-public.eyebrow>
                <x-public.heading class="max-w-[42rem] {{ $h2 }}">Histórias que permanecem acessíveis</x-public.heading>
            </div>
            <div class="grid max-w-160 gap-4 text-[1rem] leading-[1.75] text-ink-600">
                <p>A área pública está sendo preparada para apresentar fotografias e documentos catalogados, com contexto e informações que ajudam a reconhecer cada registro.</p>
                <p>Os conteúdos serão disponibilizados conforme as etapas de organização e revisão do acervo forem concluídas.</p>
            </div>
        </x-public.container>
    </x-public.section>

    <x-public.section tinted>
        <x-public.container class="grid grid-cols-1 gap-8 md:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)] md:gap-16">
            <div>
                <x-public.eyebrow>Como consultar</x-public.eyebrow>
                <x-public.heading class="max-w-[30rem] {{ $h2 }}">Uma porta de entrada para a memória local</x-public.heading>
            </div>
            <div class="grid gap-5">
                @foreach ([
                    ['01', 'Explore', 'Consulte os registros públicos disponíveis no catálogo.'],
                    ['02', 'Reconheça', 'Conheça os contextos e as informações de cada fotografia.'],
                    ['03', 'Compartilhe', 'Leve adiante as memórias que ajudam a contar a história de Umuarama.'],
                ] as [$numero, $titulo, $texto])
                    <div class="grid grid-cols-[2.5rem_minmax(0,1fr)] gap-4 border-t border-mist-350 pt-[1.15rem]">
                        <span class="text-[0.72rem] font-extrabold tracking-[0.08em] text-institutional">{{ $numero }}</span>
                        <div>
                            <h3 class="text-[1rem] text-institutional">{{ $titulo }}</h3>
                            <p class="mt-[0.35rem] text-[0.9rem] leading-[1.6] text-ink-600">{{ $texto }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-public.container>
    </x-public.section>

    <x-public.section aria-labelledby="recentes-titulo">
        <x-public.container>
            <div class="flex flex-col items-start gap-4 md:flex-row md:items-end md:justify-between md:gap-8">
                <div>
                    <x-public.eyebrow>Em destaque</x-public.eyebrow>
                    <x-public.heading id="recentes-titulo" class="max-w-[42rem] {{ $h2 }}">Fotografias recentes</x-public.heading>
                </div>
                <x-public.text-link :href="route('public.catalogo')" class="mb-[0.35rem] flex-none">Ver catálogo <span aria-hidden="true">&rarr;</span></x-public.text-link>
            </div>

            @if ($fotografias->isNotEmpty())
                <div class="relative mt-9 md:px-13" data-carousel @if ($fotografias->count() > 1) tabindex="0" @endif aria-roledescription="carrossel" aria-label="Fotografias recentes">
                    @if ($fotografias->count() > 1)
                        <button type="button" class="left-4 md:left-0 {{ $carouselControl }}" data-carousel-prev aria-label="Fotografia anterior">&larr;</button>
                    @endif
                    <div class="overflow-hidden">
                        <div class="flex transition-transform duration-220 ease-[ease]" data-carousel-track>
                            @foreach ($fotografias as $fotografia)
                                @php($imagem = $fotografia->imagemAdministrativa(['medium', 'thumbnail', 'large']))
                                <article
                                    class="min-w-full"
                                    data-carousel-slide
                                    aria-label="{{ $loop->iteration }} de {{ $fotografias->count() }}"
                                    aria-hidden="{{ $loop->first ? 'false' : 'true' }}"
                                    @if (! $loop->first) inert @endif
                                >
                                    <a href="{{ route('public.fotografias.show', $fotografia) }}" class="grid min-h-88 grid-cols-1 border border-mist-200 bg-white text-ink-950 hover:border-mist-500 hover:shadow-[0_0.75rem_2rem_rgb(23_62_99/0.08)] md:grid-cols-[minmax(0,1.25fr)_minmax(15rem,0.75fr)]">
                                        <div class="grid max-h-72 min-h-56 place-items-center overflow-hidden bg-mist-250 text-ink-600 md:max-h-none md:min-h-88">
                                            @if ($imagem)
                                                <img src="{{ route('public.imagens.show', $imagem) }}" alt="{{ $fotografia->titulo }}" loading="lazy" width="640" height="420" class="size-full object-cover">
                                            @else
                                                <span aria-hidden="true">Sem imagem disponível</span>
                                            @endif
                                        </div>
                                        <div class="flex min-h-60 flex-col justify-center p-6 md:min-h-0 md:p-[clamp(1.5rem,4vw,3rem)]">
                                            <h3 class="font-serif text-[length:clamp(1.45rem,3vw,2.25rem)] leading-[1.15] wrap-anywhere text-institutional">{{ $fotografia->titulo }}</h3>
                                            <p class="mt-[0.8rem] text-[0.9rem] text-ink-600">{{ $fotografia->dataHistorica()->label() }}</p>
                                            <span class="mt-8 text-[0.8rem] font-extrabold text-institutional">Ver detalhes <span aria-hidden="true">&rarr;</span></span>
                                        </div>
                                    </a>
                                </article>
                            @endforeach
                        </div>
                    </div>
                    @if ($fotografias->count() > 1)
                        <button type="button" class="right-4 md:right-0 {{ $carouselControl }}" data-carousel-next aria-label="Próxima fotografia">&rarr;</button>
                        <div class="mt-5 flex justify-center gap-[0.45rem]" data-carousel-dots aria-label="Selecionar fotografia"></div>
                    @endif
                </div>
            @else
                <x-public.empty-state title="Nenhuma fotografia pública disponível ainda" class="mt-9">
                    <span>As fotografias publicadas aparecerão aqui conforme o acervo for revisado.</span>
                </x-public.empty-state>
            @endif
        </x-public.container>
    </x-public.section>
</x-layouts.public>

@if ($fotografias->isNotEmpty())
    <script>
        document.querySelectorAll('[data-carousel]').forEach((carousel) => {
            const track = carousel.querySelector('[data-carousel-track]');
            const slides = [...carousel.querySelectorAll('[data-carousel-slide]')];
            const dots = carousel.querySelector('[data-carousel-dots]');
            let current = 0;

            const update = (index) => {
                current = (index + slides.length) % slides.length;
                track.style.transform = `translateX(-${current * 100}%)`;
                slides.forEach((slide, slideIndex) => {
                    const active = slideIndex === current;
                    slide.setAttribute('aria-hidden', active ? 'false' : 'true');
                    slide.inert = !active;
                });
                if (dots) {
                    [...dots.children].forEach((dot, dotIndex) => dot.setAttribute('aria-current', dotIndex === current ? 'true' : 'false'));
                }
            };

            if (dots) {
                slides.forEach((slide, index) => {
                    const dot = document.createElement('button');
                    dot.type = 'button';
                    dot.className = 'size-[0.55rem] rounded-full bg-mist-400 aria-[current=true]:bg-institutional aria-[current=true]:shadow-[0_0_0_3px_var(--color-institutional-muted)]';
                    dot.setAttribute('aria-label', `Exibir fotografia ${index + 1}`);
                    dot.addEventListener('click', () => update(index));
                    dots.append(dot);
                });
            }

            if (slides.length > 1) {
                carousel.querySelector('[data-carousel-prev]').addEventListener('click', () => update(current - 1));
                carousel.querySelector('[data-carousel-next]').addEventListener('click', () => update(current + 1));
                carousel.addEventListener('keydown', (event) => {
                    if (event.key === 'ArrowLeft') update(current - 1);
                    if (event.key === 'ArrowRight') update(current + 1);
                });
            }
            update(0);
        });
    </script>
@endif
