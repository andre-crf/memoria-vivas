<x-layouts.admin title="Lixeira de fotografias | Memórias Vivas">
    <div class="mx-auto max-w-7xl px-6 py-8">
        <section class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <a
                    href="{{ route('admin.fotografias.index') }}"
                    class="text-sm font-semibold text-[#173F35] hover:text-[#0f2b24]"
                >
                    Voltar para fotografias
                </a>
                <p class="mt-4 text-sm font-medium text-[#6B5E2E]">Acervo</p>
                <h1 class="mt-2 text-3xl font-semibold text-stone-950">Lixeira de fotografias</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-600">
                    Fotografias excluídas logicamente e disponíveis para restauração administrativa.
                </p>
            </div>
        </section>

        @if (session('success'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm leading-6 text-red-800">
            <strong class="font-semibold">Atenção:</strong>
            a exclusão permanente remove o registro e seus vínculos definitivamente. Essa operação é irreversível.
        </div>

        <section class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
            @if ($fotografias->isEmpty())
                <div class="px-6 py-16 text-center">
                    <h2 class="text-xl font-semibold text-stone-950">Nenhuma fotografia na lixeira</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-stone-600">
                        Quando uma fotografia for excluída logicamente, ela aparecerá aqui para possível restauração.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200">
                        <thead class="bg-stone-100">
                            <tr>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Título</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Data</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Status</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Visibilidade</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Exclusão</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-200 bg-white">
                            @foreach ($fotografias as $fotografia)
                                <tr id="fotografia-excluida-{{ $fotografia->id }}" class="hover:bg-stone-50">
                                    <td class="max-w-md px-5 py-4">
                                        <p class="truncate text-sm font-semibold text-stone-950">{{ $fotografia->titulo }}</p>
                                        <p class="mt-1 text-xs text-stone-500">#{{ $fotografia->id }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-stone-700">
                                        {{ $fotografia->dataHistorica()->label() }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="inline-flex rounded-full bg-stone-100 px-2.5 py-1 text-xs font-medium text-stone-700">
                                            {{ $fotografia->statusLabel() }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="inline-flex rounded-full bg-[#E8E2C9] px-2.5 py-1 text-xs font-medium text-[#4A3F18]">
                                            {{ $fotografia->visibilidade->label() }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-stone-700">
                                        <p>{{ $fotografia->deleted_at?->format('d/m/Y H:i') ?: 'Não registrada' }}</p>
                                        <p class="mt-1 text-xs text-stone-500">{{ $fotografia->excluidoPor?->nome ?: 'Usuário não registrado' }}</p>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex justify-end gap-2">
                                            @can('restore', $fotografia)
                                                <form method="POST" action="{{ route('admin.fotografias.restore', $fotografia->id) }}" onsubmit="return confirm('Restaurar esta fotografia?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button
                                                        type="submit"
                                                        class="rounded-md border border-[#2F8F6F]/30 px-3 py-2 text-sm font-medium text-[#1F6F55] transition hover:bg-[#E7F5EF] focus:outline-none focus:ring-2 focus:ring-[#1F6F55] focus:ring-offset-2"
                                                    >
                                                        Restaurar
                                                    </button>
                                                </form>
                                            @endcan

                                            @can('forceDelete', $fotografia)
                                                <form method="POST" action="{{ route('admin.fotografias.force-destroy', $fotografia->id) }}" onsubmit="return confirm('Excluir permanentemente esta fotografia? Esta operação é irreversível.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        type="submit"
                                                        class="rounded-md border border-red-200 px-3 py-2 text-sm font-medium text-red-700 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-700 focus:ring-offset-2"
                                                    >
                                                        Excluir permanentemente
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-stone-200 px-5 py-4">
                    {{ $fotografias->links() }}
                </div>
            @endif
        </section>
    </div>
</x-layouts.admin>
