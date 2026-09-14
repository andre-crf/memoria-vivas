@php
    $fotografia ??= null;

    $value = fn (string $field, mixed $default = '') => old($field, $fotografia?->{$field} ?? $default);
    $selectedTipoData = old('tipo_data', $fotografia?->tipo_data?->value ?? 'desconhecida');
    $selectedEstadoConservacao = old('estado_conservacao', $fotografia?->estado_conservacao ?? 'desconhecido');
    $selectedStatus = old('status', $fotografia?->status ?? 'rascunho');
    $selectedVisibilidade = old('visibilidade', $fotografia?->visibilidade?->value ?? 'privado');
@endphp

<form method="POST" action="{{ $action }}" class="rounded-lg border border-[#D8E2EF] bg-white p-6 shadow-sm shadow-[#173F7A]/5" data-date-form>
    @csrf
    @isset($method)
        @method($method)
    @endisset

    <div class="grid gap-6">
        <div>
            <label for="titulo" class="block text-sm font-semibold text-[#173F7A]">Título</label>
            <input
                id="titulo"
                name="titulo"
                type="text"
                value="{{ $value('titulo') }}"
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
            >{{ $value('legenda') }}</textarea>
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
                        <option value="{{ $tipoData->value }}" @selected($selectedTipoData === $tipoData->value)>
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
                            value="{{ $value($field) }}"
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
                        value="{{ $value($field) }}"
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
                    @foreach ($estadoConservacaoOptions as $optionValue => $label)
                        <option value="{{ $optionValue }}" @selected($selectedEstadoConservacao === $optionValue)>{{ $label }}</option>
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
                    @foreach ($statusOptions as $optionValue => $label)
                        <option value="{{ $optionValue }}" @selected($selectedStatus === $optionValue)>{{ $label }}</option>
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
                        <option value="{{ $visibilidade->value }}" @selected($selectedVisibilidade === $visibilidade->value)>
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
            href="{{ $cancelUrl }}"
            class="inline-flex items-center justify-center rounded-md border border-[#D8E2EF] px-4 py-2 text-sm font-semibold text-[#173F7A] transition hover:bg-[#F4F8FC] focus:outline-none focus:ring-2 focus:ring-[#173F7A] focus:ring-offset-2"
        >
            Cancelar
        </a>
        <button
            type="submit"
            class="inline-flex items-center justify-center rounded-md bg-[#173F7A] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0E2A52] focus:outline-none focus:ring-2 focus:ring-[#173F7A] focus:ring-offset-2"
        >
            {{ $submitLabel }}
        </button>
    </div>
</form>

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
