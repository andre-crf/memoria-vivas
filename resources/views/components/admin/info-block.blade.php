@props([
    'label',
    'empty' => false,
])

{{-- Campo somente leitura em bloco de fundo sutil; deve ficar dentro de um <dl>. --}}
<div {{ $attributes->class('min-w-0 rounded-md bg-mist-50 px-4 py-3') }}>
    <dt class="text-[0.8rem] font-medium text-ink-600">{{ $label }}</dt>
    <dd @class(['mt-1 text-[0.9rem] leading-6 wrap-anywhere', 'text-ink-500' => $empty, 'text-ink-950' => ! $empty])>{{ $slot }}</dd>
</div>
