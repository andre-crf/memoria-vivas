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
    $fieldClass = 'catalog-field';
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="catalog-form">
    @csrf
    <input type="hidden" name="classificacoes_enviadas" value="1">
    @isset($method)
        @method($method)
    @endisset

    <section class="catalog-section">
        <div class="catalog-section__heading">
            <div>
                <p class="catalog-section__eyebrow">Identificação</p>
                <h2>Dados principais</h2>
            </div>
            <p>Comece pelas informações que identificam a fotografia.</p>
        </div>

        <div class="catalog-grid catalog-grid--two">
            <label class="catalog-label catalog-grid__wide" for="titulo">
                <span>Título <strong>obrigatório</strong></span>
                <input id="titulo" name="titulo" type="text" value="{{ $value('titulo') }}" required maxlength="255" class="{{ $fieldClass }}">
                @error('titulo') <small class="catalog-error">{{ $message }}</small> @enderror
            </label>

            <label class="catalog-label catalog-grid__wide" for="legenda" x-data="characterCounter({{ $legendaLength }}, 5000)">
                <span>Legenda/descrição</span>
                <textarea id="legenda" name="legenda" rows="3" maxlength="5000" class="{{ $fieldClass }}" @input="update($event.target.value)">{{ $legenda }}</textarea>
                <span
                    class="catalog-character-count"
                    :class="{ 'is-near-limit': count >= limit * 0.9, 'is-over-limit': count > limit }"
                    x-text="formattedCount"
                >{{ number_format($legendaLength, 0, ',', '.') }}/5.000</span>
                @error('legenda') <small class="catalog-error">{{ $message }}</small> @enderror
            </label>

            <label class="catalog-label" for="estado_conservacao">
                <span>Estado de conservação</span>
                <select id="estado_conservacao" name="estado_conservacao" required class="{{ $fieldClass }}">
                    @foreach ($estadoConservacaoOptions as $optionValue => $label)
                        <option value="{{ $optionValue }}" @selected($selectedEstadoConservacao === $optionValue)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('estado_conservacao') <small class="catalog-error">{{ $message }}</small> @enderror
            </label>

            <label class="catalog-label" for="status">
                <span>Status</span>
                <select id="status" name="status" required class="{{ $fieldClass }}">
                    @foreach ($statusOptions as $optionValue => $label)
                        <option value="{{ $optionValue }}" @selected($selectedStatus === $optionValue)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status') <small class="catalog-error">{{ $message }}</small> @enderror
            </label>

            <label class="catalog-label" for="visibilidade">
                <span>Visibilidade</span>
                <select id="visibilidade" name="visibilidade" required class="{{ $fieldClass }}">
                    @foreach ($visibilidadeOptions as $visibilidade)
                        <option value="{{ $visibilidade->value }}" @selected($selectedVisibilidade === $visibilidade->value)>{{ $visibilidade->label() }}</option>
                    @endforeach
                </select>
                @error('visibilidade') <small class="catalog-error">{{ $message }}</small> @enderror
            </label>
        </div>
    </section>

    <section class="catalog-section">
        <div class="catalog-section__heading">
            <div>
                <p class="catalog-section__eyebrow">Autoria</p>
                <h2>Autor da fotografia</h2>
            </div>
            <p>Selecione um autor existente ou cadastre um sem abandonar o formulário.</p>
        </div>

        <livewire:admin.quick-catalog-create
            catalog="autor"
            :initial-options="$autorOptions"
            :initial-selected="$selectedAutor"
            :key="'quick-autor-'.($fotografia?->id ?? 'new')"
        />
    </section>

    <details class="catalog-disclosure" @if($errors->hasAny(['tipo_data', 'dia', 'mes', 'ano', 'decada', 'local_atual', 'local_epoca', 'evento', 'cedente'])) open @endif>
        <summary><span><strong>Data e contexto</strong><small>Precisão da data, locais, evento e cedente</small></span></summary>
        <div class="catalog-disclosure__body" x-data="historicalDateFields(@js($selectedTipoData))">
            <div class="catalog-grid catalog-grid--date">
                <label class="catalog-label" for="tipo_data">
                    <span>Precisão da data</span>
                    <select id="tipo_data" name="tipo_data" required class="{{ $fieldClass }}" x-model="precision">
                        @foreach ($tipoDataOptions as $tipoData)
                            <option value="{{ $tipoData->value }}" @selected($selectedTipoData === $tipoData->value)>{{ $tipoData->label() }}</option>
                        @endforeach
                    </select>
                    @error('tipo_data') <small class="catalog-error">{{ $message }}</small> @enderror
                </label>
                @foreach (['dia' => 'Dia', 'mes' => 'Mês', 'ano' => 'Ano', 'decada' => 'Década'] as $field => $label)
                    <label class="catalog-label" for="{{ $field }}" x-show="isVisible(@js($field))">
                        <span>{{ $label }}</span>
                        <input id="{{ $field }}" name="{{ $field }}" type="{{ $field === 'decada' ? 'text' : 'number' }}" value="{{ $value($field) }}"
                            @if ($field === 'dia') min="1" max="31" @endif @if ($field === 'mes') min="1" max="12" @endif
                            @if ($field === 'ano') min="1000" max="{{ date('Y') }}" step="1" @endif @if ($field === 'decada') inputmode="numeric" maxlength="4" pattern="[0-9]{4}" @endif
                            class="{{ $fieldClass }}" :disabled="!isVisible(@js($field))">
                        @error($field) <small class="catalog-error">{{ $message }}</small> @enderror
                    </label>
                @endforeach
            </div>
            <div class="catalog-grid catalog-grid--two">
                @foreach (['local_atual' => 'Local atual', 'local_epoca' => 'Local na época', 'evento' => 'Evento relacionado', 'cedente' => 'Cedente'] as $field => $label)
                    <label class="catalog-label" for="{{ $field }}"><span>{{ $label }}</span><input id="{{ $field }}" name="{{ $field }}" type="text" value="{{ $value($field) }}" maxlength="255" class="{{ $fieldClass }}">@error($field) <small class="catalog-error">{{ $message }}</small> @enderror</label>
                @endforeach
            </div>
        </div>
    </details>

    <details class="catalog-disclosure" @if(count($selectedPessoas) || $errors->has('pessoa_ids') || $errors->has('pessoa_ids.*')) open @endif>
        <summary><span><strong>Pessoas identificadas</strong><small>Opcional · {{ count($selectedPessoas) }} selecionada(s)</small></span></summary>
        <div class="catalog-disclosure__body">
            @include('admin.fotografias._relationship_picker', [
                'name' => 'pessoa_ids', 'label' => 'Pessoas', 'description' => 'Marque somente quem foi identificado na imagem.',
                'options' => $pessoaOptions, 'selected' => $selectedPessoas, 'valueField' => 'id', 'labelField' => 'nome',
                'searchPlaceholder' => 'Buscar pessoa...', 'emptyMessage' => 'Nenhuma pessoa cadastrada.',
            ])
        </div>
    </details>

    <details class="catalog-disclosure" @if(count($selectedCategorias) || count($selectedAssuntos) || count($selectedPalavrasChave) || $errors->hasAny(['categoria_ids', 'categoria_ids.*', 'assunto_ids', 'assunto_ids.*', 'palavra_chave_ids', 'palavra_chave_ids.*'])) open @endif>
        <summary><span><strong>Classificações</strong><small>Categorias, assuntos e palavras-chave</small></span></summary>
        <div class="catalog-disclosure__body catalog-classifications">
            <livewire:admin.quick-catalog-create catalog="categoria" :initial-options="$categoriaOptions" :initial-selected="$selectedCategorias" :key="'quick-categoria-'.($fotografia?->id ?? 'new')" />
            <livewire:admin.quick-catalog-create catalog="assunto" :initial-options="$assuntoOptions" :initial-selected="$selectedAssuntos" :key="'quick-assunto-'.($fotografia?->id ?? 'new')" />
            <livewire:admin.quick-catalog-create catalog="palavra-chave" :initial-options="$palavraChaveOptions" :initial-selected="$selectedPalavrasChave" :key="'quick-palavra-chave-'.($fotografia?->id ?? 'new')" />
        </div>
    </details>

    <details class="catalog-disclosure" @error('arquivo_original') open @enderror>
        <summary><span><strong>Arquivo original</strong><small>Imagem ou PDF de até {{ $uploadMaxMb }} MB</small></span></summary>
        <div class="catalog-disclosure__body" x-data="filePreview()">
            <p class="catalog-help">Formatos aceitos: {{ $acceptedExtensions }}.</p>
            @if ($fotografia?->arquivos?->firstWhere('versao_arquivo', 'original'))
                @php($arquivoOriginal = $fotografia->arquivos->firstWhere('versao_arquivo', 'original'))
                <div class="catalog-file-current"><strong>{{ $arquivoOriginal->nome_original }}</strong><span>{{ $arquivoOriginal->mime_type }} · {{ number_format($arquivoOriginal->file_size / 1024, 1, ',', '.') }} KB</span></div>
            @else
                <input id="arquivo_original" name="arquivo_original" type="file" accept="{{ $acceptedMimeTypes }}" class="catalog-file-input" @change="selectFile($event)">
                <div class="catalog-file-preview" x-show="file !== null" style="display: none;">
                    <div class="catalog-file-preview__media">
                        <template x-if="isImage">
                            <img :src="previewUrl" :alt="`Pré-visualização de ${file.name}`">
                        </template>
                        <template x-if="file !== null && !isImage">
                            <span>PDF</span>
                        </template>
                    </div>
                    <div class="catalog-file-preview__details">
                        <strong x-text="file?.name"></strong>
                        <span x-text="metadata"></span>
                        <small x-text="status"></small>
                    </div>
                </div>
            @endif
            @error('arquivo_original') <p class="catalog-error">{{ $message }}</p> @enderror
        </div>
    </details>

    <div class="catalog-actions"><a href="{{ $cancelUrl }}">Cancelar</a><button type="submit">{{ $submitLabel }}</button></div>
</form>
