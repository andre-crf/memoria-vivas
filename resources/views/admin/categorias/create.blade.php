<x-layouts.admin title="Nova categoria | Memórias Vivas">
    <div class="mx-auto max-w-3xl px-6 py-8">
            <section class="mb-6">
                <p class="text-sm font-medium text-[#6B5E2E]">
                    Catalogação
                </p>

                <h2 class="mt-1 text-2xl font-semibold text-stone-950">
                    Nova categoria
                </h2>

                <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-600">
                    Cadastre uma categoria para organizar e classificar os itens do acervo.
                </p>
            </section>

            <section class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
                <form
                    method="POST"
                    action="{{ route('admin.categorias.store') }}"
                    class="space-y-6"
                >
                    @csrf

                    <div>
                        <label
                            for="titulo"
                            class="block text-sm font-medium text-stone-900"
                        >
                            Título
                        </label>

                        <input
                            type="text"
                            id="titulo"
                            name="titulo"
                            value="{{ old('titulo') }}"
                            required
                            autofocus
                            class="mt-2 block w-full rounded-md border border-stone-300 px-3 py-2 text-sm text-stone-900 shadow-sm outline-none transition placeholder:text-stone-400 focus:border-[#173F35] focus:ring-2 focus:ring-[#173F35]"
                        >

                        @error('titulo')
                            <p class="mt-2 text-sm text-red-700">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label
                            for="descricao"
                            class="block text-sm font-medium text-stone-900"
                        >
                            Descrição
                        </label>

                        <textarea
                            id="descricao"
                            name="descricao"
                            rows="5"
                            class="mt-2 block w-full rounded-md border border-stone-300 px-3 py-2 text-sm text-stone-900 shadow-sm outline-none transition placeholder:text-stone-400 focus:border-[#173F35] focus:ring-2 focus:ring-[#173F35]"
                        >{{ old('descricao') }}</textarea>

                        @error('descricao')
                            <p class="mt-2 text-sm text-red-700">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-stone-200 pt-6">
                        <a
                            href="{{ route('admin.categorias.index') }}"
                            class="rounded-md border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2"
                        >
                            Cancelar
                        </a>

                        <button
                            type="submit"
                            class="rounded-md bg-[#173F35] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0f2b24] focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2"
                        >
                            Cadastrar categoria
                        </button>
                    </div>
                </form>
            </section>
    </div>
</x-layouts.admin>
