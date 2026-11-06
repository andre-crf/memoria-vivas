<x-layouts.admin title="{{ $fotografia->titulo }} | Memórias Vivas">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-admin.page-header
            class="mb-6"
            :eyebrow="'Fotografia #'.$fotografia->id"
            :title="$fotografia->titulo"
            :back-href="route('admin.fotografias.index')"
            back-label="Voltar para fotografias"
        >
            <div class="flex flex-wrap gap-2">
                <x-admin.status-fotografia :fotografia="$fotografia" />
                <x-ui.badge :tone="$fotografia->visibilidade->isPublico() ? 'heritage' : 'neutral'">{{ $fotografia->visibilidade->label() }}</x-ui.badge>
            </div>

            <x-slot:actions>
                @can('update', $fotografia)
                    <x-catalog.button variant="secondary" icon="pencil" :href="route('admin.fotografias.edit', $fotografia)">Editar</x-catalog.button>
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
                        <x-catalog.button type="submit" variant="danger" icon="trash-2">Excluir</x-catalog.button>
                    </form>
                @endcan
            </x-slot:actions>
        </x-admin.page-header>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
            <div class="space-y-6">
                <x-catalog.section id="dados-catalogacao" title="Dados de catalogação">
                    <dl class="grid gap-3 md:grid-cols-2">
                        <x-admin.info-block label="Título">{{ $fotografia->titulo }}</x-admin.info-block>
                        <x-admin.info-block label="Código de referência" :empty="! $fotografia->codigo_referencia">{{ $fotografia->codigo_referencia ?: 'Não informado' }}</x-admin.info-block>
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
                </x-catalog.section>

                <x-catalog.section title="Classificações">
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

                    <div class="grid gap-6 md:grid-cols-2">
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
                </x-catalog.section>

                <x-catalog.section title="Pessoas e autoria">
                    <dl class="grid gap-3 md:grid-cols-2">
                        <x-admin.info-block label="Autor" :empty="! $fotografia->autor?->nome">{{ $fotografia->autor?->nome ?: 'Não informado' }}</x-admin.info-block>
                        <x-admin.info-block label="Pessoas retratadas/relacionadas" :empty="$fotografia->pessoas->isEmpty()">
                            @if ($fotografia->pessoas->isEmpty())
                                Nenhuma pessoa vinculada.
                            @else
                                {{ $fotografia->pessoas->pluck('nome')->join(', ') }}
                            @endif
                        </x-admin.info-block>
                    </dl>
                </x-catalog.section>

                @if ($historicoAuditoria !== null)
                    <x-catalog.section flush title="Histórico de auditoria" title-id="photo-audit-history-title" description="Eventos da fotografia e de seus arquivos digitais.">
                        @if ($historicoAuditoria->isEmpty())
                            <p class="px-5 py-8 text-sm text-fg-muted sm:px-6">Nenhum evento de auditoria registrado para esta fotografia.</p>
                        @else
                            <ul class="divide-y divide-mist-200">
                                @foreach ($historicoAuditoria as $eventoAuditoria)
                                    <li class="flex flex-col justify-between gap-2 px-5 py-3.5 sm:flex-row sm:items-center sm:gap-6 sm:px-6">
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
                                <div class="border-t border-line px-5 py-4">
                                    {{ $historicoAuditoria->links() }}
                                </div>
                            @endif
                        @endif
                    </x-catalog.section>
                @endif
            </div>

            <aside class="space-y-6">
                <x-catalog.section title="Auditoria">
                    @php
                        $registroAuditoria = [
                            ['Criado em', $fotografia->created_at, 'Não registrado', true],
                            ['Criado por', $fotografia->criadoPor?->nome, 'Não registrado', false],
                            ['Última alteração', $fotografia->updated_at, 'Não registrada', true],
                            ['Alterado por', $fotografia->atualizadoPor?->nome, 'Não registrado', false],
                        ];
                    @endphp
                    <dl class="-my-2.5 divide-y divide-mist-200">
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
                </x-catalog.section>

                <x-catalog.section title="Arquivos">
                    @php
                        $arquivoOriginal = $fotografia->arquivos->firstWhere('versao_arquivo', 'original');
                        $previewAdministrativa = $fotografia->visualizacaoAdministrativa();
                        $podeSubstituir = $arquivoOriginal && auth()->user()->can('replaceOriginal', $arquivoOriginal);
                        $originalEhImagem = $arquivoOriginal?->isImagem() ?? false;
                        // Versão esperada que falta é falha (ou geração não concluída). Versão que repetiria as
                        // dimensões de uma menor é dispensada, assim como todas quando o original não é imagem.
                        $geradorVersoes = app(\App\Support\OptimizedImageVersions::class);
                        $versoesExistentes = $fotografia->arquivos->pluck('versao_arquivo');
                        $versoesAusentes = $originalEhImagem
                            ? collect($geradorVersoes->expectedVersions($arquivoOriginal))->diff($versoesExistentes)->values()
                            : collect();
                        $versoesDispensadas = $originalEhImagem && $arquivoOriginal->width && $arquivoOriginal->height
                            ? collect($geradorVersoes->plan($arquivoOriginal->width, $arquivoOriginal->height)['dispensed'])->except($versoesExistentes->all())
                            : collect();
                        // Estado gravado no original (FILA-02/03). Pendente ou em processamento por mais que o
                        // tempo limite do job conta como sem progresso: a regeneração volta a ficar disponível.
                        $statusDerivacoes = $originalEhImagem ? $arquivoOriginal->derivacoes_status : null;
                        $derivacoesEmAndamento = $originalEhImagem && $arquivoOriginal->derivacoesEmAndamento();
                        $derivacoesSemProgresso = $originalEhImagem && $arquivoOriginal->derivacoesSemProgresso();
                        // [texto, tom do x-ui.badge, ícone]: os mesmos tons do Style Guide (seção Badges).
                        $seloDerivacoes = match (true) {
                            $derivacoesSemProgresso => ['Sem progresso', 'warning', 'triangle-alert'],
                            $statusDerivacoes === \App\Enums\StatusDerivacoes::Falhou => [$statusDerivacoes->label(), 'danger', 'circle-x'],
                            $derivacoesEmAndamento => [$statusDerivacoes->label(), 'info', $statusDerivacoes === \App\Enums\StatusDerivacoes::Processando ? 'loader-circle' : 'clock'],
                            $statusDerivacoes === \App\Enums\StatusDerivacoes::Concluido => [$statusDerivacoes->label(), 'success', 'circle-check'],
                            default => null,
                        };
                    @endphp

                    {{-- O menu de ações do arquivo vai sem alterações para o slot de ações do bloco. --}}
                    <x-slot:actions>
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
                                    class="admin-menu absolute top-[calc(100%+0.35rem)] right-0 z-30 w-52"
                                >
                                    <button
                                        type="button"
                                        @click="open = false; $dispatch('replace-original-open')"
                                        class="admin-menu-item"
                                    >
                                        <x-lucide-image-up class="size-4 flex-none" aria-hidden="true" />
                                        Substituir imagem
                                    </button>
                                    @if ($derivacoesEmAndamento)
                                        <button
                                            type="button"
                                            disabled
                                            aria-disabled="true"
                                            title="Aguarde a geração atual terminar e atualize a página."
                                            class="admin-menu-item"
                                        >
                                            <x-lucide-loader-circle class="size-4 flex-none" aria-hidden="true" />
                                            Geração em andamento
                                        </button>
                                    @elseif ($originalEhImagem)
                                        <form
                                            method="POST"
                                            action="{{ route('admin.fotografias.regenerate-versions', $fotografia) }}"
                                            data-confirm-title="Regenerar versões otimizadas"
                                            data-confirm-message="A miniatura e as versões média e grande serão recriadas a partir do original, que não é alterado. Se alguma versão falhar, a anterior é mantida."
                                            data-confirm-label="Regenerar versões"
                                            data-confirm-variant="primary"
                                        >
                                            @csrf
                                            <button
                                                type="submit"
                                                @click="open = false"
                                                class="admin-menu-item"
                                            >
                                                <x-lucide-refresh-cw class="size-4 flex-none" aria-hidden="true" />
                                                Regenerar versões
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </x-slot:actions>

                    <div>
                        @if ($previewAdministrativa)
                            <figure>
                                <img
                                    src="{{ route('admin.arquivos.show', $previewAdministrativa) }}"
                                    alt="Visualização administrativa de {{ $fotografia->titulo }}"
                                    width="{{ $previewAdministrativa->width ?: 800 }}"
                                    height="{{ $previewAdministrativa->height ?: 600 }}"
                                    class="aspect-[4/3] w-full rounded-md bg-mist-100 object-contain"
                                >
                                <figcaption class="mt-2 text-xs text-fg-subtle">
                                    Prévia administrativa: versão {{ $previewAdministrativa->versao_arquivo }}.
                                </figcaption>
                            </figure>
                        @else
                            <div class="grid aspect-[4/3] w-full place-items-center rounded-md border border-dashed border-mist-300 bg-surface-muted p-6 text-center">
                                <div>
                                    <p class="text-sm font-semibold text-ink-700">Prévia indisponível</p>
                                    <p class="mt-1 text-xs leading-5 text-fg-subtle">
                                        @if ($derivacoesEmAndamento)
                                            As versões otimizadas estão sendo geradas em segundo plano. Atualize a página em alguns instantes.
                                        @elseif ($originalEhImagem)
                                            As versões otimizadas não foram geradas. Use “Regenerar versões” no menu de ações do arquivo.
                                        @elseif ($arquivoOriginal)
                                            O arquivo original não é uma imagem, então não há versões otimizadas para exibir.
                                        @else
                                            Cadastre um arquivo original para visualizar a fotografia aqui.
                                        @endif
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
                                class="admin-dialog w-[min(36rem,calc(100%-2rem))] open:flex open:flex-col"
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

                                    <div class="flex items-start justify-between gap-4 border-b border-line px-5 py-4">
                                        <div class="min-w-0">
                                            <h2 id="substituir-imagem-titulo" class="text-section font-semibold">Substituir imagem</h2>
                                            <p id="substituir-imagem-descricao" class="mt-1 text-sm leading-6 text-fg-muted">Selecione um novo arquivo para substituir a imagem original deste registro.</p>
                                        </div>
                                        <x-catalog.button variant="ghost" size="sm" icon="x" class="-mt-1 -mr-2 flex-none" x-on:click="close()" x-bind:disabled="submitting" aria-label="Fechar" title="Fechar" />
                                    </div>

                                    <div class="min-h-0 flex-1 overflow-y-auto bg-surface-muted p-5">
                                        <x-catalog.file-dropzone
                                            data-dropzone
                                            name="arquivo_original"
                                            :mime-types="$uploadConfig['mime_types']"
                                            :extensions="$uploadConfig['extensions']"
                                            :max-kb="$uploadConfig['max_kb']"
                                        />
                                        <p class="mt-3 text-caption text-fg-subtle">As versões otimizadas serão geradas novamente a partir do novo arquivo.</p>
                                        @error('arquivo_original') <x-catalog.error as="p" role="alert">{{ $message }}</x-catalog.error> @enderror
                                    </div>

                                    <div class="flex flex-col-reverse gap-2 border-t border-line px-5 py-4 sm:flex-row sm:justify-end">
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

                        <div class="mt-5 flex flex-wrap items-center justify-between gap-2">
                            <h3 class="text-[0.8rem] font-medium text-ink-600">Versões disponíveis</h3>
                            @if ($seloDerivacoes)
                                <x-ui.badge data-status-derivacoes="{{ $derivacoesSemProgresso ? 'sem_progresso' : $statusDerivacoes->value }}" :tone="$seloDerivacoes[1]" :icon="$seloDerivacoes[2]">{{ $seloDerivacoes[0] }}</x-ui.badge>
                            @endif
                        </div>
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
                                        <span class="block text-[0.85rem] font-semibold text-ink-950">{{ \App\Models\Arquivo::rotuloVersao($arquivo->versao_arquivo) }}</span>
                                        <span class="block text-[0.75rem] text-ink-500">{{ $formato($arquivo) }} · {{ $dimensoes($arquivo) ?? '—' }}</span>
                                    </span>
                                    <span class="flex-none text-[0.8rem] text-ink-600 tabular-nums">{{ $tamanho($arquivo) }}</span>
                                </li>
                            @endforeach
                            @foreach ($versoesAusentes as $versao)
                                @if ($derivacoesEmAndamento)
                                    <li class="flex items-center gap-3 py-2.5" data-versao-pendente="{{ $versao }}">
                                        <span class="grid size-8 flex-none place-items-center rounded-md bg-institutional-soft text-institutional" aria-hidden="true">
                                            @if ($statusDerivacoes === \App\Enums\StatusDerivacoes::Processando)
                                                <x-lucide-loader-circle class="size-4" />
                                            @else
                                                <x-lucide-clock class="size-4" />
                                            @endif
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-[0.85rem] font-semibold text-ink-950">{{ \App\Models\Arquivo::rotuloVersao($versao) }}</span>
                                            <span class="block text-[0.75rem] text-ink-500">{{ $statusDerivacoes === \App\Enums\StatusDerivacoes::Processando ? 'Em geração' : 'Aguardando processamento' }}</span>
                                        </span>
                                    </li>
                                @else
                                    <li class="flex items-center gap-3 py-2.5" data-versao-ausente="{{ $versao }}">
                                        <span class="grid size-8 flex-none place-items-center rounded-md bg-error-soft text-danger" aria-hidden="true">
                                            <x-lucide-triangle-alert class="size-4" />
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-[0.85rem] font-semibold text-ink-950">{{ \App\Models\Arquivo::rotuloVersao($versao) }}</span>
                                            <span class="block text-[0.75rem] text-danger">Ausente: a geração falhou ou não foi concluída</span>
                                        </span>
                                    </li>
                                @endif
                            @endforeach
                            @foreach ($versoesDispensadas as $versao => $substituta)
                                <li class="flex items-center gap-3 py-2.5" data-versao-dispensada="{{ $versao }}">
                                    <span class="grid size-8 flex-none place-items-center rounded-md bg-mist-100 text-ink-500" aria-hidden="true">
                                        <x-lucide-copy-check class="size-4" />
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-[0.85rem] font-semibold text-ink-700">{{ \App\Models\Arquivo::rotuloVersao($versao) }}</span>
                                        <span class="block text-[0.75rem] text-ink-500">Dispensada: teria as mesmas dimensões da versão {{ \App\Models\Arquivo::rotuloVersao($substituta) }}</span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                        @if ($derivacoesEmAndamento)
                            <p class="mt-2 text-[0.75rem] leading-normal text-ink-600" role="note">
                                As versões estão sendo geradas em segundo plano. Atualize a página para ver o resultado.
                            </p>
                        @elseif ($derivacoesSemProgresso)
                            <p class="mt-2 text-[0.75rem] leading-normal text-ink-600" role="note">
                                O processamento não avançou nos últimos minutos.
                                @if ($podeSubstituir)
                                    Use “Regenerar versões” no menu de ações para tentar de novo.
                                @else
                                    Peça a regeneração das versões a um administrador.
                                @endif
                            </p>
                        @elseif ($versoesAusentes->isNotEmpty() || $statusDerivacoes === \App\Enums\StatusDerivacoes::Falhou)
                            <p class="mt-2 text-[0.75rem] leading-normal text-ink-600" role="note">
                                @if ($statusDerivacoes === \App\Enums\StatusDerivacoes::Falhou && $versoesAusentes->isEmpty())
                                    A última geração não foi concluída; as versões anteriores foram mantidas.
                                @endif
                                @if ($podeSubstituir)
                                    Use “Regenerar versões” no menu de ações para tentar de novo a partir do original.
                                @else
                                    Peça a regeneração das versões a um administrador.
                                @endif
                            </p>
                        @elseif ($arquivoOriginal && ! $originalEhImagem)
                            <p class="mt-2 text-[0.75rem] leading-normal text-ink-500" role="note">
                                Versões otimizadas dispensadas: o original não é uma imagem.
                            </p>
                        @endif

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
                                        <dd class="text-ink-800">{{ \App\Models\Arquivo::rotuloVersao($arquivo->versao_arquivo) }} ({{ $arquivo->versao_arquivo }})</dd>
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
                </x-catalog.section>
            </aside>
        </div>
    </div>
</x-layouts.admin>
