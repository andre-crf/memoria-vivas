@php
    $selectedValues = collect($selected)->map(fn ($id) => (string) $id)->all();
@endphp

<x-catalog.picker
    :title="$label"
    :description="$description"
    :count="count($selectedValues)"
    :search-placeholder="$searchPlaceholder"
    :empty-message="$emptyMessage"
    x-data="relationshipPicker({{ count($selectedValues) }})"
>
    @foreach ($options as $option)
        @php
            $optionValue = (string) data_get($option, $valueField);
        @endphp
        <x-catalog.option :text="(string) data_get($option, $labelField)">
            <input
                type="checkbox"
                name="{{ $name }}[]"
                value="{{ $optionValue }}"
                @checked(in_array($optionValue, $selectedValues, true))
            >
        </x-catalog.option>
    @endforeach

    <x-slot:footer>
        @error($name)
            <x-catalog.error as="p">{{ $message }}</x-catalog.error>
        @enderror
        @error("{$name}.*")
            <x-catalog.error as="p">{{ $message }}</x-catalog.error>
        @enderror
    </x-slot:footer>
</x-catalog.picker>
