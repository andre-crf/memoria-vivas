<x-layouts.admin title="Cadastrar fotografia | Memórias Vivas" active="fotografias">
    <div class="mx-auto max-w-5xl">
        <section class="mb-6">
            <a
                href="{{ route('admin.fotografias.index') }}"
                class="text-sm font-semibold text-[#173F7A] hover:text-[#0E2A52]"
            >
                Voltar para fotografias
            </a>
            <h1 class="mt-3 font-serif text-3xl font-semibold text-[#173F7A]">Cadastrar fotografia</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-[#55709B]">
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
