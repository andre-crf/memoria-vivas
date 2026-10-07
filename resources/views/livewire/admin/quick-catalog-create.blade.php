<div class="contents">
    @if ($isAuthor)
        <div class="catalog-author">
            <label class="catalog-label" for="autor_id">
                <span>{{ $definition['label'] }}</span>
                <select id="autor_id" name="{{ $definition['input'] }}" wire:model="selectedSingle" class="catalog-field">
                    <option value="">Sem autor associado</option>
                    @foreach ($options as $option)
                        <option value="{{ $option['id'] }}">{{ $option['label'] }} · {{ $option['description'] }}</option>
                    @endforeach
                </select>
                @error('autor_id') <small class="catalog-error">{{ $message }}</small> @enderror
            </label>

            @include('livewire.admin._quick-catalog-fields')
        </div>
    @else
        <article
            class="catalog-picker"
            x-data="relationshipPicker({{ count($selected) }})"
            @change="refreshSelection()"
            @quick-catalog-created.window="if ($event.detail.catalog === @js($catalog)) refresh()"
        >
            <div class="catalog-picker__heading">
                <div>
                    <h3>{{ $definition['label'] }}</h3>
                    <p>{{ $definition['description'] }}</p>
                </div>
                <span class="catalog-picker__count" x-text="`${selectedCount} selecionado(s)`">{{ count($selected) }} selecionado(s)</span>
            </div>

            <label class="catalog-search">
                <span class="sr-only">Pesquisar em {{ mb_strtolower($definition['label']) }}</span>
                <input type="search" placeholder="{{ $definition['search_placeholder'] }}" x-model="search" @input="filterOptions()">
            </label>

            <div class="catalog-picker__options" x-ref="options">
                @forelse ($options as $option)
                    <label class="catalog-option" data-option data-search-value="{{ mb_strtolower($option['label']) }}" wire:key="{{ $catalog }}-option-{{ $option['id'] }}">
                        {{-- As entidades são decodificadas pelo navegador para [] e preservam o array no POST tradicional. --}}
                        <input type="checkbox" name="{{ $definition['input'] }}&#91;&#93;" value="{{ $option['id'] }}" wire:model="selected" @checked(in_array($option['id'], $selected, true))>
                        <span>{{ $option['label'] }}</span>
                    </label>
                @empty
                    <p class="catalog-picker__empty">{{ $definition['empty_message'] }}</p>
                @endforelse
                <p class="catalog-picker__empty" x-show="showNoResults" style="display: none;">Nenhum resultado encontrado.</p>
            </div>

            @include('livewire.admin._quick-catalog-fields')

            @error($definition['input'])
                <p class="catalog-error">{{ $message }}</p>
            @enderror
            @error($definition['input'].'.*')
                <p class="catalog-error">{{ $message }}</p>
            @enderror
        </article>
    @endif
</div>
