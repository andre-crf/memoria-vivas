@props([
    'eyebrow',
    'title',
    'description',
])

<section {{ $attributes->class('rounded-lg border border-mist-200 bg-white p-[1.1rem] shadow-[0_10px_28px_rgb(24_42_63/0.045)] sm:p-6') }}>
    <div class="mb-[1.35rem] gap-8 border-b border-mist-150 pb-4 sm:flex sm:items-start sm:justify-between">
        <div>
            <p class="text-[0.65rem] font-extrabold text-institutional uppercase">{{ $eyebrow }}</p>
            <h2 class="mt-[0.15rem] font-serif text-[1.35rem] leading-[1.2] text-ink-950">{{ $title }}</h2>
        </div>
        <p class="mt-2 max-w-[25rem] text-[0.8rem] leading-[1.55] text-ink-500 sm:mt-0 sm:text-right">{{ $description }}</p>
    </div>

    {{ $slot }}
</section>
