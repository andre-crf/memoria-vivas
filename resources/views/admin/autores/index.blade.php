<x-layouts.admin title="Autores | Memórias Vivas">

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <p class="text-sm font-medium text-heritage-700">Catalogação</p>

                <h2 class="mt-1 text-2xl font-semibold text-stone-950">
                    Autores
                </h2>

                <p class="mt-2 text-sm leading-6 text-stone-600">
                    Gerencie os autores associados às fotografias do acervo.
                </p>
            </div>

            <a
                href="{{ route('admin.autores.create') }}"
                class="inline-flex items-center justify-center rounded-md bg-institutional px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-institutional-strong focus:outline-none focus:ring-2 focus:ring-institutional focus:ring-offset-2"
            >
                Novo autor
            </a>
        </div>

        <x-admin.busca-listagem
            class="mt-6"
            :action="route('admin.autores.index')"
            :termo="$termo"
            placeholder="Nome ou observação"
            :total="$autores->total()"
            singular="autor encontrado"
            plural="autores encontrados"
            :search-error="$searchError"
            :ordenacao="$ordenacao"
        />

        @if ($autores->isEmpty() && $termo !== '')
            <div class="rounded-lg border border-stone-200 bg-white shadow-sm"><x-admin.busca-sem-resultados :action="route('admin.autores.index')" mensagem="Nenhum autor encontrado para a pesquisa" /></div>
        @elseif ($autores->isEmpty())
            <div class="rounded-lg border border-dashed border-stone-300 bg-white p-8 text-center">
                <p class="text-sm text-stone-600">
                    Nenhum autor cadastrado.
                </p>
            </div>
        @else
            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-[0_18px_40px_rgb(28_25_23/0.05)]">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200">
                        <thead class="bg-stone-50">
                            <tr>
                                <x-admin.coluna-ordenavel rotulo="Nome" chave="nome" :ordenacao="$ordenacao" :action="route('admin.autores.index')" :parametros="$parametros" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-stone-500" />

                                <x-admin.coluna-ordenavel rotulo="Tipo" chave="tipo" :ordenacao="$ordenacao" :action="route('admin.autores.index')" :parametros="$parametros" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-stone-500" />

                                <x-admin.coluna-ordenavel rotulo="Itens associados" chave="itens" :ordenacao="$ordenacao" :action="route('admin.autores.index')" :parametros="$parametros" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-stone-500" />

                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wide text-stone-500">
                                    Ações
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-stone-200">
                            @foreach ($autores as $autor)
                                <tr class="relative hover:bg-stone-50">
                                    <td class="px-6 py-4 text-sm font-medium text-stone-900">
                                        <x-admin.link-linha :href="route('admin.autores.edit', $autor)">{{ $autor->nome }}</x-admin.link-linha>
                                    </td>

                                    <td class="px-6 py-4 text-sm text-stone-600">
                                        {{ $autor->tipo === 'pessoa' ? 'Pessoa' : 'Instituição' }}
                                    </td>

                                    <td class="px-6 py-4 text-sm text-stone-600">
                                        {{ $autor->itens_acervo_count }}
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="relative z-10 flex justify-end items-center gap-2">
                                            <a
                                                href="{{ route('admin.autores.edit', $autor) }}"
                                                class="rounded-md border border-stone-300 px-3 py-2 text-xs font-medium text-stone-700 hover:bg-stone-50"
                                            >
                                                Editar
                                            </a>

                                            <form
                                                method="POST"
                                                action="{{ route('admin.autores.destroy', $autor) }}"
                                                data-confirm-title="Confirmar exclusão"
                                                data-confirm-message="{{ $autor->itens_acervo_count > 0
                                                    ? "Este autor está associado a {$autor->itens_acervo_count} itens do acervo. Ao excluir o autor, essas associações serão removidas, mas os itens serão mantidos. Deseja continuar?"
                                                    : "Este autor não possui itens associados. Deseja realmente excluí-lo?" }}"
                                                data-confirm-label="Excluir autor"
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="rounded-md border border-red-200 px-3 py-2 text-xs font-medium text-red-700 hover:bg-red-50"
                                                >
                                                    Excluir
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($autores->hasPages())
                    <div class="border-t border-stone-200 px-5 py-4">
                        {{ $autores->links() }}
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-layouts.admin>
