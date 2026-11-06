<x-layouts.admin title="Cadastrar fotografia | Memórias Vivas">
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
        <x-admin.page-header
            class="mb-6"
            eyebrow="Acervo"
            title="Cadastrar fotografia"
            description="Registre os dados básicos de catalogação do novo item fotográfico."
            :back-href="route('admin.fotografias.index')"
            back-label="Voltar para fotografias"
        />

        @include('admin.fotografias._form', [
            'action' => route('admin.fotografias.store'),
            'cancelUrl' => route('admin.fotografias.index'),
            'submitLabel' => 'Salvar fotografia',
        ])
    </div>
</x-layouts.admin>
