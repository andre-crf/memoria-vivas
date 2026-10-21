@props([
    'items',
])

<ul {{ $attributes->class('flex list-none flex-wrap gap-1.5') }}>
    @foreach ($items as $item)
        <li class="rounded-full border border-mist-300 bg-mist-50 px-2.5 py-1 text-[0.8rem] leading-[1.35] wrap-anywhere text-institutional">{{ $item }}</li>
    @endforeach
</ul>
