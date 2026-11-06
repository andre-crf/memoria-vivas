@props([
    'name',
])

{{-- Valor atual do token, lido do CSS gerado: o Style Guide não guarda uma cópia da paleta. --}}
<code
    x-data
    x-text="getComputedStyle(document.documentElement).getPropertyValue(@js($name)).trim() || 'não gerado'"
    {{ $attributes->class('font-mono text-caption text-fg-subtle') }}
></code>
