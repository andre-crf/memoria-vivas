<x-layouts.admin title="Editar fotografia | Memórias Vivas">
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
        <x-admin.page-header
            class="mb-6"
            eyebrow="Acervo"
            title="Editar fotografia"
            description="Atualize as informações de catalogação da fotografia selecionada."
            :back-href="route('admin.fotografias.show', $fotografia)"
            back-label="Voltar para detalhes"
        />

        @include('admin.fotografias._form', [
            'fotografia' => $fotografia,
            'action' => route('admin.fotografias.update', $fotografia),
            'method' => 'PUT',
            'cancelUrl' => route('admin.fotografias.show', $fotografia),
            'submitLabel' => 'Salvar alterações',
        ])
    </div>
</x-layouts.admin>
