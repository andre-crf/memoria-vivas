@props([
    'text',
    'required' => false,
])

<label {{ $attributes->class('grid min-w-0 content-start gap-[0.42rem] text-[0.78rem] font-[650] text-ink-900') }}>
    <span>{{ $text }}@if ($required) <strong class="ml-[0.35rem] text-[0.62rem] font-bold text-ink-400 uppercase">obrigatório</strong>@endif</span>
    {{ $slot }}
</label>
