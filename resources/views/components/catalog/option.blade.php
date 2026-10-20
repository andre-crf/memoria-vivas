@props([
    'text',
])

{{-- O slot recebe o checkbox, que varia entre o formulário tradicional e o Livewire. --}}
<label {{ $attributes->class('flex min-h-[2.45rem] cursor-pointer items-center gap-[0.55rem] border-b border-mist-150 px-[0.7rem] py-[0.55rem] text-[0.78rem] text-ink-800 last-of-type:border-b-0 hover:bg-institutional-soft [&>input]:size-4 [&>input]:accent-institutional') }} data-option data-search-value="{{ mb_strtolower($text) }}">
    {{ $slot }}
    <span>{{ $text }}</span>
</label>
