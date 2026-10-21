<x-layouts.public :title="$fotografia->titulo.' | Memórias Vivas'">
    @php
        $imagem = $fotografia->arquivos->firstWhere('versao_arquivo', 'large')
            ?? $fotografia->arquivos->firstWhere('versao_arquivo', 'medium')
            ?? $fotografia->arquivos->firstWhere('versao_arquivo', 'thumbnail');
        $imagemUrl = route('publico.imagens.show', $imagem);
        $data = $fotografia->dataHistorica()->label();
        $categoriaPrincipal = $fotografia->categorias->first()?->titulo;
        // Primeira frase da legenda, como subtítulo curto no hero.
        $primeiraLinha = trim(strtok((string) $fotografia->legenda, "\n") ?: '');
        $resumo = $primeiraLinha === '' ? null : Str::limit(preg_split('/(?<=[.!?])\s+/u', $primeiraLinha)[0], 160);
        $metadados = array_filter([
            'Data' => $data,
            'Precisão da data' => $fotografia->tipo_data->label(),
            'Local atual' => $fotografia->local_atual,
            'Local na época' => $fotografia->local_epoca,
            'Evento' => $fotografia->evento,
            'Cedente' => $fotografia->cedente,
            'Autoria' => $fotografia->autor?->nome,
        ]);
        $classificacoes = array_filter([
            'Categorias' => $fotografia->categorias->pluck('titulo'),
            'Assuntos' => $fotografia->assuntos->pluck('titulo'),
            'Palavras-chave' => $fotografia->palavrasChave->pluck('termo'),
        ], fn ($itens) => $itens->isNotEmpty());
        $contextos = array_filter([
            'Coleções' => $fotografia->colecoes->pluck('titulo'),
            'Conjuntos contextuais' => $fotografia->conjuntosContextuais->pluck('titulo'),
        ], fn ($itens) => $itens->isNotEmpty());
        $glassButton = 'inline-flex min-h-11 items-center gap-2 rounded-full bg-white/12 px-4 text-[0.82rem] font-semibold text-white ring-1 ring-white/30 backdrop-blur-sm transition-colors hover:bg-white/22 focus-visible:outline-white';
        $textButton = 'inline-flex min-h-11 items-center gap-1.5 px-1 text-[0.82rem] font-semibold text-institutional underline decoration-mist-500 underline-offset-4 hover:decoration-institutional disabled:cursor-not-allowed disabled:opacity-50';
        $recordRow = 'border-t border-mist-200 py-2.5';
        $viewerButton = 'grid size-11 place-items-center rounded-md border border-night-500 text-[1.6rem] text-white hover:bg-night-700 focus-visible:outline-white focus-visible:outline-offset-2';
    @endphp

    <div x-data="publicPhotoViewer()">
        {{-- Abertura: a própria fotografia recortada, com gradiente escuro para o texto. --}}
        <section class="relative isolate overflow-hidden bg-institutional" aria-labelledby="fotografia-titulo">
            <img
                data-photo
                src="{{ $imagemUrl }}"
                alt=""
                class="absolute inset-0 -z-20 size-full object-cover"
                x-show="available"
                x-on:error="available = false"
            >
            <div class="absolute inset-0 -z-10 bg-linear-to-b from-night-950/55 via-night-950/20 via-35% to-night-950/90" aria-hidden="true"></div>

            <x-public.container class="flex min-h-[max(18rem,42vh)] flex-col justify-between gap-10 py-5 text-white md:min-h-[clamp(22rem,52vh,36rem)] md:py-7">
                <div class="flex items-center justify-between gap-3">
                    <a href="{{ $retornoCatalogo }}" class="{{ $glassButton }}"><span aria-hidden="true">&larr;</span> Voltar ao catálogo</a>
                    <button type="button" class="{{ $glassButton }}" @click="open()" x-show="available" aria-haspopup="dialog" aria-label="Ver fotografia em tela cheia">
                        <span aria-hidden="true">&#x26F6;</span> <span class="hidden sm:inline">Tela cheia</span>
                    </button>
                </div>

                <div class="max-w-[52rem]">
                    <p class="text-[0.72rem] font-bold tracking-[0.14em] text-white/80 uppercase">
                        Fotografia do acervo @if ($categoriaPrincipal) <span aria-hidden="true">·</span> {{ $categoriaPrincipal }} @endif
                    </p>
                    <h1 id="fotografia-titulo" class="mt-3 font-serif text-[length:clamp(2.1rem,5.2vw,4.25rem)] leading-[1.04] font-medium tracking-[-0.015em] text-balance wrap-anywhere">{{ $fotografia->titulo }}</h1>
                    <p class="mt-3 text-[0.95rem] font-semibold text-white/90">{{ $data }}</p>
                    @if ($resumo)
                        <p class="mt-2 max-w-[40rem] text-[1rem] leading-[1.6] text-white/80">{{ $resumo }}</p>
                    @endif
                </div>
            </x-public.container>
        </section>

        {{-- Consulta: imagem completa como documento, ao lado das informações do registro. --}}
        <section class="bg-white py-10 md:py-14">
            <x-public.container class="grid grid-cols-1 items-start gap-10 lg:grid-cols-[minmax(0,1.45fr)_minmax(20rem,1fr)] lg:gap-14">
                <figure class="min-w-0">
                    <div class="mx-auto w-fit max-w-full bg-mist-150 p-2">
                        <button
                            type="button"
                            class="relative block cursor-zoom-in focus-visible:outline-offset-4 disabled:cursor-not-allowed"
                            x-ref="trigger"
                            @click="open()"
                            :disabled="!available"
                            aria-haspopup="dialog"
                            aria-label="Ampliar fotografia: {{ $fotografia->titulo }}"
                            title="Ver imagem em tela cheia"
                        >
                            <img
                                data-photo
                                src="{{ $imagemUrl }}"
                                alt="{{ $fotografia->titulo }}"
                                @if ($imagem->width) width="{{ $imagem->width }}" @endif
                                @if ($imagem->height) height="{{ $imagem->height }}" @endif
                                loading="eager"
                                class="block h-auto max-h-[78vh] w-auto max-w-full"
                                x-show="available"
                                x-on:error="available = false"
                            >
                            <span x-show="!available" x-cloak class="grid aspect-4/3 w-[min(40rem,80vw)] place-items-center text-[0.9rem] text-ink-600">Imagem indisponível</span>
                            <span class="absolute right-3 bottom-3 grid size-10 place-items-center rounded-full bg-night-950/70 text-[1.2rem] text-white" x-show="available" aria-hidden="true">&#x26F6;</span>
                        </button>
                    </div>
                    <figcaption class="mt-2 flex flex-wrap items-center justify-between gap-x-4 text-[0.8rem] text-ink-500">
                        <span>Clique na imagem para ampliar.</span>
                        <span class="flex gap-4">
                            <button type="button" class="{{ $textButton }}" @click="open()" :disabled="!available">Ampliar</button>
                            <button type="button" class="{{ $textButton }}" @click="open(true)" :disabled="!available">Tamanho original</button>
                        </span>
                    </figcaption>
                </figure>

                <div class="grid gap-9 lg:sticky lg:top-8">
                    @if ($fotografia->legenda)
                        <x-public.record-section title="Descrição">
                            <p class="text-[1.02rem] leading-[1.75] whitespace-pre-line text-ink-800">{{ $fotografia->legenda }}</p>
                        </x-public.record-section>
                    @endif

                    <x-public.record-section title="Informações do registro">
                        <dl class="grid grid-cols-[minmax(7.5rem,auto)_1fr] border-b border-mist-200">
                            @foreach ($metadados as $rotulo => $valor)
                                <dt class="{{ $recordRow }} pr-4 text-[0.82rem] text-ink-500">{{ $rotulo }}</dt>
                                <dd class="{{ $recordRow }} text-[0.92rem] wrap-anywhere text-ink-950">{{ $valor }}</dd>
                            @endforeach
                        </dl>
                    </x-public.record-section>

                    @if ($classificacoes)
                        <x-public.record-section title="Classificações">
                            <x-public.record-groups :groups="$classificacoes" />
                        </x-public.record-section>
                    @endif

                    @if ($fotografia->pessoas->isNotEmpty())
                        <x-public.record-section title="Pessoas identificadas">
                            <x-public.tag-list :items="$fotografia->pessoas->pluck('nome')" />
                        </x-public.record-section>
                    @endif

                    @if ($contextos)
                        <x-public.record-section title="Coleções e contextos">
                            <x-public.record-groups :groups="$contextos" />
                        </x-public.record-section>
                    @endif
                </div>
            </x-public.container>
        </section>

        <dialog class="fixed inset-0 m-0 h-dvh max-h-none w-screen max-w-none bg-night-950 p-0 text-white backdrop:bg-night-950 open:flex open:flex-col" x-ref="viewer" aria-labelledby="viewer-title" @close="restore()">
            <div class="flex min-h-16 flex-none items-center justify-between gap-4 border-b border-night-700 px-4 pt-[max(0.5rem,env(safe-area-inset-top))] pb-2">
                <h2 id="viewer-title" class="m-0 max-h-16 min-w-0 overflow-y-auto text-[1rem] leading-[1.4] font-semibold wrap-anywhere text-white">{{ $fotografia->titulo }}</h2>
                <div class="flex flex-none gap-2">
                    <button type="button" class="{{ $viewerButton }}" @click="zoomed = !zoomed" :aria-pressed="zoomed" :aria-label="zoomed ? 'Ajustar imagem à tela' : 'Ver imagem no tamanho original'" :title="zoomed ? 'Ajustar à tela' : 'Tamanho original'"><span aria-hidden="true" x-text="zoomed ? '−' : '+'">+</span></button>
                    <button type="button" class="{{ $viewerButton }}" x-ref="close" @click="close()" aria-label="Fechar visualização" title="Fechar (Esc)"><span aria-hidden="true">&times;</span></button>
                </div>
            </div>
            <div class="group/viewport min-h-0 flex-1 overflow-auto overscroll-contain" :data-zoomed="zoomed">
                <div class="grid size-full place-items-center p-4 group-data-zoomed/viewport:size-max group-data-zoomed/viewport:min-h-full group-data-zoomed/viewport:min-w-full">
                    <img src="{{ $imagemUrl }}" alt="{{ $fotografia->titulo }}" @if ($imagem->width) width="{{ $imagem->width }}" @endif @if ($imagem->height) height="{{ $imagem->height }}" @endif loading="lazy" class="h-auto max-h-[calc(100dvh-8rem)] w-auto max-w-full object-contain group-data-zoomed/viewport:max-h-none group-data-zoomed/viewport:max-w-none">
                </div>
            </div>
        </dialog>
    </div>
</x-layouts.public>
