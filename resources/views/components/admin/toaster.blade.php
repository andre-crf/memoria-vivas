@php
    // Mensagens gravadas na sessão pelos controllers (redirect()->with(...)). A chave define o tipo:
    // sucesso completo, aviso (sucesso parcial) ou erro (falha total).
    $types = ['success' => 'success', 'profile_success' => 'success', 'password_success' => 'success', 'warning' => 'warning', 'error' => 'error'];
    $messages = collect($types)
        ->map(fn (string $type, string $key) => ['type' => $type, 'message' => session($key)])
        ->filter(fn (array $item) => filled($item['message']))
        ->values();
    $toast = 'pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border bg-white p-4 text-ink-950 shadow-[0_16px_30px_rgb(24_42_63/0.18)]';
    $variants = [
        'success' => ['border' => 'border-mist-200', 'icon' => 'text-institutional'],
        'warning' => ['border' => 'border-warning/40', 'icon' => 'text-warning'],
        'error' => ['border' => 'border-danger/40', 'icon' => 'text-danger'],
    ];
    $closeButton = 'grid size-7 flex-none cursor-pointer place-items-center rounded text-ink-500 hover:bg-mist-150 hover:text-ink-950 focus-visible:outline-2 focus-visible:outline-institutional';
@endphp

<div
    x-data="toaster"
    @toast.window="push($event.detail.message, $event.detail.type)"
    class="pointer-events-none fixed inset-x-4 top-20 z-[60] flex flex-col items-end gap-2 md:top-36 md:right-6 md:left-auto md:w-96"
    role="status"
    aria-live="polite"
>
    @foreach ($messages as $item)
        <div x-data="toast" x-show="visible" x-transition.opacity.duration.200ms @mouseenter="pause()" @mouseleave="resume()" @focusin="pause()" @focusout="resume()" class="{{ $toast }} {{ $variants[$item['type']]['border'] }}" data-toast-type="{{ $item['type'] }}">
            @if ($item['type'] === 'error')
                <x-lucide-circle-x class="mt-0.5 size-5 flex-none {{ $variants['error']['icon'] }}" aria-hidden="true" />
            @elseif ($item['type'] === 'warning')
                <x-lucide-triangle-alert class="mt-0.5 size-5 flex-none {{ $variants['warning']['icon'] }}" aria-hidden="true" />
            @else
                <x-lucide-circle-check class="mt-0.5 size-5 flex-none {{ $variants['success']['icon'] }}" aria-hidden="true" />
            @endif
            <p class="min-w-0 flex-1 text-[0.85rem] leading-[1.45]">
                @if ($item['type'] !== 'success')
                    <span class="sr-only">{{ $item['type'] === 'error' ? 'Erro:' : 'Atenção:' }}</span>
                @endif
                {{ $item['message'] }}
            </p>
            <button type="button" class="{{ $closeButton }}" @click="dismiss()" aria-label="Fechar aviso">
                <x-lucide-x class="size-4" aria-hidden="true" />
            </button>
        </div>
    @endforeach

    <template x-for="item in items" :key="item.id">
        <div x-data="toast" x-show="visible" x-transition.opacity.duration.200ms @mouseenter="pause()" @mouseleave="resume()" @focusin="pause()" @focusout="resume()" class="{{ $toast }}" :class="{ 'border-mist-200': item.type === 'success', 'border-warning/40': item.type === 'warning', 'border-danger/40': item.type === 'error' }" :data-toast-type="item.type">
            <span x-show="item.type === 'success'"><x-lucide-circle-check class="mt-0.5 size-5 flex-none {{ $variants['success']['icon'] }}" aria-hidden="true" /></span>
            <span x-show="item.type === 'warning'" x-cloak><x-lucide-triangle-alert class="mt-0.5 size-5 flex-none {{ $variants['warning']['icon'] }}" aria-hidden="true" /></span>
            <span x-show="item.type === 'error'" x-cloak><x-lucide-circle-x class="mt-0.5 size-5 flex-none {{ $variants['error']['icon'] }}" aria-hidden="true" /></span>
            <p class="min-w-0 flex-1 text-[0.85rem] leading-[1.45]" x-text="item.message"></p>
            <button type="button" class="{{ $closeButton }}" @click="dismiss()" aria-label="Fechar aviso">
                <x-lucide-x class="size-4" aria-hidden="true" />
            </button>
        </div>
    </template>
</div>
