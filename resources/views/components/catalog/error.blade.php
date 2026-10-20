@props([
    'as' => 'small',
])

<{{ $as }} {{ $attributes->class('text-[0.72rem] leading-[1.4] text-danger') }}>{{ $slot }}</{{ $as }}>
