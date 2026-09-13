<x-layouts.admin title="Editar categoria | Memórias Vivas">
    <div class="mx-auto max-w-3xl px-6 py-8">
            <section class="mb-6">
                <p class="text-sm font-medium text-[#6B5E2E]">
                    Acervo
                </p>

                <h2 class="mt-1 text-2xl font-semibold text-stone-950">
                    Editar categoria
                </h2>

                <p class="mt-2 text-sm leading-6 text-stone-600">
                    Atualize os dados da categoria cadastrada.
                </p>
            </section>

            <section class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
                @if ($errors->any())
                    <div class="mb-6 rounded-md border border-red-200 bg-red-50 p-4">
                        <p class="text-sm font-medium text-red-800">
                            Verifique os dados informados.
                        </p>

                        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form
                    method="POST"
                    action="{{ route('admin.categorias.update', $categoria) }}"
                    class="space-y-6"
                >
                    @csrf
                    @method('PUT')

                    <div>
                        <label
                            for="titulo"
                            class="block text-sm font-medium text-stone-800"
                        >
                            Título
                        </label>

                        <input
                            id="titulo"
                            name="titulo"
                            type="text"
                            value="{{ old('titulo', $categoria->titulo) }}"
                            required
                            maxlength="255"
                            class="mt-2 block w-full rounded-md border border-stone-300 px-3 py-2 text-sm text-stone-900 shadow-sm focus:border-[#6B5E2E] focus:outline-none focus:ring-1 focus:ring-[#6B5E2E]"
                        >
                    </div>

                    <div>
                        <label
                            for="descricao"
                            class="block text-sm font-medium text-stone-800"
                        >
                            Descrição
                        </label>

                        <textarea
                            id="descricao"
                            name="descricao"
                            rows="5"
                            class="mt-2 block w-full rounded-md border border-stone-300 px-3 py-2 text-sm text-stone-900 shadow-sm focus:border-[#6B5E2E] focus:outline-none focus:ring-1 focus:ring-[#6B5E2E]"
                        >{{ old('descricao', $categoria->descricao) }}</textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-stone-200 pt-6">
                        <a
                            href="{{ route('admin.categorias.index') }}"
                            class="rounded-md border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50"
                        >
                            Cancelar
                        </a>

                        <button
                            type="submit"
                            class="rounded-md bg-[#6B5E2E] px-4 py-2 text-sm font-medium text-white hover:opacity-90"
                        >
                            Salvar alterações
                        </button>
                    </div>
                </form>
            </section>
    </div>
</x-layouts.admin>
