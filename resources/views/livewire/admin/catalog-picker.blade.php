@php
    $pickerId = 'catalog-'.$catalog;
    $selectedIds = $isAuthor ? array_values(array_filter([$selectedSingle])) : $selected;
    $results = $this->results;
    $term = trim($search);
    $matches = fn (string $label): bool => mb_strtolower($label) === mb_strtolower($term);
    $alreadySelected = $term !== '' && collect($labels)->contains($matches);
    $offerCreate = $term !== '' && ! $alreadySelected && ! collect($results)->pluck('label')->contains($matches);
    $optionClass = 'flex cursor-pointer items-center justify-between gap-3 rounded px-3 py-2 text-[0.82rem] text-ink-800 aria-selected:bg-institutional-soft aria-selected:text-institutional';
@endphp

<div class="relative" x-data="catalogCombobox(@js(! $isAuthor))" @click.outside="open = false" data-testid="catalog-picker-{{ $catalog }}">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <label for="{{ $pickerId }}-search" class="text-[0.78rem] font-[650] text-ink-900">{{ $definition['label'] }}</label>
            <p class="mt-0.5 text-[0.72rem] leading-[1.45] text-ink-500">{{ $definition['description'] }}</p>
        </div>
        @include('livewire.admin._catalog-picker-create')
    </div>

    @if ($selectedIds)
        <ul class="mt-3 flex flex-wrap gap-1.5" aria-label="{{ $definition['label'] }} selecionados">
            @foreach ($selectedIds as $id)
                <li wire:key="{{ $pickerId }}-chip-{{ $id }}" class="inline-flex max-w-full items-center gap-1 rounded-full border border-institutional-muted bg-institutional-soft py-0.5 pr-0.5 pl-3 text-[0.8rem] text-institutional">
                    <span class="truncate">{{ $labels[$id] ?? '' }}</span>
                    <button type="button" wire:click="remove('{{ $id }}')" class="grid size-6 flex-none cursor-pointer place-items-center rounded-full hover:bg-white focus-visible:outline-2 focus-visible:outline-institutional" aria-label="Remover {{ $labels[$id] ?? '' }}">
                        <x-lucide-x class="size-3.5" aria-hidden="true" />
                    </button>
                </li>
            @endforeach
        </ul>
    @endif

    <div class="relative mt-3">
        <x-lucide-search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink-400" aria-hidden="true" />
        <input
            id="{{ $pickerId }}-search"
            type="text"
            role="combobox"
            autocomplete="off"
            aria-autocomplete="list"
            aria-controls="{{ $pickerId }}-listbox"
            aria-expanded="false"
            :aria-expanded="open"
            :aria-activedescendant="activeId"
            wire:model.live.debounce.250ms="search"
            @focus="show()"
            @input="show()"
            @keydown.arrow-down.prevent="move(1)"
            @keydown.arrow-up.prevent="move(-1)"
            @keydown.enter.prevent="choose()"
            @keydown.escape.prevent="open = false"
            @keydown.tab="open = false"
            placeholder="{{ $definition['search_placeholder'] }}"
            class="min-h-[2.35rem] w-full rounded-md border border-mist-300 bg-white py-[0.45rem] pr-3 pl-9 text-[0.82rem] text-ink-950 outline-none transition-[border-color,box-shadow] duration-150 focus:border-institutional focus:shadow-[0_0_0_3px_rgb(23_62_99/0.14)]"
        >

        <ul
            id="{{ $pickerId }}-listbox"
            role="listbox"
            aria-label="Sugestões de {{ mb_strtolower($definition['label']) }}"
            x-show="open"
            x-cloak
            class="absolute inset-x-0 top-full z-30 mt-1 max-h-72 overflow-y-auto rounded-md border border-mist-200 bg-white p-1 shadow-[0_16px_30px_rgb(24_42_63/0.16)]"
        >
            @foreach ($results as $index => $option)
                <li
                    id="{{ $pickerId }}-option-{{ $option['id'] }}"
                    wire:key="{{ $pickerId }}-option-{{ $option['id'] }}"
                    role="option"
                    aria-selected="false"
                    :aria-selected="active === {{ $index }}"
                    @mouseenter="active = {{ $index }}"
                    @mousedown.prevent
                    @click="pick('{{ $option['id'] }}')"
                    class="{{ $optionClass }}"
                >
                    <span class="min-w-0 truncate">{{ $option['label'] }}</span>
                    @isset($option['description'])
                        <span class="flex-none text-[0.72rem] text-ink-500">{{ $option['description'] }}</span>
                    @endisset
                </li>
            @endforeach

            @if ($offerCreate)
                <li
                    id="{{ $pickerId }}-option-create"
                    wire:key="{{ $pickerId }}-option-create"
                    role="option"
                    aria-selected="false"
                    :aria-selected="active === {{ count($results) }}"
                    @mouseenter="active = {{ count($results) }}"
                    @mousedown.prevent
                    @click="open = false; $wire.startCreate()"
                    class="{{ $optionClass }} justify-start font-semibold text-institutional"
                >
                    <x-lucide-plus class="size-4 flex-none" aria-hidden="true" />
                    <span class="min-w-0 truncate">{{ $definition['create_label'] }}: “{{ $term }}”</span>
                </li>
            @elseif ($results === [])
                <li class="px-3 py-2 text-[0.8rem] text-ink-500" role="presentation">{{ match (true) {
                    $alreadySelected => "“{$term}” já está selecionado.",
                    $term === '' => $definition['empty_message'],
                    default => 'Nenhum resultado encontrado.',
                } }}</li>
            @endif
        </ul>
    </div>

    {{-- O POST do formulário é tradicional: os selecionados vão como campos ocultos. --}}
    @if ($isAuthor)
        <input type="hidden" name="{{ $definition['input'] }}" value="{{ $selectedSingle }}">
    @else
        @foreach ($selected as $id)
            {{-- As entidades são decodificadas pelo navegador para [] e preservam o array no POST tradicional. --}}
            <input type="hidden" name="{{ $definition['input'] }}&#91;&#93;" value="{{ $id }}" wire:key="{{ $pickerId }}-hidden-{{ $id }}">
        @endforeach
    @endif

    @error($definition['input'])
        <x-catalog.error as="p" class="mt-2">{{ $message }}</x-catalog.error>
    @enderror
    @error($definition['input'].'.*')
        <x-catalog.error as="p" class="mt-2">{{ $message }}</x-catalog.error>
    @enderror
</div>
