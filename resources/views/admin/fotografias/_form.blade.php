@php
    $fotografia ??= null;

    $value = fn (string $field, mixed $default = '') => old($field, $fotografia?->{$field} ?? $default);
    $selectedTipoData = old('tipo_data', $fotografia?->tipo_data?->value ?? 'desconhecida');
    $selectedEstadoConservacao = old('estado_conservacao', $fotografia?->estado_conservacao ?? 'desconhecido');
    $selectedStatus = old('status', $fotografia?->status ?? 'rascunho');
    $selectedVisibilidade = old('visibilidade', $fotografia?->visibilidade?->value ?? 'privado');
    $selectedAutor = old('autor_id', $fotografia?->autor_id);
    $selectedCategorias = collect(old('categoria_ids', $fotografia?->categorias?->pluck('id')->all() ?? []))->map(fn ($id) => (string) $id)->all();
    $selectedAssuntos = collect(old('assunto_ids', $fotografia?->assuntos?->pluck('id')->all() ?? []))->map(fn ($id) => (string) $id)->all();
    $selectedPalavrasChave = collect(old('palavra_chave_ids', $fotografia?->palavrasChave?->pluck('id')->all() ?? []))->map(fn ($id) => (string) $id)->all();
    $selectedPessoas = collect(old('pessoa_ids', $fotografia?->pessoas?->pluck('id')->all() ?? []))->map(fn ($id) => (string) $id)->all();
    $originalUploadConfig = config('acervo.uploads.original');
    $acceptedOriginalMimeTypes = implode(',', $originalUploadConfig['mime_types']);
    $acceptedOriginalExtensions = implode(', ', array_map(fn (string $extension) => ".{$extension}", $originalUploadConfig['extensions']));
    $originalUploadMaxMb = number_format($originalUploadConfig['max_kb'] / 1024, 0, ',', '.');
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm" data-date-form>
    @csrf
    @isset($method)
        @method($method)
    @endisset

    <div class="grid gap-6">
        <div>
            <label for="titulo" class="block text-sm font-medium text-stone-900">Título</label>
            <input
                id="titulo"
                name="titulo"
                type="text"
                value="{{ $value('titulo') }}"
                required
                maxlength="255"
                class="mt-2 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 shadow-sm focus:border-[#173F35] focus:outline-none focus:ring-2 focus:ring-[#173F35]/20"
            >
            @error('titulo')
                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="legenda" class="block text-sm font-medium text-stone-900">Legenda/descrição</label>
            <textarea
                id="legenda"
                name="legenda"
                rows="4"
                class="mt-2 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 shadow-sm focus:border-[#173F35] focus:outline-none focus:ring-2 focus:ring-[#173F35]/20"
            >{{ $value('legenda') }}</textarea>
            @error('legenda')
                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-4 lg:grid-cols-[minmax(220px,0.8fr)_minmax(0,1.2fr)]">
            <div>
                <label for="tipo_data" class="block text-sm font-medium text-stone-900">Precisão da data</label>
                <select
                    id="tipo_data"
                    name="tipo_data"
                    required
                    class="mt-2 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 shadow-sm focus:border-[#173F35] focus:outline-none focus:ring-2 focus:ring-[#173F35]/20"
                >
                    @foreach ($tipoDataOptions as $tipoData)
                        <option value="{{ $tipoData->value }}" @selected($selectedTipoData === $tipoData->value)>
                            {{ $tipoData->label() }}
                        </option>
                    @endforeach
                </select>
                @error('tipo_data')
                    <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-4">
                @foreach (['dia' => 'Dia', 'mes' => 'Mês', 'ano' => 'Ano', 'decada' => 'Década'] as $field => $label)
                    <div data-date-for="{{ $field }}">
                        <label for="{{ $field }}" class="block text-sm font-medium text-stone-900">{{ $label }}</label>
                        <input
                            id="{{ $field }}"
                            name="{{ $field }}"
                            type="{{ $field === 'decada' ? 'text' : 'number' }}"
                            value="{{ $value($field) }}"
                            @if ($field === 'dia') min="1" max="31" @endif
                            @if ($field === 'mes') min="1" max="12" @endif
                            @if ($field === 'ano') min="1000" max="{{ date('Y') }}" @endif
                            @if ($field === 'decada') inputmode="numeric" maxlength="4" @endif
                            class="mt-2 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 shadow-sm focus:border-[#173F35] focus:outline-none focus:ring-2 focus:ring-[#173F35]/20"
                        >
                        @error($field)
                            <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            @foreach ([
                'local_atual' => 'Local atual',
                'local_epoca' => 'Local na época',
                'evento' => 'Evento relacionado',
                'cedente' => 'Cedente',
            ] as $field => $label)
                <div>
                    <label for="{{ $field }}" class="block text-sm font-medium text-stone-900">{{ $label }}</label>
                    <input
                        id="{{ $field }}"
                        name="{{ $field }}"
                        type="text"
                        value="{{ $value($field) }}"
                        maxlength="255"
                        class="mt-2 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 shadow-sm focus:border-[#173F35] focus:outline-none focus:ring-2 focus:ring-[#173F35]/20"
                    >
                    @error($field)
                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach
        </div>

        <div>
            <label for="autor_id" class="block text-sm font-medium text-stone-900">Autor</label>
            <select
                id="autor_id"
                name="autor_id"
                class="mt-2 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 shadow-sm focus:border-[#173F35] focus:outline-none focus:ring-2 focus:ring-[#173F35]/20"
            >
                <option value="">Sem autor associado</option>
                @foreach ($autorOptions as $autor)
                    <option value="{{ $autor->id }}" @selected((string) $selectedAutor === (string) $autor->id)>
                        {{ $autor->nome }} · {{ $autor->tipo === 'instituicao' ? 'Instituição' : 'Pessoa' }}
                    </option>
                @endforeach
            </select>
            @error('autor_id')
                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <section class="rounded-lg border border-stone-200 bg-stone-50 p-4">
            <h2 class="text-base font-semibold text-stone-950">Arquivo original</h2>
            <p class="mt-1 text-sm leading-6 text-stone-600">
                Envie imagem ou PDF até {{ $originalUploadMaxMb }} MB. Formatos aceitos: {{ $acceptedOriginalExtensions }}.
            </p>

            @if ($fotografia?->arquivos?->firstWhere('versao_arquivo', 'original'))
                @php($arquivoOriginal = $fotografia->arquivos->firstWhere('versao_arquivo', 'original'))
                <div class="mt-3 rounded-md border border-stone-200 bg-white p-3 text-sm text-stone-700">
                    <p class="font-medium text-stone-900">{{ $arquivoOriginal->nome_original }}</p>
                    <p class="mt-1 text-stone-600">
                        {{ $arquivoOriginal->mime_type }} · {{ number_format($arquivoOriginal->file_size / 1024, 1, ',', '.') }} KB
                    </p>
                    <p class="mt-1 text-stone-600">Arquivo original já vinculado.</p>
                </div>
            @else
                <input
                    id="arquivo_original"
                    name="arquivo_original"
                    type="file"
                    accept="{{ $acceptedOriginalMimeTypes }}"
                    class="mt-3 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 shadow-sm file:mr-4 file:rounded-md file:border-0 file:bg-[#173F35] file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-[#0f2b24] focus:border-[#173F35] focus:outline-none focus:ring-2 focus:ring-[#173F35]/20"
                >
            @endif

            @error('arquivo_original')
                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </section>

        <section class="rounded-lg border border-stone-200 bg-stone-50 p-4">
            <h2 class="text-base font-semibold text-stone-950">Pessoas identificadas</h2>
            <p class="mt-1 text-sm leading-6 text-stone-600">
                Vincule uma ou mais pessoas cadastradas quando elas forem identificadas na fotografia.
            </p>

            <div class="mt-3 max-h-56 space-y-2 overflow-y-auto rounded-md border border-stone-200 bg-white p-3">
                @forelse ($pessoaOptions as $pessoa)
                    <label class="flex items-start gap-2 text-sm text-stone-700">
                        <input
                            type="checkbox"
                            name="pessoa_ids[]"
                            value="{{ $pessoa->id }}"
                            @checked(in_array((string) $pessoa->id, $selectedPessoas, true))
                            class="mt-0.5 rounded border-stone-300 text-[#173F35] focus:ring-[#173F35]"
                        >
                        <span>{{ $pessoa->nome }}</span>
                    </label>
                @empty
                    <p class="text-sm text-stone-500">Nenhuma pessoa cadastrada.</p>
                @endforelse
            </div>
            @error('pessoa_ids')
                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('pessoa_ids.*')
                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </section>

        <section class="rounded-lg border border-stone-200 bg-stone-50 p-4">
            <h2 class="text-base font-semibold text-stone-950">Classificações</h2>
            <p class="mt-1 text-sm leading-6 text-stone-600">
                Selecione os vocabulários cadastrados que classificam esta fotografia.
            </p>

            <div class="mt-5 grid gap-5 lg:grid-cols-3">
                <div>
                    <h3 class="text-sm font-medium text-stone-900">Categorias</h3>
                    <div class="mt-3 max-h-56 space-y-2 overflow-y-auto rounded-md border border-stone-200 bg-white p-3">
                        @forelse ($categoriaOptions as $categoria)
                            <label class="flex items-start gap-2 text-sm text-stone-700">
                                <input
                                    type="checkbox"
                                    name="categoria_ids[]"
                                    value="{{ $categoria->id }}"
                                    @checked(in_array((string) $categoria->id, $selectedCategorias, true))
                                    class="mt-0.5 rounded border-stone-300 text-[#173F35] focus:ring-[#173F35]"
                                >
                                <span>{{ $categoria->titulo }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-stone-500">Nenhuma categoria cadastrada.</p>
                        @endforelse
                    </div>
                    @error('categoria_ids')
                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                    @error('categoria_ids.*')
                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <h3 class="text-sm font-medium text-stone-900">Assuntos</h3>
                    <div class="mt-3 max-h-56 space-y-2 overflow-y-auto rounded-md border border-stone-200 bg-white p-3">
                        @forelse ($assuntoOptions as $assunto)
                            <label class="flex items-start gap-2 text-sm text-stone-700">
                                <input
                                    type="checkbox"
                                    name="assunto_ids[]"
                                    value="{{ $assunto->id }}"
                                    @checked(in_array((string) $assunto->id, $selectedAssuntos, true))
                                    class="mt-0.5 rounded border-stone-300 text-[#173F35] focus:ring-[#173F35]"
                                >
                                <span>{{ $assunto->titulo }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-stone-500">Nenhum assunto cadastrado.</p>
                        @endforelse
                    </div>
                    @error('assunto_ids')
                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                    @error('assunto_ids.*')
                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <h3 class="text-sm font-medium text-stone-900">Palavras-chave</h3>
                    <div class="mt-3 max-h-56 space-y-2 overflow-y-auto rounded-md border border-stone-200 bg-white p-3">
                        @forelse ($palavraChaveOptions as $palavraChave)
                            <label class="flex items-start gap-2 text-sm text-stone-700">
                                <input
                                    type="checkbox"
                                    name="palavra_chave_ids[]"
                                    value="{{ $palavraChave->id }}"
                                    @checked(in_array((string) $palavraChave->id, $selectedPalavrasChave, true))
                                    class="mt-0.5 rounded border-stone-300 text-[#173F35] focus:ring-[#173F35]"
                                >
                                <span>{{ $palavraChave->termo }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-stone-500">Nenhuma palavra-chave cadastrada.</p>
                        @endforelse
                    </div>
                    @error('palavra_chave_ids')
                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                    @error('palavra_chave_ids.*')
                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </section>

        <div class="grid gap-4 md:grid-cols-3">
            <div>
                <label for="estado_conservacao" class="block text-sm font-medium text-stone-900">Estado de conservação</label>
                <select
                    id="estado_conservacao"
                    name="estado_conservacao"
                    required
                    class="mt-2 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 shadow-sm focus:border-[#173F35] focus:outline-none focus:ring-2 focus:ring-[#173F35]/20"
                >
                    @foreach ($estadoConservacaoOptions as $optionValue => $label)
                        <option value="{{ $optionValue }}" @selected($selectedEstadoConservacao === $optionValue)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('estado_conservacao')
                    <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-stone-900">Status</label>
                <select
                    id="status"
                    name="status"
                    required
                    class="mt-2 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 shadow-sm focus:border-[#173F35] focus:outline-none focus:ring-2 focus:ring-[#173F35]/20"
                >
                    @foreach ($statusOptions as $optionValue => $label)
                        <option value="{{ $optionValue }}" @selected($selectedStatus === $optionValue)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status')
                    <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="visibilidade" class="block text-sm font-medium text-stone-900">Visibilidade</label>
                <select
                    id="visibilidade"
                    name="visibilidade"
                    required
                    class="mt-2 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 shadow-sm focus:border-[#173F35] focus:outline-none focus:ring-2 focus:ring-[#173F35]/20"
                >
                    @foreach ($visibilidadeOptions as $visibilidade)
                        <option value="{{ $visibilidade->value }}" @selected($selectedVisibilidade === $visibilidade->value)>
                            {{ $visibilidade->label() }}
                        </option>
                    @endforeach
                </select>
                @error('visibilidade')
                    <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <div class="mt-8 flex flex-col-reverse gap-3 border-t border-stone-200 pt-6 sm:flex-row sm:justify-end">
        <a
            href="{{ $cancelUrl }}"
            class="inline-flex items-center justify-center rounded-md border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2"
        >
            Cancelar
        </a>
        <button
            type="submit"
            class="inline-flex items-center justify-center rounded-md bg-[#173F35] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0f2b24] focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2"
        >
            {{ $submitLabel }}
        </button>
    </div>
</form>

<script>
    (() => {
        const form = document.querySelector('[data-date-form]');
        const select = form?.querySelector('[name="tipo_data"]');
        const fieldsByPrecision = {
            data_exata: ['dia', 'mes', 'ano'],
            mes_ano: ['mes', 'ano'],
            ano: ['ano'],
            decada: ['decada'],
            desconhecida: [],
        };

        const updateDateFields = () => {
            const visibleFields = fieldsByPrecision[select.value] ?? [];

            form.querySelectorAll('[data-date-for]').forEach((field) => {
                const input = field.querySelector('input');
                const isVisible = visibleFields.includes(field.dataset.dateFor);

                field.hidden = !isVisible;
                input.disabled = !isVisible;
            });
        };

        select?.addEventListener('change', updateDateFields);
        updateDateFields();
    })();
</script>
