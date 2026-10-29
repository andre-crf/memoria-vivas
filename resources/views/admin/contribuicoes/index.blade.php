<x-layouts.admin title="Contribuições | Memórias Vivas">
    <div class="mx-auto max-w-7xl px-6 py-8">
        <p class="text-sm font-semibold text-institutional">Acervo</p>
        <h1 class="mt-2 text-3xl font-semibold text-stone-950">Contribuições recebidas</h1>
        <p class="mt-2 text-sm text-stone-600">Fotografias enviadas pelo público. Os arquivos são privados e ainda não fazem parte do catálogo.</p>

        <div class="mt-8 overflow-x-auto rounded-md border border-stone-200 bg-white">
            @if ($contribuicoes->isEmpty())
                <p class="px-6 py-12 text-center text-stone-600">Nenhuma contribuição recebida ainda.</p>
            @else
                <table class="min-w-full divide-y divide-stone-200 text-sm">
                    <thead class="bg-stone-50 text-left text-stone-600"><tr>
                        <x-admin.coluna-ordenavel rotulo="Assunto" chave="assunto" :ordenacao="$ordenacao" :action="route('admin.contribuicoes.index')" :parametros="[]" class="px-5 py-3" /><x-admin.coluna-ordenavel rotulo="Colaborador" chave="colaborador" :ordenacao="$ordenacao" :action="route('admin.contribuicoes.index')" :parametros="[]" class="px-5 py-3" /><x-admin.coluna-ordenavel rotulo="Arquivos" chave="arquivos" :ordenacao="$ordenacao" :action="route('admin.contribuicoes.index')" :parametros="[]" class="px-5 py-3" /><x-admin.coluna-ordenavel rotulo="Recebida em" chave="recebida" :ordenacao="$ordenacao" :action="route('admin.contribuicoes.index')" :parametros="[]" class="px-5 py-3" /><th class="px-5 py-3"><span class="sr-only">Ações</span></th>
                    </tr></thead>
                    <tbody class="divide-y divide-stone-200">
                        @foreach ($contribuicoes as $contribuicao)
                            <tr>
                                <td class="px-5 py-4 font-semibold text-stone-900">{{ $contribuicao->titulo }}</td>
                                <td class="px-5 py-4 text-stone-700">{{ $contribuicao->nome }}</td>
                                <td class="px-5 py-4 text-stone-700">{{ $contribuicao->arquivos_count }}</td>
                                <td class="px-5 py-4 text-stone-700"><x-data-hora :valor="$contribuicao->created_at" formato="curto" /></td>
                                <td class="px-5 py-4"><a class="font-semibold text-institutional underline" href="{{ route('admin.contribuicoes.show', $contribuicao) }}">Ver detalhes</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        <div class="mt-6">{{ $contribuicoes->links() }}</div>
    </div>
</x-layouts.admin>
