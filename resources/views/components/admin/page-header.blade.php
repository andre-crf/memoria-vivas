@props([
    'title',
    'eyebrow' => null,
    'description' => null,
    'backHref' => null,
    'backLabel' => 'Voltar',
])

{{--
    Cabeçalho das páginas do admin: o título é o h1 da página. O slot padrão fica abaixo da
    descrição (ex.: badges de status); o slot "actions" recebe os botões e quebra linha no celular.
    Não tem margem externa: o espaçamento até o conteúdo é de quem usa (nas páginas, class="mb-6").
--}}
<header {{ $attributes->class('flex flex-col gap-4 md:flex-row md:items-end md:justify-between') }}>
    <div class="min-w-0">
        @if ($backHref)
            <a href="{{ $backHref }}" class="inline-flex items-center gap-1 rounded text-sm font-semibold text-institutional hover:text-institutional-strong hover:underline hover:underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-institutional">
                <x-lucide-arrow-left class="size-4 flex-none" aria-hidden="true" />
                {{ $backLabel }}
            </a>
        @endif

        @if ($eyebrow)
            <p @class(['text-sm font-medium text-heritage-700', 'mt-4' => $backHref])>{{ $eyebrow }}</p>
        @endif

        <h1 @class(['text-2xl font-semibold text-fg wrap-anywhere sm:text-3xl', 'mt-2' => $eyebrow, 'mt-4' => $backHref && ! $eyebrow])>{{ $title }}</h1>

        @if ($description)
            <p class="mt-2 max-w-3xl text-sm leading-6 text-fg-muted">{{ $description }}</p>
        @endif

        @if ($slot->isNotEmpty())
            <div class="mt-3">{{ $slot }}</div>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap gap-2 md:flex-none md:justify-end">{{ $actions }}</div>
    @endisset
</header>
