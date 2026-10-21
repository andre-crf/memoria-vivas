<div @class(['relative', 'mt-3' => ! $isAuthor]) x-data="quickCatalogModal()" @quick-catalog-created.window="if ($event.detail.catalog === @js($catalog)) close()">
    <x-catalog.button variant="secondary" size="sm" icon="plus" x-ref="trigger" x-on:click="open()">
        {{ $isAuthor ? 'Cadastrar' : 'Criar' }} {{ $definition['article'] }}
    </x-catalog.button>
    <span role="status">{{ $status }}</span>
    <template x-teleport="body">
        <dialog
            class="fixed top-1/2 left-1/2 m-0 box-border max-h-[calc(100dvh-2rem)] w-[min(28rem,calc(100%-2rem))] -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-lg bg-white p-0 text-ink-950 shadow-[0_24px_64px_rgb(15_23_42/0.28)] backdrop:bg-[rgb(15_23_42/0.55)]"
            x-ref="dialog"
            wire:ignore.self
            aria-labelledby="quick-title-{{ $catalog }}"
            @close="restoreFocus()"
        >
            <div class="flex items-center justify-between gap-4 border-b border-mist-200 px-5 py-4">
                <h2 id="quick-title-{{ $catalog }}" class="text-[1.05rem] font-semibold">{{ $isAuthor ? 'Cadastrar' : 'Criar' }} {{ $definition['article'] }}</h2>
                <button type="button" class="grid size-8 cursor-pointer place-items-center rounded-full text-[1.35rem] leading-none text-ink-600 hover:bg-institutional-soft hover:text-ink-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-institutional" @click="close()" aria-label="Fechar cadastro" title="Fechar">&times;</button>
            </div>
            <div class="grid gap-3 bg-mist-50 p-5">
                @foreach ($definition['fields'] as $field)
                    <x-catalog.label for="quick-{{ $catalog }}-{{ $field['name'] }}" :text="$field['label']">
                        @if (($field['type'] ?? 'text') === 'select')
                            <x-catalog.control as="select" id="quick-{{ $catalog }}-{{ $field['name'] }}" wire:model="form.{{ $field['name'] }}">
                                @foreach ($field['options'] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-catalog.control>
                        @else
                            <x-catalog.control id="quick-{{ $catalog }}-{{ $field['name'] }}" type="text" wire:model="form.{{ $field['name'] }}" wire:keydown.enter.prevent="save" maxlength="255" />
                        @endif
                        @error('form.'.$field['name'])
                            <x-catalog.error role="alert">{{ $message }}</x-catalog.error>
                        @enderror
                    </x-catalog.label>
                @endforeach
                <div class="col-span-full flex items-center gap-3">
                    <x-catalog.button variant="secondary" x-on:click="close()">Cancelar</x-catalog.button>
                    <x-catalog.button wire:click="save" wire:loading.attr="disabled" wire:target="save">Criar e selecionar</x-catalog.button>
                    <span class="text-[0.7rem] text-ink-600" wire:loading wire:target="save" role="status">Criando...</span>
                </div>
            </div>
        </dialog>
    </template>
</div>
