<div class="contents">
    @if ($isAuthor)
        <div class="grid items-end gap-4 sm:grid-cols-[minmax(0,1fr)_auto]" data-testid="quick-author">
            <x-catalog.label for="autor_id" :text="$definition['label']">
                <x-catalog.control as="select" id="autor_id" :name="$definition['input']" wire:model="selectedSingle">
                    <option value="">Sem autor associado</option>
                    @foreach ($options as $option)
                        <option value="{{ $option['id'] }}">{{ $option['label'] }} · {{ $option['description'] }}</option>
                    @endforeach
                </x-catalog.control>
                @error('autor_id') <x-catalog.error>{{ $message }}</x-catalog.error> @enderror
            </x-catalog.label>

            @include('livewire.admin._quick-catalog-fields')
        </div>
    @else
        <x-catalog.picker
            :title="$definition['label']"
            :description="$definition['description']"
            :count="count($selected)"
            :search-placeholder="$definition['search_placeholder']"
            :empty-message="$definition['empty_message']"
            x-data="relationshipPicker({{ count($selected) }})"
            x-on:quick-catalog-created.window="if ($event.detail.catalog === '{{ $catalog }}') refresh()"
        >
            @foreach ($options as $option)
                <x-catalog.option :text="$option['label']" wire:key="{{ $catalog }}-option-{{ $option['id'] }}">
                    {{-- As entidades são decodificadas pelo navegador para [] e preservam o array no POST tradicional. --}}
                    <input type="checkbox" name="{{ $definition['input'] }}&#91;&#93;" value="{{ $option['id'] }}" wire:model="selected" @checked(in_array($option['id'], $selected, true))>
                </x-catalog.option>
            @endforeach

            <x-slot:footer>
                @include('livewire.admin._quick-catalog-fields')

                @error($definition['input'])
                    <x-catalog.error as="p">{{ $message }}</x-catalog.error>
                @enderror
                @error($definition['input'].'.*')
                    <x-catalog.error as="p">{{ $message }}</x-catalog.error>
                @enderror
            </x-slot:footer>
        </x-catalog.picker>
    @endif
</div>
