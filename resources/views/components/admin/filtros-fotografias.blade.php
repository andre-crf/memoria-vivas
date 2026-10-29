@props([
    'action',
    'filtros',
    'statusOptions',
    'visibilidades',
    'total',
    'searchError' => null,
    'ordenacao' => null,
])

<x-admin.busca-listagem
    :action="$action"
    :termo="$filtros->termo"
    placeholder="Título, legenda, local, evento, cedente, autor ou #ID"
    :total="$total"
    singular="fotografia encontrada"
    plural="fotografias encontradas"
    :search-error="$searchError"
    :possui-criterios="$filtros->possuiCriterios()"
    botao="Filtrar"
    limpar="Limpar filtros"
    titulo="Pesquisar e filtrar fotografias"
    :ordenacao="$ordenacao"
    {{ $attributes }}
>
    <x-catalog.label text="Status" class="md:w-48">
        <x-catalog.control as="select" id="filtro-status" name="status">
            <option value="">Todos</option>
            @foreach ($statusOptions as $valor => $rotulo)
                <option value="{{ $valor }}" @selected($filtros->status === $valor)>{{ $rotulo }}</option>
            @endforeach
        </x-catalog.control>
    </x-catalog.label>

    <x-catalog.label text="Visibilidade" class="md:w-48">
        <x-catalog.control as="select" id="filtro-visibilidade" name="visibilidade">
            <option value="">Todas</option>
            @foreach ($visibilidades as $visibilidade)
                <option value="{{ $visibilidade->value }}" @selected($filtros->visibilidade === $visibilidade->value)>{{ $visibilidade->label() }}</option>
            @endforeach
        </x-catalog.control>
    </x-catalog.label>
</x-admin.busca-listagem>
