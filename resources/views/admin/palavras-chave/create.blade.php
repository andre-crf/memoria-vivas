<x-layouts.admin title="Nova palavra-chave | Memórias Vivas">
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        <x-admin.page-header
            class="mb-6"
            eyebrow="Catalogação"
            title="Nova palavra-chave"
            description="Cadastre um termo para indexar os itens do acervo."
        />

        <x-catalog.section>
            @if ($errors->any())
                <x-ui.alert tone="danger" title="Verifique os dados informados." class="mb-5">
                    <p>Corrija os campos indicados abaixo.</p>
                </x-ui.alert>
            @endif

            <form method="POST" action="{{ route('admin.palavras-chave.store') }}" class="grid gap-5">
                @csrf

                <div class="grid gap-1.5">
                    <x-catalog.label for="termo" text="Termo" required>
                        <x-catalog.control id="termo" name="termo" type="text" :value="old('termo')" required maxlength="255" autofocus error="termo" />
                    </x-catalog.label>
                    <x-catalog.error field="termo" as="p" />
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-line pt-5 sm:flex-row sm:justify-end">
                    <x-catalog.button variant="secondary" :href="route('admin.palavras-chave.index')">Cancelar</x-catalog.button>
                    <x-catalog.button type="submit">Cadastrar palavra-chave</x-catalog.button>
                </div>
            </form>
        </x-catalog.section>
    </div>
</x-layouts.admin>
