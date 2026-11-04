@props([
    'rotulo',
    'chave',
    'ordenacao',
    'action',
    'parametros' => [],
])

@php
    // O link leva pesquisa e filtros, alterna a direção e volta para a primeira página.
    $consulta = http_build_query([...$parametros, ...$ordenacao->linkPara($chave)]);
    $href = $action.($consulta !== '' ? '?'.$consulta : '');
    $ativa = $ordenacao->ativa($chave);
    $proxima = $ordenacao->proximaDirecao($chave) === 'asc' ? 'crescente' : 'decrescente';
@endphp

<th scope="col" aria-sort="{{ $ordenacao->ariaSort($chave) }}" {{ $attributes }}>
    <a
        href="{{ $href }}"
        aria-label="Ordenar por {{ mb_strtolower($rotulo) }}, ordem {{ $proxima }}"
        title="Ordenar por {{ mb_strtolower($rotulo) }}, ordem {{ $proxima }}"
        data-ordem="{{ $chave }}"
        @class([
            'group inline-flex items-center gap-1 rounded hover:text-ink-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-institutional',
            'text-institutional' => $ativa,
        ])
    >
        {{ $rotulo }}
        @if ($ativa && $ordenacao->direcao === 'asc')
            <x-lucide-arrow-up class="size-3.5 flex-none" aria-hidden="true" />
        @elseif ($ativa)
            <x-lucide-arrow-down class="size-3.5 flex-none" aria-hidden="true" />
        @else
            <x-lucide-chevrons-up-down class="size-3.5 flex-none opacity-40 group-hover:opacity-80" aria-hidden="true" />
        @endif
    </a>
</th>
