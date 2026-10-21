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
    $uploadMaxMb = number_format($uploadConfig['max_kb'] / 1024, 0, ',', '.');
    $arquivoOriginal = $fotografia?->arquivos?->firstWhere('versao_arquivo', 'original');
    $miniaturaOriginal = $arquivoOriginal ? $fotografia->imagemAdministrativa(['thumbnail', 'medium']) : null;
    $tamanhoOriginal = $arquivoOriginal === null ? null : ($arquivoOriginal->file_size >= 1024 * 1024
        ? number_format($arquivoOriginal->file_size / 1024 / 1024, 1, ',', '.').' MB'
        : number_format(max(1, $arquivoOriginal->file_size / 1024), 0, ',', '.').' KB');
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

    <x-catalog.section title="Dados principais" description="Comece pelas informações que identificam a fotografia.">
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

    <x-catalog.section title="Autoria" description="Selecione um autor existente ou cadastre um novo sem sair do formulário.">
        <livewire:admin.catalog-picker catalog="autor" :initial-selected="$selectedAutor" :key="'picker-autor-'.($fotografia?->id ?? 'new')" />
    </x-catalog.section>

    <x-catalog.section
        title="Data e contexto"
        description="Precisão da data, locais, evento e cedente."
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
    </x-catalog.section>

    <x-catalog.section title="Pessoas identificadas" description="Opcional. Indique somente quem aparece na imagem.">
        <livewire:admin.catalog-picker catalog="pessoa" :initial-selected="$selectedPessoas" :key="'picker-pessoa-'.($fotografia?->id ?? 'new')" />
    </x-catalog.section>

    <x-catalog.section title="Classificações" description="Categorias, assuntos e palavras-chave que ajudam a encontrar a fotografia.">
        <div class="grid gap-6">
            <livewire:admin.catalog-picker catalog="categoria" :initial-selected="$selectedCategorias" :key="'picker-categoria-'.($fotografia?->id ?? 'new')" />
            <livewire:admin.catalog-picker catalog="assunto" :initial-selected="$selectedAssuntos" :key="'picker-assunto-'.($fotografia?->id ?? 'new')" />
            <livewire:admin.catalog-picker catalog="palavra-chave" :initial-selected="$selectedPalavrasChave" :key="'picker-palavra-chave-'.($fotografia?->id ?? 'new')" />
        </div>
    </x-catalog.section>

    <x-catalog.section
        title="Arquivo original"
        :description="'Imagem ou PDF de até '.$uploadMaxMb.' MB.'"
    >
        @if ($arquivoOriginal)
            {{-- Na edição, o original atual é só exibido: a troca acontece na página da fotografia, com confirmação. --}}
            <div class="flex flex-wrap items-center gap-4 rounded-lg border border-mist-200 bg-mist-50 p-3">
                @if ($miniaturaOriginal)
                    <img src="{{ route('admin.arquivos.show', $miniaturaOriginal) }}" alt="Miniatura do arquivo original atual" class="size-20 flex-none rounded-md bg-mist-150 object-cover">
                @else
                    <span class="grid size-20 flex-none place-items-center rounded-md bg-institutional-soft text-institutional" aria-hidden="true">
                        <x-lucide-file-text class="size-8" />
                    </span>
                @endif
                <div class="grid min-w-0 flex-1 gap-0.5">
                    <span class="text-[0.7rem] font-semibold tracking-[0.08em] text-ink-500 uppercase">Arquivo atual</span>
                    <strong class="truncate text-[0.875rem] text-ink-950">{{ $arquivoOriginal->nome_original }}</strong>
                    <span class="text-[0.75rem] text-ink-500">{{ $arquivoOriginal->mime_type }} · {{ $tamanhoOriginal }}</span>
                </div>
                @can('replaceOriginal', $arquivoOriginal)
                    <x-catalog.button variant="secondary" size="sm" icon="refresh-cw" :href="route('admin.fotografias.show', $fotografia).'#substituir-arquivo-original'">Substituir na página da fotografia</x-catalog.button>
                @endcan
            </div>
        @else
            <x-catalog.file-dropzone
                name="arquivo_original"
                :mime-types="$uploadConfig['mime_types']"
                :extensions="$uploadConfig['extensions']"
                :max-kb="$uploadConfig['max_kb']"
            />
        @endif
        @error('arquivo_original') <x-catalog.error as="p">{{ $message }}</x-catalog.error> @enderror
    </x-catalog.section>

    <div class="flex justify-end gap-[0.7rem] rounded-lg border border-mist-200 bg-white/96 px-3 py-[0.8rem] shadow-[0_8px_24px_rgb(24_42_63/0.05)] sm:px-4">
        <x-catalog.button variant="secondary" :href="$cancelUrl">Cancelar</x-catalog.button>
        <x-catalog.button type="submit">{{ $submitLabel }}</x-catalog.button>
    </div>
</form>
