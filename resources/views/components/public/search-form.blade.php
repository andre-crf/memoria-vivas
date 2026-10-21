@props([
    'catalogQuery' => [],
    'searchError' => null,
    'variant' => 'header',
])

@php
    $termo = (string) ($catalogQuery['q'] ?? '');
    $isPage = $variant === 'page';
@endphp

{{-- Busca do catálogo: a mesma no header e na página do catálogo; os filtros aplicados seguem como campos ocultos. --}}
<div {{ $attributes->class('min-w-0') }}>
    <form method="GET" action="{{ route('public.catalogo') }}" role="search" @class([
        'flex w-full min-w-0 items-center rounded-md border border-mist-300 bg-white focus-within:border-institutional focus-within:shadow-[0_0_0_3px_rgb(23_62_99/0.14)]',
        'md:min-w-[min(18rem,34vw)]' => ! $isPage,
        'h-12' => $isPage,
    ])>
        @if ($isPage)
            <x-lucide-search class="ml-3.5 size-4 flex-none text-ink-500" aria-hidden="true" />
        @endif
        <label class="sr-only" for="public-search">Buscar no catálogo</label>
        <input id="public-search" name="q" type="search" value="{{ $termo }}" maxlength="200" @if ($searchError) aria-invalid="true" aria-describedby="catalog-search-error" @endif placeholder="{{ $isPage ? 'Buscar por título ou descrição' : 'Buscar no catálogo' }}" autocomplete="off" @class([
            'min-w-0 flex-1 bg-transparent text-ink-950 outline-none',
            'px-[0.7rem] py-[0.58rem] text-[0.78rem]' => ! $isPage,
            'h-full px-3 text-[0.92rem]' => $isPage,
        ])>
        @foreach (['decada', 'categoria', 'assunto'] as $parametro)
            @if (isset($catalogQuery[$parametro]))
                <input type="hidden" name="{{ $parametro }}" value="{{ $catalogQuery[$parametro] }}">
            @endif
        @endforeach
        <button type="submit" aria-label="Buscar no catálogo" @class([
            'border-l border-mist-300 text-institutional',
            'px-[0.7rem] py-[0.58rem] text-[0.75rem] font-[750]' => ! $isPage,
            'h-full cursor-pointer rounded-r-md px-5 text-[0.82rem] font-semibold hover:bg-mist-50' => $isPage,
        ])>Buscar</button>
    </form>
    @if ($searchError)
        <p id="catalog-search-error" class="mt-2 max-w-88 text-[0.8rem] leading-normal text-error-strong" role="alert">{{ $searchError }}</p>
    @endif
</div>
