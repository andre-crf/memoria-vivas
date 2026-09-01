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

        <form method="POST" action="{{ route('admin.fotografias.store') }}" class="rounded-lg border border-[#D8E2EF] bg-white p-6 shadow-sm shadow-[#173F7A]/5" data-date-form>
            @csrf

            <div class="grid gap-6">
                <div>
                    <label for="titulo" class="block text-sm font-semibold text-[#173F7A]">Título</label>
                    <input
                        id="titulo"
                        name="titulo"
                        type="text"
                        value="{{ old('titulo') }}"
                        required
                        maxlength="255"
                        class="mt-2 block w-full rounded-md border border-[#C8D6E8] bg-white px-3 py-2 text-sm text-[#173F7A] shadow-sm focus:border-[#173F7A] focus:outline-none focus:ring-2 focus:ring-[#173F7A]/20"
                    >
                    @error('titulo')
                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="legenda" class="block text-sm font-semibold text-[#173F7A]">Legenda/descrição</label>
                    <textarea
                        id="legenda"
                        name="legenda"
                        rows="4"
                        class="mt-2 block w-full rounded-md border border-[#C8D6E8] bg-white px-3 py-2 text-sm text-[#173F7A] shadow-sm focus:border-[#173F7A] focus:outline-none focus:ring-2 focus:ring-[#173F7A]/20"
                    >{{ old('legenda') }}</textarea>
                    @error('legenda')
                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-4 lg:grid-cols-[minmax(220px,0.8fr)_minmax(0,1.2fr)]">
                    <div>
                        <label for="tipo_data" class="block text-sm font-semibold text-[#173F7A]">Precisão da data</label>
                        <select
                            id="tipo_data"
                            name="tipo_data"
                            required
                            class="mt-2 block w-full rounded-md border border-[#C8D6E8] bg-white px-3 py-2 text-sm text-[#173F7A] shadow-sm focus:border-[#173F7A] focus:outline-none focus:ring-2 focus:ring-[#173F7A]/20"
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
                        @foreach (['dia' => 'Dia', 'mes' => 'Mês', 'ano' => 'Ano', 'decada' => 'Década'] as $field => $label)
                            <div data-date-for="{{ $field }}">
                                <label for="{{ $field }}" class="block text-sm font-semibold text-[#173F7A]">{{ $label }}</label>
                                <input
                                    id="{{ $field }}"
                                    name="{{ $field }}"
                                    type="{{ $field === 'decada' ? 'text' : 'number' }}"
                                    value="{{ old($field) }}"
                                    @if ($field === 'dia') min="1" max="31" @endif
                                    @if ($field === 'mes') min="1" max="12" @endif
                                    @if ($field === 'ano') min="1000" max="{{ date('Y') }}" @endif
                                    @if ($field === 'decada') inputmode="numeric" maxlength="4" @endif
                                    class="mt-2 block w-full rounded-md border border-[#C8D6E8] bg-white px-3 py-2 text-sm text-[#173F7A] shadow-sm focus:border-[#173F7A] focus:outline-none focus:ring-2 focus:ring-[#173F7A]/20"
                                >
                                @error($field)
                                    <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ([
                        'local_atual' => 'Local atual',
                        'local_epoca' => 'Local na época',
                        'evento' => 'Evento relacionado',
                        'cedente' => 'Cedente',
                    ] as $field => $label)
                        <div>
                            <label for="{{ $field }}" class="block text-sm font-semibold text-[#173F7A]">{{ $label }}</label>
                            <input
                                id="{{ $field }}"
                                name="{{ $field }}"
                                type="text"
                                value="{{ old($field) }}"
                                maxlength="255"
                                class="mt-2 block w-full rounded-md border border-[#C8D6E8] bg-white px-3 py-2 text-sm text-[#173F7A] shadow-sm focus:border-[#173F7A] focus:outline-none focus:ring-2 focus:ring-[#173F7A]/20"
                            >
                            @error($field)
                                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label for="estado_conservacao" class="block text-sm font-semibold text-[#173F7A]">Estado de conservação</label>
                        <select
                            id="estado_conservacao"
                            name="estado_conservacao"
                            required
                            class="mt-2 block w-full rounded-md border border-[#C8D6E8] bg-white px-3 py-2 text-sm text-[#173F7A] shadow-sm focus:border-[#173F7A] focus:outline-none focus:ring-2 focus:ring-[#173F7A]/20"
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
                        <label for="status" class="block text-sm font-semibold text-[#173F7A]">Status</label>
                        <select
                            id="status"
                            name="status"
                            required
                            class="mt-2 block w-full rounded-md border border-[#C8D6E8] bg-white px-3 py-2 text-sm text-[#173F7A] shadow-sm focus:border-[#173F7A] focus:outline-none focus:ring-2 focus:ring-[#173F7A]/20"
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
                        <label for="visibilidade" class="block text-sm font-semibold text-[#173F7A]">Visibilidade</label>
                        <select
                            id="visibilidade"
                            name="visibilidade"
                            required
                            class="mt-2 block w-full rounded-md border border-[#C8D6E8] bg-white px-3 py-2 text-sm text-[#173F7A] shadow-sm focus:border-[#173F7A] focus:outline-none focus:ring-2 focus:ring-[#173F7A]/20"
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

            <div class="mt-8 flex flex-col-reverse gap-3 border-t border-[#D8E2EF] pt-6 sm:flex-row sm:justify-end">
                <a
                    href="{{ route('admin.fotografias.index') }}"
                    class="inline-flex items-center justify-center rounded-md border border-[#D8E2EF] px-4 py-2 text-sm font-semibold text-[#173F7A] transition hover:bg-[#F4F8FC] focus:outline-none focus:ring-2 focus:ring-[#173F7A] focus:ring-offset-2"
                >
                    Cancelar
                </a>
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-md bg-[#173F7A] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0E2A52] focus:outline-none focus:ring-2 focus:ring-[#173F7A] focus:ring-offset-2"
                >
                    Salvar fotografia
                </button>
            </div>
        </form>
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
</x-layouts.admin>
