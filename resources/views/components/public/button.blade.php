@php
    $classes = 'inline-flex items-center gap-3 rounded-md bg-institutional px-4 py-[0.78rem] text-[0.82rem] font-bold text-white hover:bg-institutional-strong [&_span]:text-[1rem] [&_span]:leading-none';
@endphp

@if ($attributes->has('href'))
    <a {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    {{-- Desabilitado durante um envio (ex.: colaboração): aria-busy indica a espera. --}}
    <button {{ $attributes->merge(['type' => 'button'])->class([$classes, 'cursor-pointer disabled:cursor-not-allowed disabled:opacity-65 aria-busy:cursor-wait']) }}>{{ $slot }}</button>
@endif
