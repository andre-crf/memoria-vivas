<x-layouts.admin title="Fotografias | Memórias Vivas">
    <div class="mx-auto max-w-7xl px-6 py-8">
        <section class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <p class="text-sm font-medium text-[#6B5E2E]">Acervo</p>
                <h1 class="mt-2 text-3xl font-semibold text-stone-950">Fotografias cadastradas</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-600">
                    Lista administrativa das fotografias ativas cadastradas no acervo.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                @can('restore', new \App\Models\ItemAcervo)
                    <a
                        href="{{ route('admin.fotografias.trashed') }}"
                        class="inline-flex items-center justify-center rounded-md border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2"
                    >
                        Lixeira
                    </a>
                @endcan
                <a
                    href="{{ route('admin.fotografias.create') }}"
                    class="inline-flex items-center justify-center rounded-md bg-[#173F35] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0f2b24] focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2"
                >
                    Nova fotografia
                </a>
            </div>
        </section>

        @if (session('success'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <section class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
            @if ($fotografias->isEmpty())
                <div class="px-6 py-16 text-center">
                    <h2 class="text-xl font-semibold text-stone-950">Nenhuma fotografia cadastrada</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-stone-600">
                        Quando houver fotografias ativas no acervo, elas aparecerão nesta listagem.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200">
                        <thead class="bg-stone-100">
                            <tr>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Imagem</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Título</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Data</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Status</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Visibilidade</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-200 bg-white">
                            @foreach ($fotografias as $fotografia)
                                @php($thumbnail = $fotografia->thumbnailAdministrativa())
                                <tr id="fotografia-{{ $fotografia->id }}" class="hover:bg-stone-50">
                                    <td class="px-5 py-4">
                                        @if ($thumbnail)
                                            <img
                                                src="{{ route('admin.arquivos.show', $thumbnail) }}"
                                                alt="Miniatura de {{ $fotografia->titulo }}"
                                                width="{{ $thumbnail->width ?: 64 }}"
                                                height="{{ $thumbnail->height ?: 64 }}"
                                                loading="lazy"
                                                class="h-16 w-16 rounded-md bg-stone-100 object-cover"
                                            >
                                        @else
                                            <div class="grid h-16 w-16 place-items-center rounded-md border border-dashed border-stone-300 bg-stone-50 text-xs font-semibold uppercase tracking-[0.12em] text-stone-400">
                                                Sem imagem
                                            </div>
                                        @endif
                                    </td>
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
                                    <td class="px-5 py-4">
                                        <div class="flex justify-end gap-2">
                                            <a
                                                href="{{ route('admin.fotografias.show', $fotografia) }}"
                                                class="rounded-md border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2"
                                            >
                                                Visualizar
                                            </a>
                                            <a
                                                href="{{ route('admin.fotografias.edit', $fotografia) }}"
                                                class="rounded-md border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2"
                                            >
                                                Editar
                                            </a>
                                            @can('delete', $fotografia)
                                                <form method="POST" action="{{ route('admin.fotografias.destroy', $fotografia) }}" onsubmit="return confirm('Excluir esta fotografia?')">
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

                <div class="border-t border-stone-200 px-5 py-4">
                    {{ $fotografias->links() }}
                </div>
            @endif
        </section>
    </div>
</x-layouts.admin>
