<x-layouts.app title="Admin | Memórias Vivas">
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
                            aria-current="page"
                            class="rounded-md bg-[#173F35] px-3 py-2 text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2"
                        >
                            Painel
                        </a>
                        <a
                            href="{{ route('admin.fotografias.index') }}"
                            class="rounded-md px-3 py-2 text-sm font-medium text-stone-600 transition hover:bg-stone-100 hover:text-stone-950 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2"
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
            <section class="mb-8">
                <h2 class="text-2xl font-semibold text-stone-950">Painel inicial</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-600">
                    Acompanhe o estado geral da catalogação antes de acessar os cadastros administrativos.
                </p>
            </section>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-stone-600">Itens do acervo</p>
                    <p class="mt-3 text-3xl font-semibold text-stone-950">{{ $totalItens }}</p>
                </article>

                <article class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-stone-600">Itens publicados</p>
                    <p class="mt-3 text-3xl font-semibold text-stone-950">{{ $itensPublicados }}</p>
                </article>

                <article class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-stone-600">Arquivos digitais</p>
                    <p class="mt-3 text-3xl font-semibold text-stone-950">{{ $totalArquivos }}</p>
                </article>

                <article class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-stone-600">Usuários ativos</p>
                    <p class="mt-3 text-3xl font-semibold text-stone-950">{{ $usuariosAtivos }}</p>
                </article>
            </section>

            <section class="mt-8 rounded-lg border border-dashed border-stone-300 bg-white p-6">
                <h3 class="text-base font-semibold text-stone-950">Fotografias</h3>
                <p class="mt-2 text-sm leading-6 text-stone-600">
                    Acesse a listagem principal de fotografias cadastradas no acervo.
                </p>
                <a
                    href="{{ route('admin.fotografias.index') }}"
                    class="mt-4 inline-flex items-center justify-center rounded-md bg-[#173F35] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0f2b24] focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2"
                >
                    Abrir fotografias
                </a>
            </section>
        </main>
    </div>
</x-layouts.app>
