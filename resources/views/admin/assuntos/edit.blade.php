<x-layouts.app title="Editar assunto | Memórias Vivas">
    <div class="min-h-screen bg-stone-50">
        <header class="border-b border-stone-200 bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-6 py-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#6B5E2E]">Memórias Vivas</p>
                    <h1 class="mt-1 text-xl font-semibold text-stone-950">Administração do acervo</h1>
                </div>

                <div class="flex items-center gap-4">
                    <nav class="hidden items-center gap-2 md:flex" aria-label="Navegação administrativa">
                        <a href="{{ route('admin.dashboard') }}" class="rounded-md px-3 py-2 text-sm font-medium text-stone-600 transition hover:bg-stone-100 hover:text-stone-950 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2">Painel</a>
                        <a href="{{ route('admin.fotografias.index') }}" class="rounded-md px-3 py-2 text-sm font-medium text-stone-600 transition hover:bg-stone-100 hover:text-stone-950 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2">Fotografias</a>
                        <a href="{{ route('admin.categorias.index') }}" class="rounded-md px-3 py-2 text-sm font-medium text-stone-600 transition hover:bg-stone-100 hover:text-stone-950 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2">Categorias</a>
                        <a href="{{ route('admin.assuntos.index') }}" aria-current="page" class="rounded-md bg-[#173F35] px-3 py-2 text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2">Assuntos</a>
                    </nav>

                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-medium text-stone-900">{{ auth()->user()->nome }}</p>
                        <p class="text-xs uppercase tracking-[0.12em] text-stone-500">{{ auth()->user()->role }}</p>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-md border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2">Sair</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-6 py-8">
            <section class="mb-6">
                <p class="text-sm font-medium text-[#6B5E2E]">Catalogação</p>
                <h2 class="mt-1 text-2xl font-semibold text-stone-950">Editar assunto</h2>
                <p class="mt-2 text-sm leading-6 text-stone-600">Atualize os dados do assunto cadastrado.</p>
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

                <form method="POST" action="{{ route('admin.assuntos.update', $assunto) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="titulo" class="block text-sm font-medium text-stone-900">Título</label>
                        <input type="text" id="titulo" name="titulo" value="{{ old('titulo', $assunto->titulo) }}" required maxlength="255" autofocus class="mt-2 block w-full rounded-md border border-stone-300 px-3 py-2 text-sm text-stone-900 shadow-sm outline-none transition placeholder:text-stone-400 focus:border-[#173F35] focus:ring-2 focus:ring-[#173F35]">
                        @error('titulo')
                            <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="descricao" class="block text-sm font-medium text-stone-900">Descrição</label>
                        <textarea id="descricao" name="descricao" rows="5" class="mt-2 block w-full rounded-md border border-stone-300 px-3 py-2 text-sm text-stone-900 shadow-sm outline-none transition placeholder:text-stone-400 focus:border-[#173F35] focus:ring-2 focus:ring-[#173F35]">{{ old('descricao', $assunto->descricao) }}</textarea>
                        @error('descricao')
                            <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-stone-200 pt-6">
                        <a href="{{ route('admin.assuntos.index') }}" class="rounded-md border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2">Cancelar</a>
                        <button type="submit" class="rounded-md bg-[#173F35] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0f2b24] focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2">Salvar alterações</button>
                    </div>
                </form>
            </section>
        </main>
    </div>
</x-layouts.app>
