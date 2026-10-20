@props([
    'items',
])

<ul {{ $attributes->class('flex list-none flex-wrap gap-2') }}>
    @foreach ($items as $item)
        <li class="rounded-full border border-mist-350 bg-white px-[0.7rem] py-[0.4rem] text-[0.82rem] wrap-anywhere text-institutional">{{ $item }}</li>
    @endforeach
</ul>
