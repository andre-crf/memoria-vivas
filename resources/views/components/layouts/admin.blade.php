@php
    $navigation = [
        [
            'label' => 'Painel',
            'route' => 'admin.dashboard',
            'active' => 'admin.dashboard',
        ],
        [
            'label' => 'Fotografias',
            'route' => 'admin.fotografias.index',
            'active' => 'admin.fotografias.*',
        ],
        [
            'label' => 'Categorias',
            'route' => 'admin.categorias.index',
            'active' => 'admin.categorias.*',
        ],
        [
            'label' => 'Assuntos',
            'route' => 'admin.assuntos.index',
            'active' => 'admin.assuntos.*',
        ],
        [
            'label' => 'Palavras-chave',
            'route' => 'admin.palavras-chave.index',
            'active' => 'admin.palavras-chave.*',
        ],
        [
            'label' => 'Autores',
            'route' => 'admin.autores.index',
            'active' => 'admin.autores.*',
        ],
    ];
@endphp

<x-layouts.app :title="$title ?? 'Administração | Memórias Vivas'">
    <div class="min-h-screen bg-stone-50">
        <header class="border-b border-stone-200 bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-6 py-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#6B5E2E]">
                        Memórias Vivas
                    </p>

                    <h1 class="mt-1 text-xl font-semibold text-stone-950">
                        Administração do acervo
                    </h1>
                </div>

                <div class="flex items-center gap-4">
                    <nav
                        class="hidden items-center gap-2 md:flex"
                        aria-label="Navegação administrativa"
                    >
                        @foreach ($navigation as $item)
                            @php
                                $isActive = request()->routeIs($item['active']);
                            @endphp

                            <a
                                href="{{ route($item['route']) }}"
                                @if ($isActive)
                                    aria-current="page"
                                @endif
                                class="{{ $isActive
                                    ? 'rounded-md bg-[#173F35] px-3 py-2 text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2'
                                    : 'rounded-md px-3 py-2 text-sm font-medium text-stone-600 transition hover:bg-stone-100 hover:text-stone-950 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2' }}"
                            >
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </nav>

                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-medium text-stone-900">
                            {{ auth()->user()->nome }}
                        </p>

                        <p class="text-xs uppercase tracking-[0.12em] text-stone-500">
                            {{ auth()->user()->role }}
                        </p>
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

        <main>
            {{ $slot }}
        </main>
    </div>
</x-layouts.app>
