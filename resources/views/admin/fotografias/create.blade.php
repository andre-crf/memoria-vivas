<x-layouts.app title="Cadastrar fotografia | Memórias Vivas">
    <div class="min-h-screen bg-stone-50">
        <header class="border-b border-stone-200 bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-6 py-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#6B5E2E]">Memórias Vivas</p>
                    <h1 class="mt-1 text-xl font-semibold text-stone-950">Administração do acervo</h1>
                </div>

                <div class="flex items-center gap-4">
                    <nav class="hidden items-center gap-2 md:flex" aria-label="Navegação administrativa">
                        <a
                            href="{{ route('admin.dashboard') }}"
                            class="rounded-md px-3 py-2 text-sm font-medium text-stone-600 transition hover:bg-stone-100 hover:text-stone-950 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2"
                        >
                            Painel
                        </a>
                        <a
                            href="{{ route('admin.fotografias.index') }}"
                            aria-current="page"
                            class="rounded-md bg-[#173F35] px-3 py-2 text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2"
                        >
                            Fotografias
                        </a>
                    </nav>

                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-medium text-stone-900">{{ auth()->user()->nome }}</p>
                        <p class="text-xs uppercase tracking-[0.12em] text-stone-500">{{ auth()->user()->role }}</p>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="rounded-md border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2"
                        >
                            Sair
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-6 py-8">
            <section class="mb-6">
                <a
                    href="{{ route('admin.fotografias.index') }}"
                    class="text-sm font-medium text-[#173F35] hover:text-[#0f2b24]"
                >
                    Voltar para fotografias
                </a>
                <h2 class="mt-3 text-2xl font-semibold text-stone-950">Cadastrar fotografia</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-600">
                    Registre os dados básicos de catalogação do novo item fotográfico.
                </p>
            </section>

            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <p class="font-semibold">Revise os campos destacados.</p>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.fotografias.store') }}" class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm" data-date-form>
                @csrf

                <div class="grid gap-6">
                    <div>
                        <label for="titulo" class="block text-sm font-semibold text-stone-900">Título</label>
                        <input
                            id="titulo"
                            name="titulo"
                            type="text"
                            value="{{ old('titulo') }}"
                            required
                            maxlength="255"
                            class="mt-2 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm text-stone-950 shadow-sm focus:border-[#173F35] focus:outline-none focus:ring-2 focus:ring-[#173F35]/20"
                        >
                        @error('titulo')
                            <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="legenda" class="block text-sm font-semibold text-stone-900">Legenda/descrição</label>
                        <textarea
                            id="legenda"
                            name="legenda"
                            rows="4"
                            class="mt-2 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm text-stone-950 shadow-sm focus:border-[#173F35] focus:outline-none focus:ring-2 focus:ring-[#173F35]/20"
                        >{{ old('legenda') }}</textarea>
                        @error('legenda')
                            <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="tipo_data" class="block text-sm font-semibold text-stone-900">Precisão da data</label>
                            <select
                                id="tipo_data"
                                name="tipo_data"
                                required
                                class="mt-2 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm text-stone-950 shadow-sm focus:border-[#173F35] focus:outline-none focus:ring-2 focus:ring-[#173F35]/20"
                            >
                                @foreach ($tipoDataOptions as $tipoData)
                                    <option value="{{ $tipoData->value }}" @selected(old('tipo_data', 'desconhecida') === $tipoData->value)>
                                        {{ $tipoData->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tipo_data')
                                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid gap-4 sm:grid-cols-4">
                            <div data-date-for="dia">
                                <label for="dia" class="block text-sm font-semibold text-stone-900">Dia</label>
                                <input
                                    id="dia"
                                    name="dia"
                                    type="number"
                                    value="{{ old('dia') }}"
                                    min="1"
                                    max="31"
                                    class="mt-2 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm text-stone-950 shadow-sm focus:border-[#173F35] focus:outline-none focus:ring-2 focus:ring-[#173F35]/20"
                                >
                                @error('dia')
                                    <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                                @enderror
                            </div>

                            <div data-date-for="mes">
                                <label for="mes" class="block text-sm font-semibold text-stone-900">Mês</label>
                                <input
                                    id="mes"
                                    name="mes"
                                    type="number"
                                    value="{{ old('mes') }}"
                                    min="1"
                                    max="12"
                                    class="mt-2 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm text-stone-950 shadow-sm focus:border-[#173F35] focus:outline-none focus:ring-2 focus:ring-[#173F35]/20"
                                >
                                @error('mes')
                                    <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                                @enderror
                            </div>

                            <div data-date-for="ano">
                                <label for="ano" class="block text-sm font-semibold text-stone-900">Ano</label>
                                <input
                                    id="ano"
                                    name="ano"
                                    type="number"
                                    value="{{ old('ano') }}"
                                    min="1000"
                                    max="{{ date('Y') }}"
                                    class="mt-2 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm text-stone-950 shadow-sm focus:border-[#173F35] focus:outline-none focus:ring-2 focus:ring-[#173F35]/20"
                                >
                                @error('ano')
                                    <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                                @enderror
                            </div>

                            <div data-date-for="decada">
                                <label for="decada" class="block text-sm font-semibold text-stone-900">Década</label>
                                <input
                                    id="decada"
                                    name="decada"
                                    type="text"
                                    value="{{ old('decada') }}"
                                    inputmode="numeric"
                                    maxlength="4"
                                    class="mt-2 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm text-stone-950 shadow-sm focus:border-[#173F35] focus:outline-none focus:ring-2 focus:ring-[#173F35]/20"
                                >
                                @error('decada')
                                    <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="local_atual" class="block text-sm font-semibold text-stone-900">Local atual</label>
                            <input
                                id="local_atual"
                                name="local_atual"
                                type="text"
                                value="{{ old('local_atual') }}"
                                maxlength="255"
                                class="mt-2 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm text-stone-950 shadow-sm focus:border-[#173F35] focus:outline-none focus:ring-2 focus:ring-[#173F35]/20"
                            >
                            @error('local_atual')
                                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="local_epoca" class="block text-sm font-semibold text-stone-900">Local na época</label>
                            <input
                                id="local_epoca"
                                name="local_epoca"
                                type="text"
                                value="{{ old('local_epoca') }}"
                                maxlength="255"
                                class="mt-2 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm text-stone-950 shadow-sm focus:border-[#173F35] focus:outline-none focus:ring-2 focus:ring-[#173F35]/20"
                            >
                            @error('local_epoca')
                                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="evento" class="block text-sm font-semibold text-stone-900">Evento relacionado</label>
                            <input
                                id="evento"
                                name="evento"
                                type="text"
                                value="{{ old('evento') }}"
                                maxlength="255"
                                class="mt-2 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm text-stone-950 shadow-sm focus:border-[#173F35] focus:outline-none focus:ring-2 focus:ring-[#173F35]/20"
                            >
                            @error('evento')
                                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="cedente" class="block text-sm font-semibold text-stone-900">Cedente</label>
                            <input
                                id="cedente"
                                name="cedente"
                                type="text"
                                value="{{ old('cedente') }}"
                                maxlength="255"
                                class="mt-2 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm text-stone-950 shadow-sm focus:border-[#173F35] focus:outline-none focus:ring-2 focus:ring-[#173F35]/20"
                            >
                            @error('cedente')
                                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label for="estado_conservacao" class="block text-sm font-semibold text-stone-900">Estado de conservação</label>
                            <select
                                id="estado_conservacao"
                                name="estado_conservacao"
                                required
                                class="mt-2 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm text-stone-950 shadow-sm focus:border-[#173F35] focus:outline-none focus:ring-2 focus:ring-[#173F35]/20"
                            >
                                @foreach ($estadoConservacaoOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('estado_conservacao', 'desconhecido') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('estado_conservacao')
                                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-semibold text-stone-900">Status</label>
                            <select
                                id="status"
                                name="status"
                                required
                                class="mt-2 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm text-stone-950 shadow-sm focus:border-[#173F35] focus:outline-none focus:ring-2 focus:ring-[#173F35]/20"
                            >
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', 'rascunho') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="visibilidade" class="block text-sm font-semibold text-stone-900">Visibilidade</label>
                            <select
                                id="visibilidade"
                                name="visibilidade"
                                required
                                class="mt-2 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm text-stone-950 shadow-sm focus:border-[#173F35] focus:outline-none focus:ring-2 focus:ring-[#173F35]/20"
                            >
                                @foreach ($visibilidadeOptions as $visibilidade)
                                    <option value="{{ $visibilidade->value }}" @selected(old('visibilidade', 'privado') === $visibilidade->value)>
                                        {{ $visibilidade->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('visibilidade')
                                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex flex-col-reverse gap-3 border-t border-stone-200 pt-6 sm:flex-row sm:justify-end">
                    <a
                        href="{{ route('admin.fotografias.index') }}"
                        class="inline-flex items-center justify-center rounded-md border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2"
                    >
                        Cancelar
                    </a>
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-md bg-[#173F35] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0f2b24] focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2"
                    >
                        Salvar fotografia
                    </button>
                </div>
            </form>
        </main>
    </div>

    <script>
        (() => {
            const form = document.querySelector('[data-date-form]');
            const select = form?.querySelector('[name="tipo_data"]');
            const fieldsByPrecision = {
                data_exata: ['dia', 'mes', 'ano'],
                mes_ano: ['mes', 'ano'],
                ano: ['ano'],
                decada: ['decada'],
                desconhecida: [],
            };

            const updateDateFields = () => {
                const visibleFields = fieldsByPrecision[select.value] ?? [];

                form.querySelectorAll('[data-date-for]').forEach((field) => {
                    const input = field.querySelector('input');
                    const isVisible = visibleFields.includes(field.dataset.dateFor);

                    field.hidden = !isVisible;
                    input.disabled = !isVisible;
                });
            };

            select?.addEventListener('change', updateDateFields);
            updateDateFields();
        })();
    </script>
</x-layouts.app>
