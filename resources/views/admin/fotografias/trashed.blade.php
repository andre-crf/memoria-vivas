<x-layouts.admin title="Lixeira de fotografias | Memórias Vivas" active="fotografias">
    <div class="mx-auto max-w-7xl">
        <section class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <a
                    href="{{ route('admin.fotografias.index') }}"
                    class="text-sm font-semibold text-[#173F7A] hover:text-[#0E2A52]"
                >
                    Voltar para fotografias
                </a>
                <p class="mt-4 text-sm font-medium text-[#3F7E94]">Acervo</p>
                <h1 class="mt-2 font-serif text-3xl font-semibold text-[#173F7A]">Lixeira de fotografias</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-[#55709B]">
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

        <section class="overflow-hidden rounded-lg border border-[#D8E2EF] bg-white shadow-sm shadow-[#173F7A]/5">
            @if ($fotografias->isEmpty())
                <div class="px-6 py-16 text-center">
                    <h2 class="font-serif text-xl font-semibold text-[#173F7A]">Nenhuma fotografia na lixeira</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-[#55709B]">
                        Quando uma fotografia for excluída logicamente, ela aparecerá aqui para possível restauração.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[#D8E2EF]">
                        <thead class="bg-[#F4F8FC]">
                            <tr>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-[#55709B]">Título</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-[#55709B]">Data</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-[#55709B]">Status</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-[#55709B]">Visibilidade</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-[#55709B]">Exclusão</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-[0.12em] text-[#55709B]">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#D8E2EF] bg-white">
                            @foreach ($fotografias as $fotografia)
                                <tr id="fotografia-excluida-{{ $fotografia->id }}" class="hover:bg-[#F8FBFE]">
                                    <td class="max-w-md px-5 py-4">
                                        <p class="truncate text-sm font-semibold text-[#173F7A]">{{ $fotografia->titulo }}</p>
                                        <p class="mt-1 text-xs text-[#7A8DA8]">#{{ $fotografia->id }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-[#294B77]">
                                        {{ $fotografia->dataHistorica()->label() }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="inline-flex rounded-full bg-[#E8EEF6] px-2.5 py-1 text-xs font-medium text-[#294B77]">
                                            {{ $fotografia->statusLabel() }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="inline-flex rounded-full bg-[#DCEEEF] px-2.5 py-1 text-xs font-medium text-[#287C7C]">
                                            {{ $fotografia->visibilidade->label() }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-[#294B77]">
                                        <p>{{ $fotografia->deleted_at?->format('d/m/Y H:i') ?: 'Não registrada' }}</p>
                                        <p class="mt-1 text-xs text-[#7A8DA8]">{{ $fotografia->excluidoPor?->nome ?: 'Usuário não registrado' }}</p>
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

                <div class="border-t border-[#D8E2EF] px-5 py-4">
                    {{ $fotografias->links() }}
                </div>
            @endif
        </section>
    </div>
</x-layouts.admin>
