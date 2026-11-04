@props([
    'paginator',
    'elements',
    'livewire' => false,
])

@php
    // Mesma paginação para links comuns e para componentes Livewire, que trocam de página sem recarregar.
    $nomeDaPagina = $paginator->getPageName();
    $anterior = $livewire ? ['acao' => "previousPage('{$nomeDaPagina}')"] : ['href' => $paginator->previousPageUrl()];
    $proxima = $livewire ? ['acao' => "nextPage('{$nomeDaPagina}')"] : ['href' => $paginator->nextPageUrl()];
@endphp

@if ($paginator->hasPages())
    <nav aria-label="Paginação" class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-[0.82rem] leading-5 text-ink-600">
            @if ($paginator->firstItem())
                Mostrando <span class="font-semibold text-ink-800">{{ $paginator->firstItem() }}</span>
                a <span class="font-semibold text-ink-800">{{ $paginator->lastItem() }}</span>
                de <span class="font-semibold text-ink-800">{{ $paginator->total() }}</span> resultados
            @else
                Nenhum resultado nesta página, de <span class="font-semibold text-ink-800">{{ $paginator->total() }}</span> no total
            @endif
        </p>

        {{-- Em telas pequenas, só anterior e próxima; os números não caberiam. --}}
        <div class="flex gap-2 sm:hidden">
            <x-paginacao.link :destino="$anterior" :desabilitado="$paginator->onFirstPage()" class="rounded-md">Anterior</x-paginacao.link>
            <x-paginacao.link :destino="$proxima" :desabilitado="! $paginator->hasMorePages()" class="rounded-md">Próxima</x-paginacao.link>
        </div>

        <div class="hidden -space-x-px rounded-md shadow-sm sm:inline-flex">
            <x-paginacao.link :destino="$anterior" :desabilitado="$paginator->onFirstPage()" rotulo="Página anterior" class="rounded-l-md">
                <x-lucide-chevron-left class="size-4" aria-hidden="true" />
            </x-paginacao.link>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span aria-disabled="true" class="inline-flex min-h-9 min-w-9 items-center justify-center border border-mist-300 bg-white px-3 text-[0.82rem] text-ink-500">{{ $element }}</span>
                @else
                    @foreach ($element as $page => $url)
                        <x-paginacao.link
                            wire:key="paginacao-{{ $nomeDaPagina }}-{{ $page }}"
                            :destino="$livewire ? ['acao' => 'gotoPage('.$page.', \''.$nomeDaPagina.'\')'] : ['href' => $url]"
                            :atual="$page == $paginator->currentPage()"
                            :rotulo="'Ir para a página '.$page"
                        >{{ $page }}</x-paginacao.link>
                    @endforeach
                @endif
            @endforeach

            <x-paginacao.link :destino="$proxima" :desabilitado="! $paginator->hasMorePages()" rotulo="Próxima página" class="rounded-r-md">
                <x-lucide-chevron-right class="size-4" aria-hidden="true" />
            </x-paginacao.link>
        </div>
    </nav>
@endif
