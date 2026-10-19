<div class="quick-create" x-data="quickCatalogModal()" @quick-catalog-created.window="if ($event.detail.catalog === @js($catalog)) close()">
    <button type="button" class="quick-create__trigger" x-ref="trigger" @click="open()">+ {{ $isAuthor ? 'Cadastrar' : 'Criar' }} {{ $definition['article'] }}</button>
    <span class="quick-create__status" role="status">{{ $status }}</span>
    <template x-teleport="body">
        <dialog class="quick-create__dialog" x-ref="dialog" wire:ignore.self aria-labelledby="quick-title-{{ $catalog }}" @close="restoreFocus()">
            <div class="quick-create__dialog-heading">
                <h2 id="quick-title-{{ $catalog }}">{{ $isAuthor ? 'Cadastrar' : 'Criar' }} {{ $definition['article'] }}</h2>
                <button type="button" @click="close()" aria-label="Fechar cadastro" title="Fechar">&times;</button>
            </div>
            <div class="quick-create__body">
                @foreach ($definition['fields'] as $field)
                    <label for="quick-{{ $catalog }}-{{ $field['name'] }}">
                        <span>{{ $field['label'] }}</span>
                        @if (($field['type'] ?? 'text') === 'select')
                            <select id="quick-{{ $catalog }}-{{ $field['name'] }}" wire:model="form.{{ $field['name'] }}">
                                @foreach ($field['options'] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        @else
                            <input id="quick-{{ $catalog }}-{{ $field['name'] }}" type="text" wire:model="form.{{ $field['name'] }}" wire:keydown.enter.prevent="save" maxlength="255">
                        @endif
                        @error('form.'.$field['name'])
                            <small class="catalog-error" role="alert">{{ $message }}</small>
                        @enderror
                    </label>
                @endforeach
                <div class="quick-create__actions">
                    <button type="button" @click="close()">Cancelar</button>
                    <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save">Criar e selecionar</button>
                    <span wire:loading wire:target="save" role="status">Criando...</span>
                </div>
            </div>
        </dialog>
    </template>
</div>
