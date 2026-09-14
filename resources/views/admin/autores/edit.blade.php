<x-layouts.admin title="Editar autor | Memórias Vivas">

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        <div>
            <h2 class="text-2xl font-semibold text-stone-900">
                Editar autor
            </h2>

            <p class="mt-1 text-sm text-stone-600">
                Atualize os dados do autor.
            </p>
        </div>

        @if ($errors->any())
            <div class="mt-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-medium">
                    Verifique os dados informados.
                </p>

                <ul class="mt-2 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('admin.autores.update', $autor) }}"
            class="mt-6 space-y-6 rounded-lg border border-stone-200 bg-white p-6"
        >
            @csrf
            @method('PUT')

            <div>
                <label
                    for="nome"
                    class="block text-sm font-medium text-stone-700"
                >
                    Nome
                </label>

                <input
                    id="nome"
                    name="nome"
                    type="text"
                    value="{{ old('nome', $autor->nome) }}"
                    required
                    maxlength="255"
                    class="mt-2 block w-full rounded-md border-stone-300 shadow-sm focus:border-emerald-700 focus:ring-emerald-700"
                >
            </div>

            <div>
                <label
                    for="tipo"
                    class="block text-sm font-medium text-stone-700"
                >
                    Tipo
                </label>

                <select
                    id="tipo"
                    name="tipo"
                    required
                    class="mt-2 block w-full rounded-md border-stone-300 shadow-sm focus:border-emerald-700 focus:ring-emerald-700"
                >
                    <option value="">Selecione</option>

                    <option
                        value="pessoa"
                        @selected(old('tipo', $autor->tipo) === 'pessoa')
                    >
                        Pessoa
                    </option>

                    <option
                        value="instituicao"
                        @selected(old('tipo', $autor->tipo) === 'instituicao')
                    >
                        Instituição
                    </option>
                </select>
            </div>

            <div>
                <label
                    for="observacao"
                    class="block text-sm font-medium text-stone-700"
                >
                    Observação
                </label>

                <textarea
                    id="observacao"
                    name="observacao"
                    rows="4"
                    class="mt-2 block w-full rounded-md border-stone-300 shadow-sm focus:border-emerald-700 focus:ring-emerald-700"
                >{{ old('observacao', $autor->observacao) }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a
                    href="{{ route('admin.autores.index') }}"
                    class="rounded-md border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50"
                >
                    Cancelar
                </a>

                <button
                    type="submit"
                    class="rounded-md bg-emerald-800 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-900"
                >
                    Salvar alterações
                </button>
            </div>
        </form>
    </div>
</x-layouts.admin>