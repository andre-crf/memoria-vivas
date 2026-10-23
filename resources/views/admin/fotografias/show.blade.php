<x-layouts.admin title="{{ $fotografia->titulo }} | Memórias Vivas">
    <div class="mx-auto max-w-7xl px-6 py-8">
        <section class="mb-6 flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
            <div>
                <a
                    href="{{ route('admin.fotografias.index') }}"
                    class="text-sm font-semibold text-institutional hover:text-institutional-strong"
                >
                    Voltar para fotografias
                </a>
                <p class="mt-4 text-sm font-medium text-heritage-700">Fotografia #{{ $fotografia->id }}</p>
                <h1 class="mt-2 max-w-4xl break-words text-3xl font-semibold text-stone-950">{{ $fotografia->titulo }}</h1>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span class="inline-flex rounded-full bg-stone-100 px-2.5 py-1 text-xs font-semibold text-stone-700">
                        {{ $fotografia->statusLabel() }}
                    </span>
                    <span class="inline-flex rounded-full bg-heritage-100 px-2.5 py-1 text-xs font-semibold text-heritage-900">
                        {{ $fotografia->visibilidade->label() }}
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                @can('update', $fotografia)
                    <a
                        href="{{ route('admin.fotografias.edit', $fotografia) }}"
                        class="inline-flex items-center justify-center rounded-md border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-institutional focus:ring-offset-2"
                    >
                        Editar
                    </a>
                @endcan

                @can('delete', $fotografia)
                    <form
                        method="POST"
                        action="{{ route('admin.fotografias.destroy', $fotografia) }}"
                        data-confirm-title="Confirmar exclusão"
                        data-confirm-message="Excluir esta fotografia?"
                        data-confirm-label="Excluir fotografia"
                    >
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-md border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-700 focus:ring-offset-2"
                        >
                            Excluir
                        </button>
                    </form>
                @endcan
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
            <div class="space-y-6">
                <section id="dados-catalogacao" class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-semibold text-stone-950">Dados de catalogação</h2>

                    <dl class="mt-5 grid gap-3 md:grid-cols-2">
                        <x-admin.info-block label="Título">{{ $fotografia->titulo }}</x-admin.info-block>
                        <x-admin.info-block label="Data">{{ $fotografia->dataHistorica()->label() }}</x-admin.info-block>
                        <x-admin.info-block label="Local atual" :empty="! $fotografia->local_atual">{{ $fotografia->local_atual ?: 'Não informado' }}</x-admin.info-block>
                        <x-admin.info-block label="Local na época" :empty="! $fotografia->local_epoca">{{ $fotografia->local_epoca ?: 'Não informado' }}</x-admin.info-block>
                        <x-admin.info-block label="Localização física do original" :empty="! $fotografia->localizacao_fisica">{{ $fotografia->localizacao_fisica ?: 'Não informado' }}</x-admin.info-block>
                        <x-admin.info-block label="Evento relacionado" :empty="! $fotografia->evento">{{ $fotografia->evento ?: 'Não informado' }}</x-admin.info-block>
                        <x-admin.info-block label="Cedente" :empty="! $fotografia->cedente">{{ $fotografia->cedente ?: 'Não informado' }}</x-admin.info-block>
                        <x-admin.info-block label="Estado de conservação">{{ $fotografia->estadoConservacaoLabel() }}</x-admin.info-block>
                        <x-admin.info-block label="Precisão da data">{{ $fotografia->tipo_data->label() }}</x-admin.info-block>
                        <x-admin.info-block label="Legenda/descrição" :empty="! $fotografia->legenda" class="md:col-span-2">
                            <span class="block break-words whitespace-pre-line">{{ $fotografia->legenda ?: 'Não informado' }}</span>
                        </x-admin.info-block>
                    </dl>
                </section>

                <section class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-semibold text-stone-950">Classificações</h2>
                    @php
                        $classificacoes = [
                            ['Categorias', $fotografia->categorias->pluck('titulo'), 'Nenhuma categoria vinculada.'],
                            ['Assuntos', $fotografia->assuntos->pluck('titulo'), 'Nenhum assunto vinculado.'],
                            ['Palavras-chave', $fotografia->palavrasChave->pluck('termo'), 'Nenhuma palavra-chave vinculada.'],
                            ['Coleções', $fotografia->colecoes->pluck('titulo'), 'Nenhuma coleção vinculada.'],
                            [
                                'Conjuntos contextuais',
                                $fotografia->conjuntosContextuais->map(fn ($conjunto) => $conjunto->titulo.($conjunto->pivot->ordem !== null ? ' · Ordem '.$conjunto->pivot->ordem : '')),
                                'Nenhum conjunto contextual vinculado.',
                            ],
                        ];
                    @endphp

                    <div class="mt-5 grid gap-6 md:grid-cols-2">
                        @foreach ($classificacoes as [$rotulo, $itens, $vazio])
                            <div @class(['min-w-0', 'md:col-span-2' => $loop->last])>
                                <h3 class="text-[0.8rem] font-medium text-ink-600">{{ $rotulo }}</h3>
                                @if ($itens->isEmpty())
                                    <p class="mt-2 text-[0.85rem] text-ink-500">{{ $vazio }}</p>
                                @else
                                    <ul class="mt-2.5 flex flex-wrap gap-2">
                                        @foreach ($itens as $item)
                                            <li class="max-w-full rounded-full bg-mist-150 px-3 py-1 text-[0.8rem] font-medium wrap-anywhere text-ink-800">{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-semibold text-stone-950">Pessoas e autoria</h2>

                    <dl class="mt-5 grid gap-3 md:grid-cols-2">
                        <x-admin.info-block label="Autor" :empty="! $fotografia->autor?->nome">{{ $fotografia->autor?->nome ?: 'Não informado' }}</x-admin.info-block>
                        <x-admin.info-block label="Pessoas retratadas/relacionadas" :empty="$fotografia->pessoas->isEmpty()">
                            @if ($fotografia->pessoas->isEmpty())
                                Nenhuma pessoa vinculada.
                            @else
                                {{ $fotografia->pessoas->pluck('nome')->join(', ') }}
                            @endif
                        </x-admin.info-block>
                    </dl>
                </section>

                @if ($historicoAuditoria !== null)
                    <section class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm" aria-labelledby="photo-audit-history-title">
                        <div class="border-b border-stone-200 px-6 py-5">
                            <h2 id="photo-audit-history-title" class="text-2xl font-semibold text-stone-950">Histórico de auditoria</h2>
                            <p class="mt-1 text-sm text-stone-600">Eventos da fotografia e de seus arquivos digitais.</p>
                        </div>

                        @if ($historicoAuditoria->isEmpty())
                            <p class="px-6 py-8 text-sm text-stone-600">Nenhum evento de auditoria registrado para esta fotografia.</p>
                        @else
                            <ul class="divide-y divide-mist-200">
                                @foreach ($historicoAuditoria as $eventoAuditoria)
                                    <li class="flex flex-col justify-between gap-2 px-6 py-3.5 sm:flex-row sm:items-center sm:gap-6">
                                        <div class="min-w-0">
                                            <p class="text-[0.9rem] font-semibold wrap-anywhere text-ink-950">
                                                {{ $eventoAuditoria->action->label() }} · {{ $eventoAuditoria->subject_label ?: 'Sem identificação' }}
                                            </p>
                                            <p class="mt-0.5 text-[0.8rem] wrap-anywhere text-ink-500">
                                                <x-data-hora :valor="$eventoAuditoria->occurred_at" /> · {{ $eventoAuditoria->actor_name ?: 'Sistema' }} · {{ $eventoAuditoria->subject_type->label() }} #{{ $eventoAuditoria->subject_id }}
                                            </p>
                                        </div>
                                        <a
                                            href="{{ route('admin.auditoria.show', $eventoAuditoria) }}"
                                            class="shrink-0 self-start rounded text-[0.8rem] font-semibold text-institutional hover:text-institutional-strong hover:underline hover:underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-institutional sm:self-auto"
                                        >
                                            Ver detalhes <span aria-hidden="true">&rarr;</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>

                            @if ($historicoAuditoria->hasPages())
                                <div class="border-t border-stone-200 px-5 py-4">
                                    {{ $historicoAuditoria->links() }}
                                </div>
                            @endif
                        @endif
                    </section>
                @endif
            </div>

            <aside class="space-y-6">
                <section class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-semibold text-stone-950">Auditoria</h2>

                    @php
                        $registroAuditoria = [
                            ['Criado em', $fotografia->created_at, 'Não registrado', true],
                            ['Criado por', $fotografia->criadoPor?->nome, 'Não registrado', false],
                            ['Última alteração', $fotografia->updated_at, 'Não registrada', true],
                            ['Alterado por', $fotografia->atualizadoPor?->nome, 'Não registrado', false],
                        ];
                    @endphp
                    <dl class="mt-4 divide-y divide-mist-200">
                        @foreach ($registroAuditoria as [$rotulo, $valor, $vazio, $ehDataHora])
                            <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-0.5 py-2.5">
                                <dt class="text-[0.8rem] font-medium text-ink-600">{{ $rotulo }}</dt>
                                <dd @class(['text-right text-[0.9rem] wrap-anywhere', 'text-ink-500' => blank($valor), 'text-ink-950' => filled($valor)])>
                                    @if ($ehDataHora)
                                        <x-data-hora :valor="$valor" formato="curto" :vazio="$vazio" />
                                    @else
                                        {{ $valor ?: $vazio }}
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </section>

                <section class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
                    @php
                        $arquivoOriginal = $fotografia->arquivos->firstWhere('versao_arquivo', 'original');
                        $previewAdministrativa = $fotografia->visualizacaoAdministrativa();
                        $podeSubstituir = $arquivoOriginal && auth()->user()->can('replaceOriginal', $arquivoOriginal);
                    @endphp

                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-2xl font-semibold text-stone-950">Arquivos</h2>

                        @if ($podeSubstituir)
                            <div
                                class="relative"
                                x-data="{ open: false }"
                                @click.outside="open = false"
                                @keydown.escape="if (open) { open = false; $refs.actionsTrigger.focus() }"
                            >
                                <button
                                    type="button"
                                    id="arquivo-acoes-botao"
                                    x-ref="actionsTrigger"
                                    @click="open = !open"
                                    :aria-expanded="open"
                                    aria-expanded="false"
                                    aria-controls="arquivo-acoes"
                                    aria-label="Ações do arquivo"
                                    title="Ações do arquivo"
                                    class="grid size-9 cursor-pointer place-items-center rounded-md text-ink-600 transition-colors hover:bg-mist-150 hover:text-ink-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-institutional"
                                >
                                    <x-lucide-ellipsis class="size-5" aria-hidden="true" />
                                </button>

                                <div
                                    id="arquivo-acoes"
                                    x-show="open"
                                    x-cloak
                                    x-transition.origin.top.right
                                    class="absolute top-[calc(100%+0.35rem)] right-0 z-30 w-52 rounded-md border border-mist-200 bg-white p-1.5 shadow-[0_16px_30px_rgb(24_42_63/0.18)]"
                                >
                                    <button
                                        type="button"
                                        @click="open = false; $dispatch('replace-original-open')"
                                        class="flex min-h-10 w-full cursor-pointer items-center gap-2.5 rounded px-3 text-left text-[0.8125rem] font-semibold text-ink-700 hover:bg-institutional-soft hover:text-institutional focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-institutional"
                                    >
                                        <x-lucide-image-up class="size-4 flex-none" aria-hidden="true" />
                                        Substituir imagem
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="mt-4">
                        @if ($previewAdministrativa)
                            <figure>
                                <img
                                    src="{{ route('admin.arquivos.show', $previewAdministrativa) }}"
                                    alt="Visualização administrativa de {{ $fotografia->titulo }}"
                                    width="{{ $previewAdministrativa->width ?: 800 }}"
                                    height="{{ $previewAdministrativa->height ?: 600 }}"
                                    class="aspect-[4/3] w-full rounded-md bg-stone-100 object-contain"
                                >
                                <figcaption class="mt-2 text-xs text-stone-500">
                                    Prévia administrativa: versão {{ $previewAdministrativa->versao_arquivo }}.
                                </figcaption>
                            </figure>
                        @else
                            <div class="grid aspect-[4/3] w-full place-items-center rounded-md border border-dashed border-stone-300 bg-stone-50 p-6 text-center">
                                <div>
                                    <p class="text-sm font-semibold text-stone-700">Prévia indisponível</p>
                                    <p class="mt-1 text-xs leading-5 text-stone-500">
                                        Cadastre ou regenere versões otimizadas para visualizar a fotografia aqui.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>

                    @if ($podeSubstituir)
                        @php $uploadConfig = config('acervo.uploads.original'); @endphp

                        {{-- O próprio modal é a confirmação; o envio segue o fluxo tradicional (redirect com toast ou com o erro). --}}
                        <div
                            x-data="{
                                hasFile: false,
                                submitting: false,
                                open() {
                                    this.$refs.dialog.showModal();
                                },
                                close() {
                                    if (! this.submitting) this.$refs.dialog.close();
                                },
                                // Fechar sem enviar descarta a seleção; o original segue intacto.
                                restore() {
                                    if (this.submitting) return;
                                    Alpine.$data(this.$refs.dialog.querySelector('[data-dropzone]')).clear();
                                    // A opção do menu já fechou; o foco volta para o botão de ações.
                                    document.getElementById('arquivo-acoes-botao')?.focus();
                                },
                            }"
                            x-init="if (@js($errors->has('arquivo_original')) || location.hash === '#substituir-arquivo-original') open()"
                            @replace-original-open.window="open()"
                            @file-change="hasFile = $event.detail.file !== null"
                        >
                            <dialog
                                id="substituir-arquivo-original"
                                x-ref="dialog"
                                aria-labelledby="substituir-imagem-titulo"
                                aria-describedby="substituir-imagem-descricao"
                                @cancel="if (submitting) $event.preventDefault()"
                                @close="restore()"
                                class="fixed top-1/2 left-1/2 m-0 box-border max-h-[calc(100dvh-2rem)] w-[min(36rem,calc(100%-2rem))] -translate-x-1/2 -translate-y-1/2 rounded-lg bg-white p-0 text-ink-950 shadow-[0_24px_64px_rgb(15_23_42/0.28)] backdrop:bg-[rgb(15_23_42/0.55)] open:flex open:flex-col"
                            >
                                <form
                                    method="POST"
                                    action="{{ route('admin.fotografias.replace-original', $fotografia) }}"
                                    enctype="multipart/form-data"
                                    class="flex min-h-0 flex-col"
                                    @submit="submitting = true"
                                    :aria-busy="submitting"
                                >
                                    @csrf
                                    @method('PUT')

                                    <div class="flex items-start justify-between gap-4 border-b border-mist-200 px-5 py-4">
                                        <div class="min-w-0">
                                            <h2 id="substituir-imagem-titulo" class="text-[1.05rem] font-semibold">Substituir imagem</h2>
                                            <p id="substituir-imagem-descricao" class="mt-1 text-[0.8rem] leading-normal text-ink-600">Selecione um novo arquivo para substituir a imagem original deste registro.</p>
                                        </div>
                                        <button type="button" @click="close()" :disabled="submitting" aria-label="Fechar" title="Fechar" class="grid size-8 flex-none cursor-pointer place-items-center rounded-full text-ink-600 hover:bg-institutional-soft hover:text-ink-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-institutional disabled:cursor-wait disabled:opacity-55">
                                            <x-lucide-x class="size-4" aria-hidden="true" />
                                        </button>
                                    </div>

                                    <div class="min-h-0 flex-1 overflow-y-auto bg-mist-50 p-5">
                                        <x-catalog.file-dropzone
                                            data-dropzone
                                            name="arquivo_original"
                                            :mime-types="$uploadConfig['mime_types']"
                                            :extensions="$uploadConfig['extensions']"
                                            :max-kb="$uploadConfig['max_kb']"
                                        />
                                        <p class="mt-3 text-[0.75rem] leading-normal text-ink-500">As versões otimizadas serão geradas novamente a partir do novo arquivo.</p>
                                        @error('arquivo_original') <x-catalog.error as="p" role="alert">{{ $message }}</x-catalog.error> @enderror
                                    </div>

                                    <div class="flex flex-col-reverse gap-2 border-t border-mist-200 px-5 py-4 sm:flex-row sm:justify-end">
                                        <x-catalog.button variant="secondary" x-on:click="close()" x-bind:disabled="submitting">Cancelar</x-catalog.button>
                                        <x-catalog.button type="submit" disabled x-bind:disabled="!hasFile || submitting">
                                            <span x-text="submitting ? 'Substituindo…' : 'Confirmar substituição'">Confirmar substituição</span>
                                        </x-catalog.button>
                                    </div>
                                </form>
                            </dialog>
                        </div>
                    @endif

                    @if ($fotografia->arquivos->isEmpty())
                        <p class="mt-4 text-[0.85rem] text-ink-500">Nenhum arquivo vinculado.</p>
                    @else
                        @php
                            $ordemVersoes = ['original', 'large', 'medium', 'thumbnail'];
                            $versoes = $fotografia->arquivos->sortBy(fn ($arquivo) => [
                                in_array($arquivo->versao_arquivo, $ordemVersoes, true) ? array_search($arquivo->versao_arquivo, $ordemVersoes, true) : count($ordemVersoes),
                                $arquivo->id,
                            ]);
                            $rotuloVersao = fn ($arquivo) => ['original' => 'Original', 'large' => 'Grande', 'medium' => 'Média', 'thumbnail' => 'Miniatura'][$arquivo->versao_arquivo] ?? Str::ucfirst($arquivo->versao_arquivo);
                            $formato = fn ($arquivo) => match ($subtipo = Str::after((string) $arquivo->mime_type, '/')) {
                                'webp' => 'WebP',
                                '' => 'Desconhecido',
                                default => Str::upper($subtipo),
                            };
                            $tamanho = fn ($arquivo) => $arquivo->file_size >= 1024 * 1024
                                ? number_format($arquivo->file_size / 1024 / 1024, 1, ',', '.').' MB'
                                : number_format($arquivo->file_size / 1024, 1, ',', '.').' KB';
                            $dimensoes = fn ($arquivo) => $arquivo->width && $arquivo->height ? "{$arquivo->width} × {$arquivo->height} px" : null;
                        @endphp

                        @if ($arquivoOriginal)
                            <div class="mt-4">
                                <p class="text-[0.9rem] font-semibold wrap-anywhere text-ink-950">{{ $arquivoOriginal->nome_original ?: 'Arquivo original' }}</p>
                                <p class="mt-0.5 text-[0.8rem] text-ink-500">
                                    {{ collect([$formato($arquivoOriginal), $tamanho($arquivoOriginal), $dimensoes($arquivoOriginal)])->filter()->join(' · ') }}
                                </p>
                            </div>
                        @endif

                        <h3 class="mt-5 text-[0.8rem] font-medium text-ink-600">Versões disponíveis</h3>
                        <ul class="mt-1 divide-y divide-mist-200">
                            @foreach ($versoes as $arquivo)
                                <li class="flex items-center gap-3 py-2.5">
                                    <span class="grid size-8 flex-none place-items-center rounded-md bg-mist-100 text-ink-600" aria-hidden="true">
                                        @if ($arquivo->mime_type === 'application/pdf')
                                            <x-lucide-file-text class="size-4" />
                                        @else
                                            <x-lucide-file-image class="size-4" />
                                        @endif
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-[0.85rem] font-semibold text-ink-950">{{ $rotuloVersao($arquivo) }}</span>
                                        <span class="block text-[0.75rem] text-ink-500">{{ $formato($arquivo) }} · {{ $dimensoes($arquivo) ?? '—' }}</span>
                                    </span>
                                    <span class="flex-none text-[0.8rem] text-ink-600 tabular-nums">{{ $tamanho($arquivo) }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <details class="group mt-3 border-t border-mist-200 pt-3">
                            <summary class="flex cursor-pointer list-none items-center gap-1.5 rounded text-[0.8rem] font-semibold text-ink-700 hover:text-institutional focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-institutional [&::-webkit-details-marker]:hidden">
                                <x-lucide-chevron-right class="size-4 transition-transform group-open:rotate-90 motion-reduce:transition-none" aria-hidden="true" />
                                Detalhes técnicos
                            </summary>
                            <div class="mt-3 grid gap-3">
                                @foreach ($versoes as $arquivo)
                                    <dl class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-3 gap-y-1 rounded-md bg-mist-50 px-3 py-2.5 text-[0.75rem]">
                                        <dt class="text-ink-500">Arquivo</dt>
                                        <dd class="font-semibold wrap-anywhere text-ink-950">{{ $arquivo->nome_original ?: 'Arquivo derivado' }}</dd>
                                        <dt class="text-ink-500">Versão</dt>
                                        <dd class="text-ink-800">{{ $rotuloVersao($arquivo) }} ({{ $arquivo->versao_arquivo }})</dd>
                                        <dt class="text-ink-500">Tipo e tamanho</dt>
                                        <dd class="wrap-anywhere text-ink-800">{{ $arquivo->mime_type }} · {{ $tamanho($arquivo) }} ({{ number_format($arquivo->file_size, 0, ',', '.') }} bytes)</dd>
                                        <dt class="text-ink-500">Dimensões</dt>
                                        <dd class="text-ink-800">{{ $dimensoes($arquivo) ?? 'Não aplicável' }}</dd>
                                        <dt class="text-ink-500">Armazenamento</dt>
                                        <dd class="font-mono wrap-anywhere text-ink-800">{{ $arquivo->provider }} · {{ $arquivo->storage_path }}</dd>
                                    </dl>
                                @endforeach
                            </div>
                        </details>
                    @endif
                </section>
            </aside>
        </div>
    </div>
</x-layouts.admin>
