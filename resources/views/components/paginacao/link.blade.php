@props([
    'destino' => [],
    'atual' => false,
    'desabilitado' => false,
    'rotulo' => null,
])

@php
    $classes = 'relative inline-flex min-h-9 min-w-9 items-center justify-center border px-3 text-[0.82rem] font-medium transition-colors focus-visible:z-10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-institutional ';
    $classes .= match (true) {
        $atual => 'z-10 border-institutional bg-institutional text-white',
        $desabilitado => 'cursor-not-allowed border-mist-300 bg-mist-50 text-ink-400',
        default => 'cursor-pointer border-mist-300 bg-white text-ink-700 hover:bg-mist-100 hover:text-ink-950',
    };
@endphp

@if ($atual)
    <span aria-current="page" {{ $attributes->class($classes) }}>{{ $slot }}</span>
@elseif ($desabilitado)
    <span aria-disabled="true" @if ($rotulo) aria-label="{{ $rotulo }}" @endif {{ $attributes->class($classes) }}>{{ $slot }}</span>
@elseif (isset($destino['acao']))
    <button type="button" wire:click="{!! $destino['acao'] !!}" x-on:click="$el.closest('body').scrollIntoView()" wire:loading.attr="disabled" @if ($rotulo) aria-label="{{ $rotulo }}" @endif {{ $attributes->class($classes) }}>{{ $slot }}</button>
@else
    <a href="{{ $destino['href'] }}" @if ($rotulo) aria-label="{{ $rotulo }}" @endif {{ $attributes->class($classes) }}>{{ $slot }}</a>
@endif
