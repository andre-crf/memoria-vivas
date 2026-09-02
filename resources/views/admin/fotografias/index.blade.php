<x-layouts.admin title="Fotografias | Memórias Vivas" active="fotografias">
    <div class="mx-auto max-w-7xl">
        <section class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <p class="text-sm font-medium text-[#3F7E94]">Acervo</p>
                <h1 class="mt-2 font-serif text-3xl font-semibold text-[#173F7A]">Fotografias cadastradas</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-[#55709B]">
                    Lista administrativa das fotografias ativas cadastradas no acervo.
                </p>
            </div>

            <a
                href="{{ route('admin.fotografias.create') }}"
                class="inline-flex items-center justify-center rounded-md bg-[#173F7A] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0E2A52] focus:outline-none focus:ring-2 focus:ring-[#173F7A] focus:ring-offset-2"
            >
                Nova fotografia
            </a>
        </section>

        @if (session('success'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <section class="overflow-hidden rounded-lg border border-[#D8E2EF] bg-white shadow-sm shadow-[#173F7A]/5">
            @if ($fotografias->isEmpty())
                <div class="px-6 py-16 text-center">
                    <h2 class="font-serif text-xl font-semibold text-[#173F7A]">Nenhuma fotografia cadastrada</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-[#55709B]">
                        Quando houver fotografias ativas no acervo, elas aparecerão nesta listagem.
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
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-[0.12em] text-[#55709B]">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#D8E2EF] bg-white">
                            @foreach ($fotografias as $fotografia)
                                <tr id="fotografia-{{ $fotografia->id }}" class="hover:bg-[#F8FBFE]">
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
                                    <td class="px-5 py-4">
                                        <div class="flex justify-end gap-2">
                                            <a
                                                href="{{ route('admin.fotografias.show', $fotografia) }}"
                                                class="rounded-md border border-[#D8E2EF] px-3 py-2 text-sm font-medium text-[#173F7A] transition hover:bg-[#F4F8FC] focus:outline-none focus:ring-2 focus:ring-[#173F7A] focus:ring-offset-2"
                                            >
                                                Visualizar
                                            </a>
                                            <a
                                                href="{{ route('admin.fotografias.edit', $fotografia) }}"
                                                class="rounded-md border border-[#D8E2EF] px-3 py-2 text-sm font-medium text-[#173F7A] transition hover:bg-[#F4F8FC] focus:outline-none focus:ring-2 focus:ring-[#173F7A] focus:ring-offset-2"
                                            >
                                                Editar
                                            </a>
                                            @can('delete', $fotografia)
                                                <form method="POST" action="{{ route('admin.fotografias.destroy', $fotografia) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        type="submit"
                                                        class="rounded-md border border-red-200 px-3 py-2 text-sm font-medium text-red-700 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-700 focus:ring-offset-2"
                                                    >
                                                        Excluir
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
