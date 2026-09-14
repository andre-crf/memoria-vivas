<x-layouts.admin title="Assuntos | Memórias Vivas">
    <div class="mx-auto max-w-7xl px-6 py-8">
            <section class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-end">
                <div>
                    <p class="text-sm font-medium text-[#6B5E2E]">Catalogação</p>
                    <h2 class="mt-1 text-2xl font-semibold text-stone-950">Assuntos</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-600">Assuntos utilizados na catalogação dos itens do acervo.</p>
                </div>

                <a href="{{ route('admin.assuntos.create') }}" class="inline-flex items-center justify-center rounded-md bg-[#173F35] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0f2b24] focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2">Novo assunto</a>
            </section>

            @if (session('success'))
                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">{{ session('success') }}</div>
            @endif

            <section class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
                @if ($assuntos->isEmpty())
                    <div class="px-6 py-16 text-center">
                        <h3 class="text-base font-semibold text-stone-950">Nenhum assunto cadastrado</h3>
                        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-stone-600">Quando houver assuntos cadastrados, eles aparecerão nesta listagem.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-200">
                            <thead class="bg-stone-100">
                                <tr>
                                    <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Título</th>
                                    <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Descrição</th>
                                    <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Itens associados</th>
                                    <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-200 bg-white">
                                @foreach ($assuntos as $assunto)
                                    <tr id="assunto-{{ $assunto->id }}" class="hover:bg-stone-50">
                                        <td class="max-w-sm px-5 py-4">
                                            <p class="truncate text-sm font-semibold text-stone-950">{{ $assunto->titulo }}</p>
                                            <p class="mt-1 text-xs text-stone-500">#{{ $assunto->id }}</p>
                                        </td>
                                        <td class="max-w-lg px-5 py-4"><p class="text-sm text-stone-700">{{ $assunto->descricao ?: 'Sem descrição' }}</p></td>
                                        <td class="whitespace-nowrap px-5 py-4"><span class="inline-flex rounded-full bg-[#E8E2C9] px-2.5 py-1 text-xs font-medium text-[#4A3F18]">{{ $assunto->itens_acervo_count }}</span></td>
                                        <td class="px-5 py-4">
                                            <div class="flex justify-end gap-2">
                                                <a href="{{ route('admin.assuntos.edit', $assunto) }}" class="rounded-md border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2">Editar</a>
                                                <form method="POST" action="{{ route('admin.assuntos.destroy', $assunto) }}" onsubmit="return confirm('Tem certeza que deseja excluir este assunto? As associações com os itens do acervo serão removidas.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="rounded-md border border-red-200 px-3 py-2 text-xs font-medium text-red-700 hover:bg-red-50">Excluir</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
    </div>
</x-layouts.admin>
