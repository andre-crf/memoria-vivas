@props([
    'title',
    'description' => null,
    'icon' => null,
    // Nível do título conforme a página: h2 dentro de uma seção da página, h3 dentro de um bloco que já tem h2.
    'heading' => 'h2',
])

@php
    $heading = in_array($heading, ['h2', 'h3', 'h4'], true) ? $heading : 'h2';
@endphp

{{-- Sem registros, pesquisa sem resultados ou conteúdo ainda não cadastrado. A borda é de quem usa (ex.: border-dashed). --}}
<div {{ $attributes->class('grid justify-items-center gap-2 px-6 py-12 text-center') }}>
    @if ($icon)
        <span class="mb-1 grid size-11 place-items-center rounded-full bg-mist-150 text-ink-500" aria-hidden="true">
            <x-dynamic-component :component="'lucide-'.$icon" class="size-5" />
        </span>
    @endif
    <{{ $heading }} class="text-base font-semibold text-fg">{{ $title }}</{{ $heading }}>
    @if ($description)
        <p class="max-w-md text-sm leading-6 text-fg-muted">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="mt-2 flex flex-wrap justify-center gap-2">{{ $action }}</div>
    @endisset
</div>
