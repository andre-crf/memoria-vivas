@props([
    'as' => 'input',
    'size' => 'md',
])

@php
    $classes = 'w-full rounded-md border border-mist-300 bg-white px-3 font-normal text-ink-950 outline-none transition-[border-color,box-shadow] duration-150 ease-[ease] focus:border-institutional focus:shadow-[0_0_0_3px_rgb(23_62_99/0.14)] ';
    $classes .= match (true) {
        $size === 'sm' => 'min-h-[2.35rem] py-[0.45rem] text-[0.78rem]',
        $as === 'textarea' => 'min-h-[5.5rem] resize-y py-[0.62rem] text-[0.875rem]',
        default => 'min-h-[2.65rem] py-[0.62rem] text-[0.875rem]',
    };
@endphp

@if ($as === 'input')
    <input {{ $attributes->class($classes) }}>
@else
    <{{ $as }} {{ $attributes->class($classes) }}>{{ $slot }}</{{ $as }}>
@endif
