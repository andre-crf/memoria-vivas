@props([
    'groups',
])

{{-- $groups: rótulo => lista de nomes. --}}
<dl {{ $attributes->class('mt-5 grid gap-5') }}>
    @foreach ($groups as $label => $items)
        <div>
            <dt class="text-[0.72rem] font-extrabold tracking-[0.08em] text-ink-600 uppercase">{{ $label }}</dt>
            <dd class="mt-[0.55rem]"><x-public.tag-list :items="$items" /></dd>
        </div>
    @endforeach
</dl>
