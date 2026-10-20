<x-layouts.public :title="$fotografia->titulo.' | Memórias Vivas'">
    @php
        $imagem = $fotografia->arquivos->firstWhere('versao_arquivo', 'large')
            ?? $fotografia->arquivos->firstWhere('versao_arquivo', 'medium')
            ?? $fotografia->arquivos->firstWhere('versao_arquivo', 'thumbnail');
        $metadados = array_filter([
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
        $viewerButton = 'grid size-11 place-items-center rounded-md border border-night-500 text-[1.6rem] text-white hover:bg-night-700 focus-visible:outline-white focus-visible:outline-offset-2';
    @endphp

    <x-public.section>
        <x-public.container>
            <x-public.back-link :href="$retornoCatalogo">&larr; Voltar ao catálogo</x-public.back-link>
            <div class="mt-8 grid grid-cols-1 gap-8 md:grid-cols-[minmax(0,1.25fr)_minmax(16rem,0.75fr)] md:gap-[clamp(2rem,6vw,5rem)]">
                <div class="min-w-0" x-data="publicPhotoViewer()">
                    <button type="button" class="relative grid min-h-88 w-full cursor-zoom-in place-items-center overflow-hidden bg-mist-250 text-ink-600 focus-visible:outline-offset-4 disabled:cursor-not-allowed" x-ref="trigger" @click="open()" :disabled="!available" aria-haspopup="dialog" aria-label="Ampliar fotografia: {{ $fotografia->titulo }}" title="Ver imagem em tela cheia">
                        <img
                            src="{{ route('publico.imagens.show', $imagem) }}"
                            alt="{{ $fotografia->titulo }}"
                            @if ($imagem->width) width="{{ $imagem->width }}" @endif
                            @if ($imagem->height) height="{{ $imagem->height }}" @endif
                            loading="eager"
                            class="size-full max-h-[42rem] object-contain"
                            x-on:error="available = false; $el.hidden = true"
                        >
                        <span x-show="!available">Imagem indisponível</span>
                        <span class="absolute right-3 bottom-3 grid size-11 place-items-center rounded-md bg-institutional text-[1.5rem] text-white" x-show="available" aria-hidden="true">&#x26F6;</span>
                    </button>
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
                                <img src="{{ route('publico.imagens.show', $imagem) }}" alt="{{ $fotografia->titulo }}" @if ($imagem->width) width="{{ $imagem->width }}" @endif @if ($imagem->height) height="{{ $imagem->height }}" @endif loading="lazy" class="h-auto max-h-[calc(100dvh-8rem)] w-auto max-w-full object-contain group-data-zoomed/viewport:max-h-none group-data-zoomed/viewport:max-w-none">
                            </div>
                        </div>
                    </dialog>
                </div>

                <div>
                    <x-public.eyebrow>Fotografia do acervo</x-public.eyebrow>
                    <x-public.heading as="h1" class="mt-[0.8rem] text-[length:clamp(2rem,5vw,4rem)] leading-[1.08]">{{ $fotografia->titulo }}</x-public.heading>
                    <p class="mt-4 font-[750] text-institutional">{{ $fotografia->dataHistorica()->label() }}</p>
                    @if ($fotografia->legenda)
                        <div class="mt-8 leading-[1.75] whitespace-pre-line text-ink-600">{{ $fotografia->legenda }}</div>
                    @endif
                    <dl class="mt-8 grid gap-4 border-t border-mist-200 pt-6 text-ink-600">
                        @foreach ($metadados as $rotulo => $valor)
                            <div><dt class="text-[0.72rem] font-extrabold tracking-[0.08em] uppercase">{{ $rotulo }}</dt><dd class="mt-1 text-institutional">{{ $valor }}</dd></div>
                        @endforeach
                    </dl>
                </div>
            </div>

            @if ($classificacoes || $fotografia->pessoas->isNotEmpty() || $contextos)
                <div class="mt-[clamp(2.5rem,6vw,4rem)] grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @if ($classificacoes)
                        <x-public.detail-section title="Classificações">
                            <x-public.detail-groups :groups="$classificacoes" />
                        </x-public.detail-section>
                    @endif

                    @if ($fotografia->pessoas->isNotEmpty())
                        <x-public.detail-section title="Pessoas identificadas">
                            <x-public.tag-list :items="$fotografia->pessoas->pluck('nome')" class="mt-5" />
                        </x-public.detail-section>
                    @endif

                    @if ($contextos)
                        <x-public.detail-section title="Coleções e contextos">
                            <x-public.detail-groups :groups="$contextos" />
                        </x-public.detail-section>
                    @endif
                </div>
            @endif
        </x-public.container>
    </x-public.section>
</x-layouts.public>
