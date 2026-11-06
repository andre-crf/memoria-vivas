@props([
    'as' => 'small',
    // Opcional: renderiza sozinho a primeira mensagem do campo (nada, se não houver erro), com id "{for}-erro".
    'field' => null,
    'bag' => 'default',
    // Id do controle descrito pela mensagem; por padrão, o próprio nome do campo.
    'for' => null,
])

@php
    $classes = 'text-caption font-medium text-danger';
    $message = $field !== null && isset($errors) ? $errors->getBag($bag)->first($field) : null;
@endphp

@if ($field === null)
    <{{ $as }} {{ $attributes->class($classes) }}>{{ $slot }}</{{ $as }}>
@elseif ($message !== '')
    <{{ $as }} {{ $attributes->merge(['id' => ($for ?? $field).'-erro'])->class($classes) }}>{{ $message }}</{{ $as }}>
@endif
