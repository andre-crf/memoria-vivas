@props([
    'tone' => 'info',
    'title' => null,
    // "auto": erro é anunciado na hora (alert), sucesso como status; informação e atenção não interrompem o leitor de tela.
    'role' => 'auto',
])

@php
    $tones = [
        'info' => ['box' => 'border-institutional-muted bg-institutional-soft', 'icon' => 'info', 'iconColor' => 'text-institutional', 'prefix' => 'Informação:', 'role' => null],
        'success' => ['box' => 'border-success/25 bg-success-soft', 'icon' => 'circle-check', 'iconColor' => 'text-success', 'prefix' => 'Sucesso:', 'role' => 'status'],
        'warning' => ['box' => 'border-warning/30 bg-warning-soft', 'icon' => 'triangle-alert', 'iconColor' => 'text-warning-ink', 'prefix' => 'Atenção:', 'role' => null],
        'danger' => ['box' => 'border-danger/25 bg-error-soft', 'icon' => 'circle-x', 'iconColor' => 'text-danger', 'prefix' => 'Erro:', 'role' => 'alert'],
    ];
    $config = $tones[$tone] ?? $tones['info'];
    $role = $role === 'auto' ? $config['role'] : $role;
@endphp

<div @if ($role) role="{{ $role }}" @endif {{ $attributes->class(['flex items-start gap-3 rounded-lg border px-4 py-3 text-left text-sm leading-6 text-ink-800', $config['box']]) }}>
    <x-dynamic-component :component="'lucide-'.$config['icon']" class="mt-1 size-4 flex-none {{ $config['iconColor'] }}" aria-hidden="true" />
    <div class="min-w-0 flex-1 [&_a]:font-semibold [&_a]:underline [&_a]:underline-offset-4 [&_ul]:mt-1.5 [&_ul]:list-disc [&_ul]:space-y-1 [&_ul]:pl-5">
        @if ($title)
            <p class="font-semibold text-fg">{{ $title }}</p>
        @else
            {{-- Sem título, o tipo da mensagem é dito em texto, não só pela cor e pelo ícone. --}}
            <span class="sr-only">{{ $config['prefix'] }}</span>
        @endif
        {{ $slot }}
    </div>
</div>
