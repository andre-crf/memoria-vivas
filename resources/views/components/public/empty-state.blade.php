@props([
    'title',
])

<div {{ $attributes->class('grid max-w-[42rem] gap-[0.45rem] rounded-lg border border-dashed border-mist-500 p-8 text-ink-600') }}>
    <strong class="text-institutional">{{ $title }}</strong>
    {{ $slot }}
</div>
