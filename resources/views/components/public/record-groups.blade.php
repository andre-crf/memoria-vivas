@props([
    'groups',
])

{{-- $groups: rótulo => lista de nomes, exibidos como tags. --}}
<dl {{ $attributes->class('grid grid-cols-[minmax(7.5rem,auto)_1fr] border-b border-mist-200') }}>
    @foreach ($groups as $label => $items)
        <dt class="border-t border-mist-200 py-2.5 pr-4 text-[0.82rem] text-ink-500">{{ $label }}</dt>
        <dd class="border-t border-mist-200 py-2.5"><x-public.tag-list :items="$items" /></dd>
    @endforeach
</dl>
