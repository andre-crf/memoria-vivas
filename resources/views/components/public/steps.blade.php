@props([
    'items',
])

{{-- $items: lista de [título, texto]; a numeração é gerada. --}}
<ol {{ $attributes->class('grid list-none grid-cols-1 gap-6 md:grid-cols-3 md:gap-5 lg:gap-8') }}>
    @foreach ($items as [$titulo, $texto])
        <li class="grid grid-cols-[2.5rem_minmax(0,1fr)] content-start gap-4 border-t border-mist-400 pt-5">
            <span class="text-[0.75rem] font-extrabold text-institutional" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
            <div>
                <h3 class="text-[1.05rem] font-[750] text-institutional">{{ $titulo }}</h3>
                <p class="mt-[0.55rem] text-[0.9rem] leading-[1.7] text-ink-600">{{ $texto }}</p>
            </div>
        </li>
    @endforeach
</ol>
