@props([
    'title',
    'description',
    'count',
    'searchPlaceholder',
    'emptyMessage',
])

{{-- O x-data (relationshipPicker) vem de quem usa o componente. O slot recebe as <x-catalog.option>. --}}
<article {{ $attributes->class('min-w-0 rounded-md border border-mist-200 bg-mist-50 p-4') }} data-testid="relationship-picker" @change="refreshSelection()">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h3 class="text-[0.9rem] font-[750] text-ink-950">{{ $title }}</h3>
            <p class="mt-[0.2rem] text-[0.72rem] leading-[1.45] text-ink-500">{{ $description }}</p>
        </div>
        <span class="flex-none rounded-full bg-institutional-soft px-2 py-1 text-[0.65rem] font-bold text-institutional" data-testid="relationship-picker-count" x-text="`${selectedCount} selecionado(s)`">{{ $count }} selecionado(s)</span>
    </div>

    <label class="mt-[0.85rem] block">
        <span class="sr-only">Pesquisar em {{ mb_strtolower($title) }}</span>
        <x-catalog.control type="search" size="sm" :placeholder="$searchPlaceholder" x-model="search" x-on:input="filterOptions()" />
    </label>

    <div class="mt-[0.6rem] grid max-h-[12.5rem] overflow-y-auto rounded-[0.3rem] border border-mist-200 bg-white [scrollbar-width:thin]" x-ref="options">
        @if ($slot->hasActualContent())
            {{ $slot }}
        @else
            <p class="p-[0.8rem] text-[0.75rem] text-ink-400">{{ $emptyMessage }}</p>
        @endif
        <p class="p-[0.8rem] text-[0.75rem] text-ink-400" x-show="showNoResults" style="display: none;">Nenhum resultado encontrado.</p>
    </div>

    {{ $footer ?? '' }}
</article>
