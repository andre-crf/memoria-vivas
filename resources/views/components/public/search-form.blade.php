@props([
    'catalogQuery' => [],
    'searchError' => null,
])

@php
    $termo = (string) ($catalogQuery['q'] ?? '');
@endphp

{{-- Busca do catálogo; os filtros aplicados seguem como campos ocultos. A lupa do header aponta para #busca e foca o campo. --}}
<div
    id="busca"
    x-data="{ focarSeBusca() { if (location.hash === '#busca') this.$root.querySelector('#public-search')?.focus() } }"
    x-init="$nextTick(() => focarSeBusca())"
    @hashchange.window="focarSeBusca()"
    {{ $attributes->class('min-w-0 scroll-mt-6') }}
>
    <form method="GET" action="{{ route('public.catalogo') }}" role="search" class="flex h-12 w-full min-w-0 items-center rounded-md border border-mist-300 bg-white focus-within:border-institutional focus-within:shadow-[0_0_0_3px_rgb(23_62_99/0.14)]">
        <x-lucide-search class="ml-3.5 size-4 flex-none text-ink-500" aria-hidden="true" />
        <label class="sr-only" for="public-search">Buscar no catálogo</label>
        <input id="public-search" name="q" type="search" value="{{ $termo }}" maxlength="200" @if ($searchError) aria-invalid="true" aria-describedby="catalog-search-error" @endif placeholder="Buscar por título, descrição ou local" autocomplete="off" class="h-full min-w-0 flex-1 bg-transparent px-3 text-[0.92rem] text-ink-950 outline-none">
        @foreach (['decada', 'categoria', 'assunto'] as $parametro)
            @if (isset($catalogQuery[$parametro]))
                <input type="hidden" name="{{ $parametro }}" value="{{ $catalogQuery[$parametro] }}">
            @endif
        @endforeach
        <button type="submit" aria-label="Buscar no catálogo" class="h-full cursor-pointer rounded-r-md border-l border-mist-300 px-5 text-[0.82rem] font-semibold text-institutional hover:bg-mist-50">Buscar</button>
    </form>
    @if ($searchError)
        <p id="catalog-search-error" class="mt-2 max-w-88 text-[0.8rem] leading-normal text-error-strong" role="alert">{{ $searchError }}</p>
    @endif
</div>
