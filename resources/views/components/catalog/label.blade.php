@props([
    'text',
    'required' => false,
])

{{-- O peso e a cor de rótulo ficam só no texto: dicas, contadores e erros do slot mantêm a aparência própria. --}}
<label {{ $attributes->class('grid min-w-0 content-start gap-[0.42rem] text-label') }}>
    <span class="font-[650] text-ink-900">{{ $text }}@if ($required) <strong class="ml-[0.35rem] text-[0.62rem] font-bold text-ink-400 uppercase">obrigatório</strong>@endif</span>
    {{ $slot }}
</label>
