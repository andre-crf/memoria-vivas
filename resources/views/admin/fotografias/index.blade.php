<x-layouts.app title="Fotografias | Memórias Vivas">
    <div class="min-h-screen bg-stone-50">
        <header class="border-b border-stone-200 bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-6 py-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#6B5E2E]">Memórias Vivas</p>
                    <h1 class="mt-1 text-xl font-semibold text-stone-950">Administração do acervo</h1>
                </div>

                <div class="flex items-center gap-4">
                    <nav class="hidden items-center gap-2 md:flex" aria-label="Navegação administrativa">
                        <a
                            href="{{ route('admin.dashboard') }}"
                            class="rounded-md px-3 py-2 text-sm font-medium text-stone-600 transition hover:bg-stone-100 hover:text-stone-950 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2"
                        >
                            Painel
                        </a>
                        <a
                            href="{{ route('admin.fotografias.index') }}"
                            aria-current="page"
                            class="rounded-md bg-[#173F35] px-3 py-2 text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2"
                        >
                            Fotografias
                        </a>
                    </nav>

                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-medium text-stone-900">{{ auth()->user()->nome }}</p>
                        <p class="text-xs uppercase tracking-[0.12em] text-stone-500">{{ auth()->user()->role }}</p>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="rounded-md border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2"
                        >
                            Sair
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-6 py-8">
            <section class="mb-6">
                <div>
                    <p class="text-sm font-medium text-[#6B5E2E]">Acervo</p>
                    <h2 class="mt-1 text-2xl font-semibold text-stone-950">Fotografias cadastradas</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-600">
                        Lista administrativa das fotografias ativas cadastradas no acervo.
                    </p>
                </div>
            </section>

            <section class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
                @if ($fotografias->isEmpty())
                    <div class="px-6 py-16 text-center">
                        <h3 class="text-base font-semibold text-stone-950">Nenhuma fotografia cadastrada</h3>
                        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-stone-600">
                            Quando houver fotografias ativas no acervo, elas aparecerão nesta listagem.
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
                                    <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-200 bg-white">
                                @foreach ($fotografias as $fotografia)
                                    @php
                                        $statusLabel = match ($fotografia->status) {
                                            'rascunho' => 'Rascunho',
                                            'em_revisao' => 'Em revisão',
                                            'publicado' => 'Publicado',
                                            'arquivado' => 'Arquivado',
                                            default => ucfirst((string) $fotografia->status),
                                        };
                                    @endphp

                                    <tr id="fotografia-{{ $fotografia->id }}" class="hover:bg-stone-50">
                                        <td class="max-w-md px-5 py-4">
                                            <p class="truncate text-sm font-semibold text-stone-950">{{ $fotografia->titulo }}</p>
                                            <p class="mt-1 text-xs text-stone-500">#{{ $fotografia->id }}</p>
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-4 text-sm text-stone-700">
                                            {{ $fotografia->dataHistorica()->label() }}
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-4">
                                            <span class="inline-flex rounded-full bg-stone-100 px-2.5 py-1 text-xs font-medium text-stone-700">
                                                {{ $statusLabel }}
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
                                                    href="#fotografia-{{ $fotografia->id }}"
                                                    class="rounded-md border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2"
                                                >
                                                    Visualizar
                                                </a>
                                                <a
                                                    href="#fotografia-{{ $fotografia->id }}"
                                                    class="rounded-md border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2"
                                                >
                                                    Editar
                                                </a>
                                                <button
                                                    type="button"
                                                    class="rounded-md border border-red-200 px-3 py-2 text-sm font-medium text-red-700 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-700 focus:ring-offset-2"
                                                >
                                                    Excluir
                                                </button>
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
        </main>
    </div>
</x-layouts.app>
