@props([
    'action',
    'mensagem' => 'Nenhum registro encontrado para a pesquisa',
    'dica' => 'Revise o texto pesquisado ou tente outro termo.',
    'limpar' => 'Limpar pesquisa',
])

{{-- Pesquisa ou filtros sem resultado: o estado vazio padrão com a ação de limpar os critérios. --}}
<x-ui.empty-state {{ $attributes }} icon="search-x" :title="$mensagem" :description="$dica">
    <x-slot:action>
        <x-catalog.button variant="secondary" size="sm" :href="$action">{{ $limpar }}</x-catalog.button>
    </x-slot:action>
</x-ui.empty-state>
