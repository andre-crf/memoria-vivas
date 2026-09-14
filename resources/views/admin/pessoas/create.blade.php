<x-layouts.admin title="Nova pessoa | Memórias Vivas">
    <div class="mx-auto max-w-3xl px-6 py-8">
        <section class="mb-6">
            <p class="text-sm font-medium text-[#6B5E2E]">Catalogação</p>
            <h2 class="mt-1 text-2xl font-semibold text-stone-950">Nova pessoa</h2>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-600">
                Cadastre uma pessoa identificada nos itens do acervo.
            </p>
        </section>

        <section class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('admin.pessoas.store') }}" class="space-y-6">
                @csrf

                <div>
                    <label for="nome" class="block text-sm font-medium text-stone-900">Nome</label>
                    <input
                        id="nome"
                        name="nome"
                        type="text"
                        value="{{ old('nome') }}"
                        required
                        maxlength="255"
                        autofocus
                        class="mt-2 block w-full rounded-md border border-stone-300 px-3 py-2 text-sm text-stone-900 shadow-sm outline-none transition focus:border-[#173F35] focus:ring-2 focus:ring-[#173F35]"
                    >
                    @error('nome')
                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="observacao" class="block text-sm font-medium text-stone-900">Observação</label>
                    <textarea
                        id="observacao"
                        name="observacao"
                        rows="5"
                        class="mt-2 block w-full rounded-md border border-stone-300 px-3 py-2 text-sm text-stone-900 shadow-sm outline-none transition focus:border-[#173F35] focus:ring-2 focus:ring-[#173F35]"
                    >{{ old('observacao') }}</textarea>
                    @error('observacao')
                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-stone-200 pt-6">
                    <a href="{{ route('admin.pessoas.index') }}" class="rounded-md border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2">Cancelar</a>
                    <button type="submit" class="rounded-md bg-[#173F35] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0f2b24] focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2">Cadastrar pessoa</button>
                </div>
            </form>
        </section>
    </div>
</x-layouts.admin>
