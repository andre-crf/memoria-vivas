<x-layouts.admin title="Nova pessoa | Memórias Vivas">
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        <x-admin.page-header
            class="mb-6"
            eyebrow="Catalogação"
            title="Nova pessoa"
            description="Cadastre uma pessoa identificada nos itens do acervo."
        />

        <x-catalog.section>
            @if ($errors->any())
                <x-ui.alert tone="danger" title="Verifique os dados informados." class="mb-5">
                    <p>Corrija os campos indicados abaixo.</p>
                </x-ui.alert>
            @endif

            <form method="POST" action="{{ route('admin.pessoas.store') }}" class="grid gap-5">
                @csrf

                <div class="grid gap-1.5">
                    <x-catalog.label for="nome" text="Nome" required>
                        <x-catalog.control id="nome" name="nome" type="text" :value="old('nome')" required maxlength="255" autofocus error="nome" />
                    </x-catalog.label>
                    <x-catalog.error field="nome" as="p" />
                </div>

                <div class="grid gap-1.5">
                    <x-catalog.label for="observacao" text="Observação">
                        <x-catalog.control as="textarea" id="observacao" name="observacao" rows="5" error="observacao">{{ old('observacao') }}</x-catalog.control>
                    </x-catalog.label>
                    <x-catalog.error field="observacao" as="p" />
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-line pt-5 sm:flex-row sm:justify-end">
                    <x-catalog.button variant="secondary" :href="route('admin.pessoas.index')">Cancelar</x-catalog.button>
                    <x-catalog.button type="submit">Cadastrar pessoa</x-catalog.button>
                </div>
            </form>
        </x-catalog.section>
    </div>
</x-layouts.admin>
