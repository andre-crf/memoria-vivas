<x-layouts.admin title="Editar palavra-chave | Memórias Vivas">
    <div class="mx-auto max-w-3xl px-6 py-8">
            <section class="mb-6">
                <p class="text-sm font-medium text-[#6B5E2E]">Catalogação</p>
                <h2 class="mt-1 text-2xl font-semibold text-stone-950">Editar palavra-chave</h2>
                <p class="mt-2 text-sm leading-6 text-stone-600">Atualize o termo da palavra-chave cadastrada.</p>
            </section>

            <section class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
                @if ($errors->any())
                    <div class="mb-6 rounded-md border border-red-200 bg-red-50 p-4">
                        <p class="text-sm font-medium text-red-800">Verifique os dados informados.</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.palavras-chave.update', $palavraChave) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="termo" class="block text-sm font-medium text-stone-900">Termo</label>
                        <input type="text" id="termo" name="termo" value="{{ old('termo', $palavraChave->termo) }}" required maxlength="255" autofocus class="mt-2 block w-full rounded-md border border-stone-300 px-3 py-2 text-sm text-stone-900 shadow-sm outline-none transition placeholder:text-stone-400 focus:border-[#173F35] focus:ring-2 focus:ring-[#173F35]">
                        @error('termo')
                            <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-stone-200 pt-6">
                        <a href="{{ route('admin.palavras-chave.index') }}" class="rounded-md border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2">Cancelar</a>
                        <button type="submit" class="rounded-md bg-[#173F35] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0f2b24] focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2">Salvar alterações</button>
                    </div>
                </form>
            </section>
    </div>
</x-layouts.admin>
