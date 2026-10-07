<details
    class="quick-create"
    x-data="{ open: false }"
    :open="open"
    @toggle="open = $el.open"
    @quick-catalog-created.window="if ($event.detail.catalog === @js($catalog)) open = false"
>
    <summary>+ {{ $isAuthor ? 'Cadastrar' : 'Criar' }} {{ $definition['article'] }}</summary>
    <div class="quick-create__body {{ $isAuthor ? 'quick-create__body--author' : '' }}">
        @foreach ($definition['fields'] as $field)
            <label>
                <span>{{ $field['label'] }}</span>
                @if (($field['type'] ?? 'text') === 'select')
                    <select wire:model="form.{{ $field['name'] }}" wire:keydown.enter.prevent="save">
                        @foreach ($field['options'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="text" wire:model="form.{{ $field['name'] }}" wire:keydown.enter.prevent="save" maxlength="255">
                @endif
                @error('form.'.$field['name'])
                    <small class="catalog-error">{{ $message }}</small>
                @enderror
            </label>
        @endforeach
        <div class="quick-create__actions">
            <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save">Criar e selecionar</button>
            <span role="status">
                <span wire:loading wire:target="save">Criando...</span>
                <span wire:loading.remove wire:target="save">{{ $status }}</span>
            </span>
        </div>
    </div>
</details>
