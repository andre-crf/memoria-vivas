@props([
    'action',
    'termo' => '',
    'placeholder' => 'Pesquisar',
    'total' => 0,
    'singular' => 'registro encontrado',
    'plural' => 'registros encontrados',
    'searchError' => null,
    'possuiCriterios' => null,
    'botao' => 'Pesquisar',
    'limpar' => 'Limpar pesquisa',
    'titulo' => 'Pesquisar na listagem',
    'ordenacao' => null,
])

@php
    // Filtros extras (selects) entram pelo slot; sem eles, os critérios são só o termo.
    $possuiCriterios ??= $termo !== '';
@endphp

{{-- Pesquisa das listagens administrativas; o envio é um GET comum, então a URL guarda os critérios. --}}
<section {{ $attributes->class('mb-6 rounded-lg border border-mist-200 bg-white p-4 shadow-sm sm:p-5') }} aria-label="{{ $titulo }}">
    <form method="GET" action="{{ $action }}" role="search" class="flex flex-col gap-3 md:flex-row md:items-end">
        <x-catalog.label text="Pesquisar" class="md:flex-1">
            <x-catalog.control
                type="search"
                id="busca-q"
                name="q"
                value="{{ $termo }}"
                maxlength="200"
                :placeholder="$placeholder"
                autocomplete="off"
                :aria-invalid="$searchError ? 'true' : null"
                :aria-describedby="$searchError ? 'busca-q-erro' : null"
            />
        </x-catalog.label>

        {{ $slot }}

        {{-- Pesquisar ou filtrar mantém a ordenação escolhida nos cabeçalhos. --}}
        @foreach ($ordenacao?->parametros() ?? [] as $nome => $valor)
            <input type="hidden" name="{{ $nome }}" value="{{ $valor }}">
        @endforeach

        <div class="flex gap-2">
            <x-catalog.button type="submit" icon="search">{{ $botao }}</x-catalog.button>
            @if ($possuiCriterios)
                <x-catalog.button variant="secondary" :href="$action">{{ $limpar }}</x-catalog.button>
            @endif
        </div>
    </form>

    @if ($searchError)
        <x-catalog.error as="p" id="busca-q-erro" class="mt-2" role="alert">{{ $searchError }}</x-catalog.error>
    @endif

    @if ($possuiCriterios)
        <p class="mt-3 text-[0.8rem] text-ink-600" role="status">
            {{ $total === 1 ? "1 {$singular}" : number_format($total, 0, ',', '.')." {$plural}" }}
        </p>
    @endif
</section>
