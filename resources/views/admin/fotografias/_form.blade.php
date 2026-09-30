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
    $uploadConfig = config('acervo.uploads.original');
    $acceptedMimeTypes = implode(',', $uploadConfig['mime_types']);
    $acceptedExtensions = implode(', ', array_map(fn (string $extension) => ".{$extension}", $uploadConfig['extensions']));
    $uploadMaxMb = number_format($uploadConfig['max_kb'] / 1024, 0, ',', '.');
    $fieldClass = 'catalog-field';
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="catalog-form" data-catalog-form>
    @csrf
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

            <label class="catalog-label catalog-grid__wide" for="legenda">
                <span>Legenda/descrição</span>
                <textarea id="legenda" name="legenda" rows="3" maxlength="5000" data-character-limit="5000" class="{{ $fieldClass }}">{{ $value('legenda') }}</textarea>
                <span class="catalog-character-count" data-character-count>0/5000</span>
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

        <div class="catalog-author">
            <label class="catalog-label" for="autor_id">
                <span>Autor</span>
                <select id="autor_id" name="autor_id" class="{{ $fieldClass }}" data-author-select>
                    <option value="">Sem autor associado</option>
                    @foreach ($autorOptions as $autor)
                        <option value="{{ $autor->id }}" @selected((string) $selectedAutor === (string) $autor->id)>{{ $autor->nome }} · {{ $autor->tipo === 'instituicao' ? 'Instituição' : 'Pessoa' }}</option>
                    @endforeach
                </select>
                @error('autor_id') <small class="catalog-error">{{ $message }}</small> @enderror
            </label>

            <details class="quick-create" data-quick-create data-endpoint="{{ route('admin.autores.store') }}" data-target-name="autor_id">
                <summary>+ Cadastrar novo autor</summary>
                <div class="quick-create__body quick-create__body--author">
                    <label><span>Nome</span><input type="text" data-field-name="nome" maxlength="255" required data-quick-field></label>
                    <label><span>Tipo</span><select data-field-name="tipo" data-quick-field><option value="pessoa">Pessoa</option><option value="instituicao">Instituição</option></select></label>
                    <div class="quick-create__actions"><button type="button" data-quick-submit>Criar e selecionar</button><span role="status" data-quick-status></span></div>
                </div>
            </details>
        </div>
    </section>

    <details class="catalog-disclosure" @if($errors->hasAny(['tipo_data', 'dia', 'mes', 'ano', 'decada', 'local_atual', 'local_epoca', 'evento', 'cedente'])) open @endif>
        <summary><span><strong>Data e contexto</strong><small>Precisão da data, locais, evento e cedente</small></span></summary>
        <div class="catalog-disclosure__body" data-date-form>
            <div class="catalog-grid catalog-grid--date">
                <label class="catalog-label" for="tipo_data">
                    <span>Precisão da data</span>
                    <select id="tipo_data" name="tipo_data" required class="{{ $fieldClass }}">
                        @foreach ($tipoDataOptions as $tipoData)
                            <option value="{{ $tipoData->value }}" @selected($selectedTipoData === $tipoData->value)>{{ $tipoData->label() }}</option>
                        @endforeach
                    </select>
                    @error('tipo_data') <small class="catalog-error">{{ $message }}</small> @enderror
                </label>
                @foreach (['dia' => 'Dia', 'mes' => 'Mês', 'ano' => 'Ano', 'decada' => 'Década'] as $field => $label)
                    <label class="catalog-label" for="{{ $field }}" data-date-for="{{ $field }}">
                        <span>{{ $label }}</span>
                        <input id="{{ $field }}" name="{{ $field }}" type="{{ $field === 'decada' ? 'text' : 'number' }}" value="{{ $value($field) }}"
                            @if ($field === 'dia') min="1" max="31" @endif @if ($field === 'mes') min="1" max="12" @endif
                            @if ($field === 'ano') min="1000" max="{{ date('Y') }}" step="1" @endif @if ($field === 'decada') inputmode="numeric" maxlength="4" pattern="[0-9]{4}" @endif class="{{ $fieldClass }}">
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
            @include('admin.fotografias._relationship_picker', [
                'name' => 'categoria_ids', 'label' => 'Categorias', 'description' => 'Classificação geral do item.',
                'options' => $categoriaOptions, 'selected' => $selectedCategorias, 'valueField' => 'id', 'labelField' => 'titulo',
                'searchPlaceholder' => 'Buscar categoria...', 'emptyMessage' => 'Nenhuma categoria cadastrada.',
                'create' => ['endpoint' => route('admin.categorias.store'), 'article' => 'categoria', 'fields' => [['name' => 'titulo', 'label' => 'Título']]],
            ])
            @include('admin.fotografias._relationship_picker', [
                'name' => 'assunto_ids', 'label' => 'Assuntos', 'description' => 'Temas retratados na fotografia.',
                'options' => $assuntoOptions, 'selected' => $selectedAssuntos, 'valueField' => 'id', 'labelField' => 'titulo',
                'searchPlaceholder' => 'Buscar assunto...', 'emptyMessage' => 'Nenhum assunto cadastrado.',
                'create' => ['endpoint' => route('admin.assuntos.store'), 'article' => 'assunto', 'fields' => [['name' => 'titulo', 'label' => 'Título']]],
            ])
            @include('admin.fotografias._relationship_picker', [
                'name' => 'palavra_chave_ids', 'label' => 'Palavras-chave', 'description' => 'Termos específicos para facilitar buscas.',
                'options' => $palavraChaveOptions, 'selected' => $selectedPalavrasChave, 'valueField' => 'id', 'labelField' => 'termo',
                'searchPlaceholder' => 'Buscar palavra-chave...', 'emptyMessage' => 'Nenhuma palavra-chave cadastrada.',
                'create' => ['endpoint' => route('admin.palavras-chave.store'), 'article' => 'palavra-chave', 'fields' => [['name' => 'termo', 'label' => 'Termo']]],
            ])
        </div>
    </details>

    <details class="catalog-disclosure" @error('arquivo_original') open @enderror>
        <summary><span><strong>Arquivo original</strong><small>Imagem ou PDF de até {{ $uploadMaxMb }} MB</small></span></summary>
        <div class="catalog-disclosure__body">
            <p class="catalog-help">Formatos aceitos: {{ $acceptedExtensions }}.</p>
            @if ($fotografia?->arquivos?->firstWhere('versao_arquivo', 'original'))
                @php($arquivoOriginal = $fotografia->arquivos->firstWhere('versao_arquivo', 'original'))
                <div class="catalog-file-current"><strong>{{ $arquivoOriginal->nome_original }}</strong><span>{{ $arquivoOriginal->mime_type }} · {{ number_format($arquivoOriginal->file_size / 1024, 1, ',', '.') }} KB</span></div>
            @else
                <input id="arquivo_original" name="arquivo_original" type="file" accept="{{ $acceptedMimeTypes }}" class="catalog-file-input">
            @endif
            @error('arquivo_original') <p class="catalog-error">{{ $message }}</p> @enderror
        </div>
    </details>

    <div class="catalog-actions"><a href="{{ $cancelUrl }}">Cancelar</a><button type="submit">{{ $submitLabel }}</button></div>
</form>

<script>
    (() => {
        const form = document.querySelector('[data-catalog-form]');
        if (!form) return;
        const dateForm = form.querySelector('[data-date-form]');
        const datePrecision = dateForm?.querySelector('[name="tipo_data"]');
        const fieldsByPrecision = { data_exata: ['dia', 'mes', 'ano'], mes_ano: ['mes', 'ano'], ano: ['ano'], decada: ['decada'], desconhecida: [] };
        const updateDateFields = () => {
            const visible = fieldsByPrecision[datePrecision?.value] ?? [];
            dateForm?.querySelectorAll('[data-date-for]').forEach((field) => {
                const show = visible.includes(field.dataset.dateFor);
                field.hidden = !show;
                field.querySelector('input').disabled = !show;
            });
        };
        datePrecision?.addEventListener('change', updateDateFields);
        updateDateFields();

        form.querySelectorAll('[data-character-limit]').forEach((field) => {
            const counter = field.parentElement.querySelector('[data-character-count]');
            const limit = Number(field.dataset.characterLimit);
            const updateCharacterCount = () => {
                const length = [...field.value].length;
                counter.textContent = `${length.toLocaleString('pt-BR')}/${limit.toLocaleString('pt-BR')}`;
                counter.classList.toggle('is-near-limit', length >= limit * 0.9);
                counter.classList.toggle('is-over-limit', length > limit);
            };
            field.addEventListener('input', updateCharacterCount);
            updateCharacterCount();
        });

        const updateCount = (picker) => {
            const count = picker.querySelectorAll('input[type="checkbox"]:checked').length;
            picker.querySelector('[data-selection-count]').textContent = `${count} selecionado(s)`;
        };
        form.querySelectorAll('[data-relationship-picker]').forEach((picker) => {
            const search = picker.querySelector('[data-option-search]');
            const noResults = picker.querySelector('[data-no-results]');
            search?.addEventListener('input', () => {
                const term = search.value.trim().toLocaleLowerCase('pt-BR');
                let visible = 0;
                picker.querySelectorAll('[data-option]').forEach((option) => {
                    option.hidden = !option.dataset.searchValue.includes(term);
                    if (!option.hidden) visible++;
                });
                noResults.hidden = visible !== 0;
            });
            picker.addEventListener('change', () => updateCount(picker));
            updateCount(picker);
        });

        const addCheckboxOption = (quickCreate, item) => {
            const picker = quickCreate.closest('[data-relationship-picker]');
            picker.querySelector('[data-empty-message]')?.remove();
            const option = document.createElement('label');
            option.className = 'catalog-option';
            option.dataset.option = '';
            option.dataset.searchValue = item.label.toLocaleLowerCase('pt-BR');
            const input = document.createElement('input');
            input.type = 'checkbox'; input.name = quickCreate.dataset.targetName; input.value = item.id; input.checked = true;
            const label = document.createElement('span'); label.textContent = item.label;
            option.append(input, label);
            picker.querySelector('[data-option-list]').prepend(option);
            updateCount(picker);
        };
        const addAuthorOption = (item) => {
            const select = form.querySelector('[data-author-select]');
            select.add(new Option(`${item.label} · ${item.description}`, item.id, true, true));
        };

        form.querySelectorAll('[data-quick-create]').forEach((quickCreate) => {
            const button = quickCreate.querySelector('[data-quick-submit]');
            const status = quickCreate.querySelector('[data-quick-status]');
            button.addEventListener('click', async () => {
                const fields = [...quickCreate.querySelectorAll('[data-quick-field]')];
                const payload = Object.fromEntries(fields.map((field) => [field.dataset.fieldName, field.value.trim()]));
                status.textContent = '';
                button.disabled = true;
                try {
                    const response = await fetch(quickCreate.dataset.endpoint, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value },
                        body: JSON.stringify(payload),
                    });
                    const data = await response.json();
                    if (!response.ok) throw new Error(Object.values(data.errors ?? {}).flat()[0] ?? 'Não foi possível criar o registro.');
                    quickCreate.dataset.targetName === 'autor_id' ? addAuthorOption(data) : addCheckboxOption(quickCreate, data);
                    fields.forEach((field) => { if (field.tagName === 'INPUT') field.value = ''; });
                    status.textContent = 'Criado e selecionado.';
                    quickCreate.open = false;
                } catch (error) {
                    status.textContent = error.message;
                } finally {
                    button.disabled = false;
                }
            });
        });
    })();
</script>
