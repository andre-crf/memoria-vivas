@props([
    'title',
    'component' => null,
    'description' => null,
    'status' => null,
])

@php
    // Situação do item no Design System: padronizado (componente pronto), referência (marcação das telas, ainda sem componente) ou planejado.
    $statusLabels = [
        'padronizado' => 'Componente padronizado',
        'referencia' => 'Padrão atual, sem componente',
        'planejado' => 'Planejado',
    ];
@endphp

{{-- Moldura de exemplo do Style Guide (só desenvolvimento). O conteúdo do slot deve ser o componente real. --}}
<article {{ $attributes->class('min-w-0 overflow-hidden rounded-lg border border-line bg-white') }}>
    <header class="flex flex-wrap items-start justify-between gap-x-4 gap-y-1.5 border-b border-line bg-surface-muted px-4 py-3">
        <div class="min-w-0 text-left">
            <h4 class="text-label font-semibold text-fg">{{ $title }}</h4>
            @if ($description)
                <p class="mt-0.5 text-caption text-fg-subtle">{{ $description }}</p>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($status)
                <span class="rounded-full border border-line-strong bg-white px-2 py-0.5 text-caption font-semibold text-fg-muted">{{ $statusLabels[$status] ?? $status }}</span>
            @endif
            @if ($component)
                <code class="rounded bg-white px-1.5 py-0.5 font-mono text-caption text-institutional ring-1 ring-line">{{ $component }}</code>
            @endif
        </div>
    </header>
    <div class="p-4 sm:p-5">
        {{ $slot }}
    </div>
</article>
