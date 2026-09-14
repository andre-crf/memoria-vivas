<x-layouts.admin title="Editar fotografia | Memórias Vivas" active="fotografias">
    <div class="mx-auto max-w-5xl">
        <section class="mb-6">
            <a
                href="{{ route('admin.fotografias.show', $fotografia) }}"
                class="text-sm font-semibold text-[#173F7A] hover:text-[#0E2A52]"
            >
                Voltar para detalhes
            </a>
            <h1 class="mt-3 font-serif text-3xl font-semibold text-[#173F7A]">Editar fotografia</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-[#55709B]">
                Atualize as informações de catalogação da fotografia selecionada.
            </p>
        </section>

        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-semibold">Revise os campos destacados.</p>
            </div>
        @endif

        @include('admin.fotografias._form', [
            'fotografia' => $fotografia,
            'action' => route('admin.fotografias.update', $fotografia),
            'method' => 'PUT',
            'cancelUrl' => route('admin.fotografias.show', $fotografia),
            'submitLabel' => 'Salvar alterações',
        ])
    </div>
</x-layouts.admin>
