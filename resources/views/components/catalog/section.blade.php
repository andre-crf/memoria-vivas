@props([
    'title',
    'description' => null,
])

{{-- Bloco padrão dos formulários do admin. O slot "actions" fica à direita do título. --}}
<section {{ $attributes->class('rounded-lg border border-mist-200 bg-white p-5 shadow-[0_10px_28px_rgb(24_42_63/0.045)] sm:p-6') }}>
    <header class="mb-5 flex items-start justify-between gap-4 border-b border-mist-150 pb-4">
        <div class="min-w-0">
            <h2 class="text-[1.05rem] leading-[1.3] font-semibold text-ink-950">{{ $title }}</h2>
            @if ($description)
                <p class="mt-1 text-[0.82rem] leading-normal text-ink-500">{{ $description }}</p>
            @endif
        </div>
        {{ $actions ?? '' }}
    </header>

    {{ $slot }}
</section>
