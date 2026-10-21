@php
    // Mensagens de sucesso gravadas na sessão pelos controllers (redirect()->with(...)).
    $messages = collect(['success', 'profile_success', 'password_success'])
        ->map(fn (string $key) => session($key))
        ->filter()
        ->values();
    $toast = 'pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border border-mist-200 bg-white p-4 text-ink-950 shadow-[0_16px_30px_rgb(24_42_63/0.18)]';
    $closeButton = 'grid size-7 flex-none cursor-pointer place-items-center rounded text-ink-500 hover:bg-mist-150 hover:text-ink-950 focus-visible:outline-2 focus-visible:outline-institutional';
@endphp

<div
    x-data="toaster"
    @toast.window="push($event.detail.message)"
    class="pointer-events-none fixed inset-x-4 top-20 z-[60] flex flex-col items-end gap-2 md:top-36 md:right-6 md:left-auto md:w-96"
    role="status"
    aria-live="polite"
>
    @foreach ($messages as $message)
        <div x-data="toast" x-show="visible" x-transition.opacity.duration.200ms @mouseenter="pause()" @mouseleave="resume()" @focusin="pause()" @focusout="resume()" class="{{ $toast }}">
            <x-lucide-circle-check class="mt-0.5 size-5 flex-none text-institutional" aria-hidden="true" />
            <p class="min-w-0 flex-1 text-[0.85rem] leading-[1.45]">{{ $message }}</p>
            <button type="button" class="{{ $closeButton }}" @click="dismiss()" aria-label="Fechar aviso">
                <x-lucide-x class="size-4" aria-hidden="true" />
            </button>
        </div>
    @endforeach

    <template x-for="item in items" :key="item.id">
        <div x-data="toast" x-show="visible" x-transition.opacity.duration.200ms @mouseenter="pause()" @mouseleave="resume()" @focusin="pause()" @focusout="resume()" class="{{ $toast }}">
            <x-lucide-circle-check class="mt-0.5 size-5 flex-none text-institutional" aria-hidden="true" />
            <p class="min-w-0 flex-1 text-[0.85rem] leading-[1.45]" x-text="item.message"></p>
            <button type="button" class="{{ $closeButton }}" @click="dismiss()" aria-label="Fechar aviso">
                <x-lucide-x class="size-4" aria-hidden="true" />
            </button>
        </div>
    </template>
</div>
