<x-layouts.admin title="{{ $fotografia->titulo }} | Memórias Vivas">
    <div class="mx-auto max-w-7xl px-6 py-8">
        <section class="mb-6 flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
            <div>
                <a
                    href="{{ route('admin.fotografias.index') }}"
                    class="text-sm font-semibold text-[#173F35] hover:text-[#0f2b24]"
                >
                    Voltar para fotografias
                </a>
                <p class="mt-4 text-sm font-medium text-[#6B5E2E]">Fotografia #{{ $fotografia->id }}</p>
                <h1 class="mt-2 max-w-4xl text-3xl font-semibold text-stone-950">{{ $fotografia->titulo }}</h1>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span class="inline-flex rounded-full bg-stone-100 px-2.5 py-1 text-xs font-semibold text-stone-700">
                        {{ $fotografia->statusLabel() }}
                    </span>
                    <span class="inline-flex rounded-full bg-[#E8E2C9] px-2.5 py-1 text-xs font-semibold text-[#4A3F18]">
                        {{ $fotografia->visibilidade->label() }}
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                @can('update', $fotografia)
                    <a
                        href="{{ route('admin.fotografias.edit', $fotografia) }}"
                        class="inline-flex items-center justify-center rounded-md border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2"
                    >
                        Editar
                    </a>
                @endcan

                @can('delete', $fotografia)
                    <form method="POST" action="{{ route('admin.fotografias.destroy', $fotografia) }}" onsubmit="return confirm('Excluir esta fotografia?')">
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

        @if (session('success'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
            <div class="space-y-6">
                <section id="dados-catalogacao" class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-semibold text-stone-950">Dados de catalogação</h2>

                    <dl class="mt-5 grid gap-5 md:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Título</dt>
                            <dd class="mt-1 text-sm text-stone-700">{{ $fotografia->titulo }}</dd>
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
                        <p class="mt-1 whitespace-pre-line text-sm leading-6 text-stone-700">{{ $fotografia->legenda ?: 'Não informado' }}</p>
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
                            <dd class="mt-1 text-sm text-stone-700">
                                @if ($fotografia->pessoas->isEmpty())
                                    Nenhuma pessoa vinculada.
                                @else
                                    {{ $fotografia->pessoas->pluck('nome')->join(', ') }}
                                @endif
                            </dd>
                        </div>
                    </dl>
                </section>
            </div>

            <aside class="space-y-6">
                <section class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-semibold text-stone-950">Auditoria</h2>

                    <dl class="mt-5 space-y-5">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Criado em</dt>
                            <dd class="mt-1 text-sm text-stone-700">{{ $fotografia->created_at?->format('d/m/Y H:i') ?: 'Não registrado' }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Criado por</dt>
                            <dd class="mt-1 text-sm text-stone-700">{{ $fotografia->criadoPor?->nome ?: 'Não registrado' }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Última alteração</dt>
                            <dd class="mt-1 text-sm text-stone-700">{{ $fotografia->updated_at?->format('d/m/Y H:i') ?: 'Não registrada' }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Alterado por</dt>
                            <dd class="mt-1 text-sm text-stone-700">{{ $fotografia->atualizadoPor?->nome ?: 'Não registrado' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-semibold text-stone-950">Arquivos</h2>
                    @php($arquivoOriginal = $fotografia->arquivos->firstWhere('versao_arquivo', 'original'))
                    @php($previewAdministrativa = $fotografia->visualizacaoAdministrativa())

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

                    @if ($arquivoOriginal)
                        @can('replaceOriginal', $arquivoOriginal)
                            <form
                                method="POST"
                                action="{{ route('admin.fotografias.replace-original', $fotografia) }}"
                                enctype="multipart/form-data"
                                class="mt-4 space-y-3 border-b border-stone-200 pb-5"
                                onsubmit="return confirm('Substituir o arquivo original desta fotografia? As versões otimizadas serão geradas novamente.')"
                            >
                                @csrf
                                @method('PUT')

                                <div>
                                    <label for="arquivo_original" class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">
                                        Substituir arquivo original
                                    </label>
                                    <input
                                        id="arquivo_original"
                                        name="arquivo_original"
                                        type="file"
                                        accept=".jpg,.jpeg,.png,.webp,.tif,.tiff,.pdf,image/jpeg,image/png,image/webp,image/tiff,application/pdf"
                                        required
                                        class="mt-2 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm text-stone-700 file:mr-4 file:rounded-md file:border-0 file:bg-[#173F35] file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-[#0f2b24] focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2"
                                    >
                                    <p class="mt-2 text-xs leading-5 text-stone-500">
                                        O novo arquivo será validado, substituirá o original atual e recriará as versões otimizadas.
                                    </p>
                                    @error('arquivo_original')
                                        <p class="mt-2 text-sm font-medium text-red-700">{{ $message }}</p>
                                    @enderror
                                </div>

                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center rounded-md border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2"
                                >
                                    Substituir arquivo
                                </button>
                            </form>
                        @endcan
                    @endif

                    @if ($fotografia->arquivos->isEmpty())
                        <p class="mt-3 text-sm leading-6 text-stone-600">Nenhum arquivo vinculado.</p>
                    @else
                        <ul class="mt-4 divide-y divide-stone-200">
                            @foreach ($fotografia->arquivos as $arquivo)
                                <li class="py-3">
                                    <p class="text-sm font-semibold text-stone-700">{{ $arquivo->nome_original ?: 'Arquivo derivado' }}</p>
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
