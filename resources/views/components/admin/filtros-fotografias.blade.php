@props([
    'action',
    'filtros',
    'statusOptions',
    'visibilidades',
    'total',
    'searchError' => null,
])

{{-- Pesquisa e filtros das listagens de fotografias; o envio é um GET comum, então a URL guarda os critérios. --}}
<section {{ $attributes->class('mb-6 rounded-lg border border-mist-200 bg-white p-4 shadow-sm sm:p-5') }} aria-labelledby="filtros-fotografias-titulo">
    <h2 id="filtros-fotografias-titulo" class="sr-only">Pesquisar e filtrar fotografias</h2>

    <form method="GET" action="{{ $action }}" role="search" class="grid gap-3 md:grid-cols-[minmax(0,1fr)_12rem_12rem_auto] md:items-end">
        <x-catalog.label text="Pesquisar">
            <x-catalog.control
                type="search"
                id="filtro-q"
                name="q"
                value="{{ $filtros->termo }}"
                maxlength="200"
                placeholder="Título, legenda, local, evento, cedente, autor ou #ID"
                autocomplete="off"
                :aria-invalid="$searchError ? 'true' : null"
                :aria-describedby="$searchError ? 'filtro-q-erro' : null"
            />
        </x-catalog.label>

        <x-catalog.label text="Status">
            <x-catalog.control as="select" id="filtro-status" name="status">
                <option value="">Todos</option>
                @foreach ($statusOptions as $valor => $rotulo)
                    <option value="{{ $valor }}" @selected($filtros->status === $valor)>{{ $rotulo }}</option>
                @endforeach
            </x-catalog.control>
        </x-catalog.label>

        <x-catalog.label text="Visibilidade">
            <x-catalog.control as="select" id="filtro-visibilidade" name="visibilidade">
                <option value="">Todas</option>
                @foreach ($visibilidades as $visibilidade)
                    <option value="{{ $visibilidade->value }}" @selected($filtros->visibilidade === $visibilidade->value)>{{ $visibilidade->label() }}</option>
                @endforeach
            </x-catalog.control>
        </x-catalog.label>

        <div class="flex gap-2">
            <x-catalog.button type="submit" icon="search">Filtrar</x-catalog.button>
            @if ($filtros->possuiCriterios())
                <x-catalog.button variant="secondary" :href="$action">Limpar filtros</x-catalog.button>
            @endif
        </div>
    </form>

    @if ($searchError)
        <x-catalog.error as="p" id="filtro-q-erro" class="mt-2" role="alert">{{ $searchError }}</x-catalog.error>
    @endif

    @if ($filtros->possuiCriterios())
        <p class="mt-3 text-[0.8rem] text-ink-600" role="status">
            {{ $total === 1 ? '1 fotografia encontrada' : number_format($total, 0, ',', '.').' fotografias encontradas' }}
        </p>
    @endif
</section>
