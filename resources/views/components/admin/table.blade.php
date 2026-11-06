@props([
    // Paginador da listagem: com mais de uma página, a paginação fica no rodapé do contêiner.
    'paginator' => null,
    // Sem registros: mostra o slot "vazio" no lugar da tabela, dentro do mesmo contêiner.
    'vazia' => false,
    // Classes extras da <table>, como uma largura mínima para tabelas com muitas colunas.
    'tableClass' => null,
])

{{--
    Contêiner das tabelas do admin: borda, fundo, sombra, rolagem horizontal localizada e paginação.
    thead, tbody, tr e td continuam em cada listagem; o estilo comum de th e td está em .admin-table (app.css).
--}}
<div {{ $attributes->class('min-w-0 overflow-hidden rounded-lg border border-line bg-white shadow-card') }}>
    @if ($vazia && isset($vazio))
        {{ $vazio }}
    @else
        <div class="overflow-x-auto">
            <table @class(['admin-table min-w-full text-left text-sm', $tableClass])>
                {{ $slot }}
            </table>
        </div>

        @if ($paginator?->hasPages())
            <div class="border-t border-line px-5 py-4">
                {{ $paginator->links() }}
            </div>
        @endif
    @endif
</div>
