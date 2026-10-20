@php
    $fotografia ??= null;
    $value = fn (string $field, mixed $default = '') => old($field, $fotografia?->{$field} ?? $default);
    $selectedTipoData = old('tipo_data', $fotografia?->tipo_data?->value ?? 'desconhecida');
    $selectedEstadoConservacao = old('estado_conservacao', $fotografia?->estado_conservacao ?? 'desconhecido');
    $selectedStatus = old('status', $fotografia?->status ?? 'rascunho');
    $selectedVisibilidade = old('visibilidade', $fotografia?->visibilidade?->value ?? 'privado');
    $selectedAutor = old('autor_id', $fotografia?->autor_id);
    $selectedCategorias = collect(old('categoria_ids', $fotografia?->categorias?->pluck('id')->all() ?? []))->all();
    $selectedAssuntos = collect(old('assunto_ids', $fotografia?->assuntos?->pluck('id')->all() ?? []))->all();
    $selectedPalavrasChave = collect(old('palavra_chave_ids', $fotografia?->palavrasChave?->pluck('id')->all() ?? []))->all();
    $selectedPessoas = collect(old('pessoa_ids', $fotografia?->pessoas?->pluck('id')->all() ?? []))->all();
    $legenda = (string) $value('legenda');
    $legendaLength = mb_strlen($legenda);
    $uploadConfig = config('acervo.uploads.original');
    $acceptedMimeTypes = implode(',', $uploadConfig['mime_types']);
    $acceptedExtensions = implode(', ', array_map(fn (string $extension) => ".{$extension}", $uploadConfig['extensions']));
    $uploadMaxMb = number_format($uploadConfig['max_kb'] / 1024, 0, ',', '.');
    $dateInputs = [
        'dia' => ['label' => 'Dia', 'type' => 'number', 'min' => 1, 'max' => 31],
        'mes' => ['label' => 'Mês', 'type' => 'number', 'min' => 1, 'max' => 12],
        'ano' => ['label' => 'Ano', 'type' => 'number', 'min' => 1000, 'max' => date('Y'), 'step' => 1],
        'decada' => ['label' => 'Década', 'type' => 'text', 'inputmode' => 'numeric', 'maxlength' => 4, 'pattern' => '[0-9]{4}'],
    ];
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="grid gap-4">
    @csrf
    <input type="hidden" name="classificacoes_enviadas" value="1">
    @isset($method)
        @method($method)
    @endisset

    <x-catalog.section eyebrow="Identificação" title="Dados principais" description="Comece pelas informações que identificam a fotografia.">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-catalog.label for="titulo" text="Título" required class="sm:col-span-full">
                <x-catalog.control id="titulo" name="titulo" type="text" :value="$value('titulo')" required maxlength="255" />
                @error('titulo') <x-catalog.error>{{ $message }}</x-catalog.error> @enderror
            </x-catalog.label>

            <x-catalog.label for="legenda" text="Legenda/descrição" class="sm:col-span-full" x-data="characterCounter({{ $legendaLength }}, 5000)">
                <x-catalog.control as="textarea" id="legenda" name="legenda" rows="3" maxlength="5000" x-on:input="update($event.target.value)">{{ $legenda }}</x-catalog.control>
                <span
                    class="justify-self-end text-[0.68rem] font-[650] text-ink-400 tabular-nums data-[limit=near]:text-warning data-[limit=over]:text-danger"
                    :data-limit="count > limit ? 'over' : (count >= limit * 0.9 ? 'near' : null)"
                    x-text="formattedCount"
                >{{ number_format($legendaLength, 0, ',', '.') }}/5.000</span>
                @error('legenda') <x-catalog.error>{{ $message }}</x-catalog.error> @enderror
            </x-catalog.label>

            <x-catalog.label for="estado_conservacao" text="Estado de conservação">
                <x-catalog.control as="select" id="estado_conservacao" name="estado_conservacao" required>
                    @foreach ($estadoConservacaoOptions as $optionValue => $label)
                        <option value="{{ $optionValue }}" @selected($selectedEstadoConservacao === $optionValue)>{{ $label }}</option>
                    @endforeach
                </x-catalog.control>
                @error('estado_conservacao') <x-catalog.error>{{ $message }}</x-catalog.error> @enderror
            </x-catalog.label>

            <x-catalog.label for="status" text="Status">
                <x-catalog.control as="select" id="status" name="status" required>
                    @foreach ($statusOptions as $optionValue => $label)
                        <option value="{{ $optionValue }}" @selected($selectedStatus === $optionValue)>{{ $label }}</option>
                    @endforeach
                </x-catalog.control>
                @error('status') <x-catalog.error>{{ $message }}</x-catalog.error> @enderror
            </x-catalog.label>

            <x-catalog.label for="visibilidade" text="Visibilidade">
                <x-catalog.control as="select" id="visibilidade" name="visibilidade" required>
                    @foreach ($visibilidadeOptions as $visibilidade)
                        <option value="{{ $visibilidade->value }}" @selected($selectedVisibilidade === $visibilidade->value)>{{ $visibilidade->label() }}</option>
                    @endforeach
                </x-catalog.control>
                @error('visibilidade') <x-catalog.error>{{ $message }}</x-catalog.error> @enderror
            </x-catalog.label>
        </div>
    </x-catalog.section>

    <x-catalog.section eyebrow="Autoria" title="Autor da fotografia" description="Selecione um autor existente ou cadastre um sem abandonar o formulário.">
        <livewire:admin.quick-catalog-create
            catalog="autor"
            :initial-options="$autorOptions"
            :initial-selected="$selectedAutor"
            :key="'quick-autor-'.($fotografia?->id ?? 'new')"
        />
    </x-catalog.section>

    <x-catalog.disclosure
        title="Data e contexto"
        subtitle="Precisão da data, locais, evento e cedente"
        :open="$errors->hasAny(['tipo_data', 'dia', 'mes', 'ano', 'decada', 'local_atual', 'local_epoca', 'evento', 'cedente'])"
        x-data="historicalDateFields({{ Js::from($selectedTipoData) }})"
    >
        <div class="mb-4 grid gap-4 sm:grid-cols-4 lg:grid-cols-[minmax(12rem,1.5fr)_repeat(4,minmax(5rem,0.65fr))]">
            <x-catalog.label for="tipo_data" text="Precisão da data" class="sm:col-span-full lg:col-auto">
                <x-catalog.control as="select" id="tipo_data" name="tipo_data" required x-model="precision">
                    @foreach ($tipoDataOptions as $tipoData)
                        <option value="{{ $tipoData->value }}" @selected($selectedTipoData === $tipoData->value)>{{ $tipoData->label() }}</option>
                    @endforeach
                </x-catalog.control>
                @error('tipo_data') <x-catalog.error>{{ $message }}</x-catalog.error> @enderror
            </x-catalog.label>
            @foreach ($dateInputs as $field => $input)
                <x-catalog.label :for="$field" :text="$input['label']" x-show="isVisible('{{ $field }}')">
                    <x-catalog.control
                        :id="$field"
                        :name="$field"
                        :data-date-field="$field"
                        :type="$input['type']"
                        :value="$value($field)"
                        :min="$input['min'] ?? null"
                        :max="$input['max'] ?? null"
                        :step="$input['step'] ?? null"
                        :inputmode="$input['inputmode'] ?? null"
                        :maxlength="$input['maxlength'] ?? null"
                        :pattern="$input['pattern'] ?? null"
                        ::disabled="!isVisible('{{ $field }}')"
                    />
                    @error($field) <x-catalog.error>{{ $message }}</x-catalog.error> @enderror
                </x-catalog.label>
            @endforeach
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach (['local_atual' => 'Local atual', 'local_epoca' => 'Local na época', 'evento' => 'Evento relacionado', 'cedente' => 'Cedente'] as $field => $label)
                <x-catalog.label :for="$field" :text="$label">
                    <x-catalog.control :id="$field" :name="$field" type="text" :value="$value($field)" maxlength="255" />
                    @error($field) <x-catalog.error>{{ $message }}</x-catalog.error> @enderror
                </x-catalog.label>
            @endforeach
        </div>
    </x-catalog.disclosure>

    <x-catalog.disclosure
        title="Pessoas identificadas"
        :subtitle="'Opcional · '.count($selectedPessoas).' selecionada(s)'"
        :open="count($selectedPessoas) || $errors->has('pessoa_ids') || $errors->has('pessoa_ids.*')"
    >
        @include('admin.fotografias._relationship_picker', [
            'name' => 'pessoa_ids', 'label' => 'Pessoas', 'description' => 'Marque somente quem foi identificado na imagem.',
            'options' => $pessoaOptions, 'selected' => $selectedPessoas, 'valueField' => 'id', 'labelField' => 'nome',
            'searchPlaceholder' => 'Buscar pessoa...', 'emptyMessage' => 'Nenhuma pessoa cadastrada.',
        ])
    </x-catalog.disclosure>

    <x-catalog.disclosure
        title="Classificações"
        subtitle="Categorias, assuntos e palavras-chave"
        :open="count($selectedCategorias) || count($selectedAssuntos) || count($selectedPalavrasChave) || $errors->hasAny(['categoria_ids', 'categoria_ids.*', 'assunto_ids', 'assunto_ids.*', 'palavra_chave_ids', 'palavra_chave_ids.*'])"
        class="grid gap-4 lg:grid-cols-3"
    >
        <livewire:admin.quick-catalog-create catalog="categoria" :initial-options="$categoriaOptions" :initial-selected="$selectedCategorias" :key="'quick-categoria-'.($fotografia?->id ?? 'new')" />
        <livewire:admin.quick-catalog-create catalog="assunto" :initial-options="$assuntoOptions" :initial-selected="$selectedAssuntos" :key="'quick-assunto-'.($fotografia?->id ?? 'new')" />
        <livewire:admin.quick-catalog-create catalog="palavra-chave" :initial-options="$palavraChaveOptions" :initial-selected="$selectedPalavrasChave" :key="'quick-palavra-chave-'.($fotografia?->id ?? 'new')" />
    </x-catalog.disclosure>

    <x-catalog.disclosure
        title="Arquivo original"
        :subtitle="'Imagem ou PDF de até '.$uploadMaxMb.' MB'"
        :open="$errors->has('arquivo_original')"
        x-data="filePreview()"
    >
        <p class="mt-[0.2rem] text-[0.72rem] leading-[1.45] text-ink-500">Formatos aceitos: {{ $acceptedExtensions }}.</p>
        @if ($fotografia?->arquivos?->firstWhere('versao_arquivo', 'original'))
            @php($arquivoOriginal = $fotografia->arquivos->firstWhere('versao_arquivo', 'original'))
            <div class="mt-3 grid gap-[0.2rem] border-l-[3px] border-institutional bg-institutional-soft p-[0.8rem] text-[0.78rem] text-ink-800"><strong>{{ $arquivoOriginal->nome_original }}</strong><span>{{ $arquivoOriginal->mime_type }} · {{ number_format($arquivoOriginal->file_size / 1024, 1, ',', '.') }} KB</span></div>
        @else
            <input
                id="arquivo_original"
                name="arquivo_original"
                type="file"
                accept="{{ $acceptedMimeTypes }}"
                class="mt-3 block w-full rounded-md border border-mist-300 bg-white p-2 text-[0.8rem] text-ink-800 file:mr-3 file:cursor-pointer file:rounded file:bg-institutional file:px-[0.7rem] file:py-2 file:font-[650] file:text-white"
                @change="selectFile($event)"
            >
            <div class="mt-3 flex items-center gap-3 rounded-lg border border-institutional-muted bg-mist-100 p-3" data-testid="file-preview" x-show="file !== null" style="display: none;">
                <div class="grid size-[4.5rem] flex-[0_0_4.5rem] place-items-center overflow-hidden rounded-md bg-institutional-soft text-[0.75rem] font-bold text-institutional">
                    <template x-if="isImage">
                        <img class="size-full object-cover" :src="previewUrl" :alt="`Pré-visualização de ${file.name}`" x-on:error="previewFailed = true" x-show="!previewFailed">
                    </template>
                    <template x-if="file !== null && !isImage">
                        <span>PDF</span>
                    </template>
                </div>
                <div class="grid min-w-0 gap-[0.2rem] *:truncate">
                    <strong class="text-[0.875rem] text-ink-950" x-text="file?.name"></strong>
                    <span class="text-[0.75rem] text-ink-500" x-text="metadata"></span>
                    <small class="text-[0.75rem] text-ink-500" x-text="status"></small>
                </div>
            </div>
        @endif
        @error('arquivo_original') <x-catalog.error as="p">{{ $message }}</x-catalog.error> @enderror
    </x-catalog.disclosure>

    <div class="flex justify-end gap-[0.7rem] rounded-lg border border-mist-200 bg-white/96 px-3 py-[0.8rem] shadow-[0_8px_24px_rgb(24_42_63/0.05)] sm:px-4">
        <a href="{{ $cancelUrl }}" class="inline-flex items-center rounded-md border border-mist-300 px-[0.9rem] py-[0.58rem] text-[0.78rem] font-[650] text-ink-600">Cancelar</a>
        <x-catalog.button type="submit">{{ $submitLabel }}</x-catalog.button>
    </div>
</form>
