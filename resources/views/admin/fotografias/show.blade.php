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

                    <dl class="mt-5 grid gap-5 md:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Título</dt>
                            <dd class="mt-1 break-words text-sm text-stone-700">{{ $fotografia->titulo }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Data</dt>
                            <dd class="mt-1 text-sm text-stone-700">{{ $fotografia->dataHistorica()->label() }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Local atual</dt>
                            <dd class="mt-1 text-sm text-stone-700">{{ $fotografia->local_atual ?: 'Não informado' }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Local na época</dt>
                            <dd class="mt-1 text-sm text-stone-700">{{ $fotografia->local_epoca ?: 'Não informado' }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Evento relacionado</dt>
                            <dd class="mt-1 text-sm text-stone-700">{{ $fotografia->evento ?: 'Não informado' }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Cedente</dt>
                            <dd class="mt-1 text-sm text-stone-700">{{ $fotografia->cedente ?: 'Não informado' }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Estado de conservação</dt>
                            <dd class="mt-1 text-sm text-stone-700">{{ $fotografia->estadoConservacaoLabel() }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Precisão da data</dt>
                            <dd class="mt-1 text-sm text-stone-700">{{ $fotografia->tipo_data->label() }}</dd>
                        </div>
                    </dl>

                    <div class="mt-5">
                        <h3 class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Legenda/descrição</h3>
                        <p class="mt-1 break-words whitespace-pre-line text-sm leading-6 text-stone-700">{{ $fotografia->legenda ?: 'Não informado' }}</p>
                    </div>
                </section>

                <section class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-semibold text-stone-950">Classificações</h2>

                    <div class="mt-5 grid gap-5 md:grid-cols-2">
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Categorias</h3>
                            @if ($fotografia->categorias->isEmpty())
                                <p class="mt-2 text-sm text-stone-600">Nenhuma categoria vinculada.</p>
                            @else
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($fotografia->categorias as $categoria)
                                        <span class="rounded-full bg-stone-100 px-2.5 py-1 text-xs font-medium text-stone-700">{{ $categoria->titulo }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Assuntos</h3>
                            @if ($fotografia->assuntos->isEmpty())
                                <p class="mt-2 text-sm text-stone-600">Nenhum assunto vinculado.</p>
                            @else
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($fotografia->assuntos as $assunto)
                                        <span class="rounded-full bg-stone-100 px-2.5 py-1 text-xs font-medium text-stone-700">{{ $assunto->titulo }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Palavras-chave</h3>
                            @if ($fotografia->palavrasChave->isEmpty())
                                <p class="mt-2 text-sm text-stone-600">Nenhuma palavra-chave vinculada.</p>
                            @else
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($fotografia->palavrasChave as $palavraChave)
                                        <span class="rounded-full bg-stone-100 px-2.5 py-1 text-xs font-medium text-stone-700">{{ $palavraChave->termo }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Coleções</h3>
                            @if ($fotografia->colecoes->isEmpty())
                                <p class="mt-2 text-sm text-stone-600">Nenhuma coleção vinculada.</p>
                            @else
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($fotografia->colecoes as $colecao)
                                        <span class="rounded-full bg-stone-100 px-2.5 py-1 text-xs font-medium text-stone-700">{{ $colecao->titulo }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="md:col-span-2">
                            <h3 class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Conjuntos contextuais</h3>
                            @if ($fotografia->conjuntosContextuais->isEmpty())
                                <p class="mt-2 text-sm text-stone-600">Nenhum conjunto contextual vinculado.</p>
                            @else
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($fotografia->conjuntosContextuais as $conjunto)
                                        <span class="rounded-full bg-stone-100 px-2.5 py-1 text-xs font-medium text-stone-700">
                                            {{ $conjunto->titulo }}@if ($conjunto->pivot->ordem !== null) · Ordem {{ $conjunto->pivot->ordem }} @endif
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </section>

                <section class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-semibold text-stone-950">Pessoas e autoria</h2>

                    <dl class="mt-5 grid gap-5 md:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Autor</dt>
                            <dd class="mt-1 text-sm text-stone-700">{{ $fotografia->autor?->nome ?: 'Não informado' }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Pessoas retratadas/relacionadas</dt>
                            <dd class="mt-1 break-words text-sm text-stone-700">
                                @if ($fotografia->pessoas->isEmpty())
                                    Nenhuma pessoa vinculada.
                                @else
                                    {{ $fotografia->pessoas->pluck('nome')->join(', ') }}
                                @endif
                            </dd>
                        </div>
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
                            <ul class="divide-y divide-stone-200">
                                @foreach ($historicoAuditoria as $eventoAuditoria)
                                    <li class="flex flex-col justify-between gap-3 px-6 py-4 sm:flex-row sm:items-center">
                                        <div>
                                            <p class="text-sm font-semibold text-stone-950">
                                                {{ $eventoAuditoria->action->label() }} · {{ $eventoAuditoria->subject_label ?: 'Sem identificação' }}
                                            </p>
                                            <p class="mt-1 text-xs text-stone-500">
                                                <x-data-hora :valor="$eventoAuditoria->occurred_at" /> · {{ $eventoAuditoria->actor_name ?: 'Sistema' }} · {{ $eventoAuditoria->subject_type->label() }} #{{ $eventoAuditoria->subject_id }}
                                            </p>
                                        </div>
                                        <a
                                            href="{{ route('admin.auditoria.show', $eventoAuditoria) }}"
                                            class="shrink-0 text-sm font-semibold text-institutional hover:text-institutional-strong"
                                        >
                                            Ver detalhes
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

                    <dl class="mt-5 space-y-5">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Criado em</dt>
                            <dd class="mt-1 text-sm text-stone-700"><x-data-hora :valor="$fotografia->created_at" formato="curto" vazio="Não registrado" /></dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Criado por</dt>
                            <dd class="mt-1 text-sm text-stone-700">{{ $fotografia->criadoPor?->nome ?: 'Não registrado' }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Última alteração</dt>
                            <dd class="mt-1 text-sm text-stone-700"><x-data-hora :valor="$fotografia->updated_at" formato="curto" vazio="Não registrada" /></dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Alterado por</dt>
                            <dd class="mt-1 text-sm text-stone-700">{{ $fotografia->atualizadoPor?->nome ?: 'Não registrado' }}</dd>
                        </div>
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
                        <p class="mt-3 text-sm leading-6 text-stone-600">Nenhum arquivo vinculado.</p>
                    @else
                        <ul class="mt-4 divide-y divide-stone-200">
                            @foreach ($fotografia->arquivos as $arquivo)
                                <li class="py-3">
                                    <p class="break-words text-sm font-semibold text-stone-700">{{ $arquivo->nome_original ?: 'Arquivo derivado' }}</p>
                                    <dl class="mt-2 grid gap-2 text-xs text-stone-600">
                                        <div>
                                            <dt class="font-semibold uppercase tracking-[0.12em] text-stone-500">Versão</dt>
                                            <dd>{{ $arquivo->versao_arquivo }}</dd>
                                        </div>
                                        <div>
                                            <dt class="font-semibold uppercase tracking-[0.12em] text-stone-500">Tipo e tamanho</dt>
                                            <dd>{{ $arquivo->mime_type }} · {{ number_format($arquivo->file_size / 1024, 1, ',', '.') }} KB</dd>
                                        </div>
                                        <div>
                                            <dt class="font-semibold uppercase tracking-[0.12em] text-stone-500">Dimensões</dt>
                                            <dd>
                                                @if ($arquivo->width && $arquivo->height)
                                                    {{ $arquivo->width }} × {{ $arquivo->height }} px
                                                @else
                                                    Não aplicável
                                                @endif
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="font-semibold uppercase tracking-[0.12em] text-stone-500">Armazenamento</dt>
                                            <dd>{{ $arquivo->provider }} · {{ $arquivo->storage_path }}</dd>
                                        </div>
                                    </dl>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            </aside>
        </div>
    </div>
</x-layouts.admin>
