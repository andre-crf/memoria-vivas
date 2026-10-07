@php
    $selectedValues = collect($selected)->map(fn ($id) => (string) $id)->all();
    $hasSelection = count($selectedValues) > 0;
@endphp

<article class="catalog-picker" data-relationship-picker>
    <div class="catalog-picker__heading">
        <div>
            <h3>{{ $label }}</h3>
            <p>{{ $description }}</p>
        </div>
        <span class="catalog-picker__count" data-selection-count>{{ count($selectedValues) }} selecionado(s)</span>
    </div>

    <label class="catalog-search">
        <span class="sr-only">Pesquisar em {{ mb_strtolower($label) }}</span>
        <input type="search" placeholder="{{ $searchPlaceholder }}" data-option-search>
    </label>

    <div class="catalog-picker__options" data-option-list>
        @forelse ($options as $option)
            @php
                $optionValue = (string) data_get($option, $valueField);
                $optionLabel = (string) data_get($option, $labelField);
            @endphp
            <label class="catalog-option" data-option data-search-value="{{ mb_strtolower($optionLabel) }}">
                <input
                    type="checkbox"
                    name="{{ $name }}[]"
                    value="{{ $optionValue }}"
                    @checked(in_array($optionValue, $selectedValues, true))
                >
                <span>{{ $optionLabel }}</span>
            </label>
        @empty
            <p class="catalog-picker__empty" data-empty-message>{{ $emptyMessage }}</p>
        @endforelse
        <p class="catalog-picker__empty" data-no-results hidden>Nenhum resultado encontrado.</p>
    </div>

    @isset($create)
        <details
            class="quick-create"
            data-quick-create
            data-endpoint="{{ $create['endpoint'] }}"
            data-target-name="{{ $name }}[]"
        >
            <summary>+ Criar {{ $create['article'] }}</summary>
            <div class="quick-create__body">
                @foreach ($create['fields'] as $field)
                    <label>
                        <span>{{ $field['label'] }}</span>
                        <input
                            type="text"
                            data-field-name="{{ $field['name'] }}"
                            maxlength="255"
                            @if ($loop->first) required @endif
                            data-quick-field
                        >
                    </label>
                @endforeach
                <div class="quick-create__actions">
                    <button type="button" data-quick-submit>Criar e selecionar</button>
                    <span role="status" data-quick-status></span>
                </div>
            </div>
        </details>
    @endisset

    @error($name)
        <p class="catalog-error">{{ $message }}</p>
    @enderror
    @error("{$name}.*")
        <p class="catalog-error">{{ $message }}</p>
    @enderror
</article>
