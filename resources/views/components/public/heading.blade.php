@props([
    'as' => 'h2',
])

{{-- Título serifado das seções públicas. Tamanho, peso e largura máxima variam por seção e vêm de quem usa. --}}
<{{ $as }} {{ $attributes->class('font-serif wrap-anywhere text-institutional') }}>{{ $slot }}</{{ $as }}>
