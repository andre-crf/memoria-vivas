@php
    $selectedValues = collect($selected)->map(fn ($id) => (string) $id)->all();
    $hasSelection = count($selectedValues) > 0;
@endphp

<article
    class="catalog-picker"
    x-data="relationshipPicker({{ count($selectedValues) }})"
    @change="refreshSelection()"
>
    <div class="catalog-picker__heading">
        <div>
            <h3>{{ $label }}</h3>
            <p>{{ $description }}</p>
        </div>
        <span class="catalog-picker__count" x-text="`${selectedCount} selecionado(s)`">{{ count($selectedValues) }} selecionado(s)</span>
    </div>

    <label class="catalog-search">
        <span class="sr-only">Pesquisar em {{ mb_strtolower($label) }}</span>
        <input
            type="search"
            placeholder="{{ $searchPlaceholder }}"
            x-model="search"
            @input="filterOptions()"
        >
    </label>

    <div class="catalog-picker__options" x-ref="options">
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
            <p class="catalog-picker__empty">{{ $emptyMessage }}</p>
        @endforelse
        <p class="catalog-picker__empty" x-show="showNoResults" style="display: none;">Nenhum resultado encontrado.</p>
    </div>

    @error($name)
        <p class="catalog-error">{{ $message }}</p>
    @enderror
    @error("{$name}.*")
        <p class="catalog-error">{{ $message }}</p>
    @enderror
</article>
