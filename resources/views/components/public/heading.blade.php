@props([
    'as' => 'h2',
])

{{-- Título serifado das seções públicas. O tamanho e a largura máxima variam por seção e vêm de quem usa. --}}
<{{ $as }} {{ $attributes->class('font-serif font-semibold wrap-anywhere text-institutional') }}>{{ $slot }}</{{ $as }}>
