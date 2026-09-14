<x-layouts.admin title="Autores | Memórias Vivas">

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-stone-900">
                    Autores
                </h2>

                <p class="mt-1 text-sm text-stone-600">
                    Gerencie os autores associados às fotografias do acervo.
                </p>
            </div>

            <a
                href="{{ route('admin.autores.create') }}"
                class="rounded-md bg-emerald-800 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-900"
            >
                Novo autor
            </a>
        </div>

        @if (session('success'))
            <div class="mt-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($autores->isEmpty())
            <div class="mt-6 rounded-lg border border-dashed border-stone-300 bg-white p-8 text-center">
                <p class="text-sm text-stone-600">
                    Nenhum autor cadastrado.
                </p>
            </div>
        @else
            <div class="mt-6 overflow-hidden rounded-lg border border-stone-200 bg-white">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200">
                        <thead class="bg-stone-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-stone-500">
                                    Nome
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-stone-500">
                                    Tipo
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-stone-500">
                                    Itens associados
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wide text-stone-500">
                                    Ações
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-stone-200">
                            @foreach ($autores as $autor)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-stone-900">
                                        {{ $autor->nome }}
                                    </td>

                                    <td class="px-6 py-4 text-sm text-stone-600">
                                        {{ $autor->tipo === 'pessoa' ? 'Pessoa' : 'Instituição' }}
                                    </td>

                                    <td class="px-6 py-4 text-sm text-stone-600">
                                        {{ $autor->itens_acervo_count }}
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="flex justify-end items-center gap-2">
                                            <a
                                                href="{{ route('admin.autores.edit', $autor) }}"
                                                class="rounded-md border border-stone-300 px-3 py-2 text-xs font-medium text-stone-700 hover:bg-stone-50"
                                            >
                                                Editar
                                            </a>

                                            <form
                                                method="POST"
                                                action="{{ route('admin.autores.destroy', $autor) }}"
                                                onsubmit="return confirm('{{ $autor->itens_acervo_count > 0
                                                    ? "Este autor está associado a {$autor->itens_acervo_count} itens do acervo. Ao excluir o autor, essas associações serão removidas, mas os itens serão mantidos. Deseja continuar?"
                                                    : "Este autor não possui itens associados. Deseja realmente excluí-lo?" }}')"
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
            </div>
        @endif
    </div>
</x-layouts.admin>