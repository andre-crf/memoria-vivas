<x-layouts.admin title="Cadastrar fotografia | Memórias Vivas">
    <div class="mx-auto max-w-5xl px-6 py-8">
        <section class="mb-6">
            <a
                href="{{ route('admin.fotografias.index') }}"
                class="text-sm font-semibold text-institutional hover:text-institutional-strong"
            >
                Voltar para fotografias
            </a>

            <p class="mt-4 text-sm font-medium text-heritage-700">Acervo</p>
            <h1 class="mt-1 text-2xl font-semibold text-stone-950">Cadastrar fotografia</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-600">
                Registre os dados básicos de catalogação do novo item fotográfico.
            </p>
        </section>

        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-semibold">Revise os campos destacados.</p>
            </div>
        @endif

        @include('admin.fotografias._form', [
            'action' => route('admin.fotografias.store'),
            'cancelUrl' => route('admin.fotografias.index'),
            'submitLabel' => 'Salvar fotografia',
        ])
    </div>
</x-layouts.admin>
