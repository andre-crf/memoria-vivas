<div
    class="flex-none"
    x-data="quickCatalogModal()"
    @quick-catalog-created.window="if ($event.detail.catalog === @js($catalog)) close()"
    @open-catalog-create.window="if ($event.detail.catalog === @js($catalog)) open()"
>
    <x-catalog.button variant="secondary" size="sm" icon="plus" x-ref="trigger" x-on:click="open()">{{ $definition['create_label'] }}</x-catalog.button>
    <template x-teleport="body">
        <dialog
            class="admin-dialog w-[min(28rem,calc(100%-2rem))] overflow-y-auto"
            x-ref="dialog"
            wire:ignore.self
            aria-labelledby="quick-title-{{ $catalog }}"
            @close="restoreFocus()"
        >
            <div class="flex items-start justify-between gap-4 border-b border-line px-5 py-4">
                <h2 id="quick-title-{{ $catalog }}" class="text-section font-semibold">{{ $definition['create_label'] }}</h2>
                <x-catalog.button variant="ghost" size="sm" icon="x" class="-mt-1 -mr-2 flex-none" x-on:click="close()" aria-label="Fechar cadastro" title="Fechar" />
            </div>
            <div class="grid gap-3 bg-surface-muted p-5">
                @foreach ($definition['fields'] as $field)
                    @php($campoId = "quick-{$catalog}-{$field['name']}")
                    {{-- Mensagem fora do rótulo e ligada ao campo (aria-describedby), como nos formulários do admin. --}}
                    <div class="grid gap-1.5">
                        <x-catalog.label :for="$campoId" :text="$field['label']">
                            @if (($field['type'] ?? 'text') === 'select')
                                <x-catalog.control as="select" :id="$campoId" wire:model="form.{{ $field['name'] }}" :error="'form.'.$field['name']">
                                    @foreach ($field['options'] as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </x-catalog.control>
                            @else
                                <x-catalog.control :id="$campoId" type="text" wire:model="form.{{ $field['name'] }}" wire:keydown.enter.prevent="save" maxlength="255" :error="'form.'.$field['name']" />
                            @endif
                        </x-catalog.label>
                        <x-catalog.error :field="'form.'.$field['name']" :for="$campoId" as="p" role="alert" />
                    </div>
                @endforeach
            </div>
            <div class="flex flex-col-reverse gap-2 border-t border-line px-5 py-4 sm:flex-row sm:items-center sm:justify-end">
                <span class="text-caption text-fg-muted sm:mr-auto" wire:loading wire:target="save" role="status">Criando...</span>
                <x-catalog.button variant="secondary" x-on:click="close()">Cancelar</x-catalog.button>
                <x-catalog.button wire:click="save" wire:loading.attr="disabled" wire:target="save">Criar e selecionar</x-catalog.button>
            </div>
        </dialog>
    </template>
</div>
