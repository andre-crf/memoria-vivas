@php($iso = $iso())
@if ($iso === null)
    {{ $vazio }}
@else
    <time datetime="{{ $iso }}" data-data-hora data-formato="{{ $formato }}" {{ $attributes }}>{{ $texto() }}</time>
@endif
