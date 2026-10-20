@props([
    'title',
    'subtitle',
    'open' => false,
])

{{-- Os atributos extras (x-data, classes de grid) vão para o corpo da seção. --}}
<details @if ($open) open @endif class="group/disclosure rounded-lg border border-mist-200 bg-white shadow-[0_10px_28px_rgb(24_42_63/0.045)]">
    <summary class="flex min-h-[4.5rem] cursor-pointer list-none items-center justify-between gap-4 px-4 py-4 sm:px-6 [&::-webkit-details-marker]:hidden after:grid after:size-[1.8rem] after:flex-none after:place-items-center after:rounded-full after:border after:border-mist-300 after:text-[1rem] after:text-institutional after:content-['+'] group-open/disclosure:after:content-['\2212']">
        <span class="grid gap-[0.2rem]">
            <strong class="font-serif text-[1.05rem] text-ink-950">{{ $title }}</strong>
            <small class="text-[0.75rem] text-ink-500">{{ $subtitle }}</small>
        </span>
    </summary>

    <div {{ $attributes->class('border-t border-mist-150 px-4 pt-5 pb-6 sm:px-6') }}>
        {{ $slot }}
    </div>
</details>
