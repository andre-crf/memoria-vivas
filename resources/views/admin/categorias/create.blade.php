<x-layouts.admin title="Nova categoria | Memórias Vivas">
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        <x-admin.page-header
            class="mb-6"
            eyebrow="Catalogação"
            title="Nova categoria"
            description="Cadastre uma categoria para organizar e classificar os itens do acervo."
        />

        <x-catalog.section>
            @if ($errors->any())
                <x-ui.alert tone="danger" title="Verifique os dados informados." class="mb-5">
                    <p>Corrija os campos indicados abaixo.</p>
                </x-ui.alert>
            @endif

            <form method="POST" action="{{ route('admin.categorias.store') }}" class="grid gap-5">
                @csrf

                <div class="grid gap-1.5">
                    <x-catalog.label for="titulo" text="Título" required>
                        <x-catalog.control id="titulo" name="titulo" type="text" :value="old('titulo')" required maxlength="255" autofocus error="titulo" />
                    </x-catalog.label>
                    <x-catalog.error field="titulo" as="p" />
                </div>

                <div class="grid gap-1.5">
                    <x-catalog.label for="descricao" text="Descrição">
                        <x-catalog.control as="textarea" id="descricao" name="descricao" rows="5" error="descricao">{{ old('descricao') }}</x-catalog.control>
                    </x-catalog.label>
                    <x-catalog.error field="descricao" as="p" />
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-line pt-5 sm:flex-row sm:justify-end">
                    <x-catalog.button variant="secondary" :href="route('admin.categorias.index')">Cancelar</x-catalog.button>
                    <x-catalog.button type="submit">Cadastrar categoria</x-catalog.button>
                </div>
            </form>
        </x-catalog.section>
    </div>
</x-layouts.admin>
