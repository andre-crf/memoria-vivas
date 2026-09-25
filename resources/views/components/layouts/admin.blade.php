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
        [
            'label' => 'Pessoas',
            'route' => 'admin.pessoas.index',
            'active' => 'admin.pessoas.*',
        ],
        [
            'label' => 'Usuários',
            'route' => 'admin.usuarios.index',
            'active' => 'admin.usuarios.*',
            'adminOnly' => true,
        ],
        [
            'label' => 'Auditoria',
            'route' => 'admin.auditoria.index',
            'active' => 'admin.auditoria.*',
            'adminOnly' => true,
        ],
    ];

    $moreNavigation = array_slice($navigation, 2);
    $isMoreActive = collect($moreNavigation)->contains(fn ($item) => request()->routeIs($item['active']));
@endphp

<x-layouts.app :title="$title ?? 'Administração | Memórias Vivas'">
    <div class="min-h-screen bg-stone-50">
        <header class="border-b border-stone-200 bg-white">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-4 px-6 py-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#6B5E2E]">
                        Memórias Vivas
                    </p>

                    <h1 class="mt-1 text-xl font-semibold text-stone-950">
                        Administração do acervo
                    </h1>
                </div>

                <nav
                    class="order-3 flex w-full items-center gap-2 lg:order-none lg:w-auto"
                    aria-label="Navegação administrativa"
                >
                    @foreach (array_slice($navigation, 0, 2) as $item)
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

                    <details class="group relative">
                        <summary
                            class="{{ $isMoreActive
                                ? 'flex cursor-pointer list-none items-center gap-2 rounded-md bg-[#173F35] px-3 py-2 text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2 [&::-webkit-details-marker]:hidden'
                                : 'flex cursor-pointer list-none items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-stone-600 transition hover:bg-stone-100 hover:text-stone-950 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2 [&::-webkit-details-marker]:hidden' }}"
                        >
                            Mais opções
                            <span aria-hidden="true" class="text-xs transition group-open:rotate-180">▼</span>
                        </summary>

                        <div class="absolute left-0 top-full z-20 mt-2 min-w-48 rounded-md border border-stone-200 bg-white p-1 shadow-lg">
                            @foreach ($moreNavigation as $item)
                                @continue(($item['adminOnly'] ?? false) && ! auth()->user()->isAdmin())
                                @php
                                    $isActive = request()->routeIs($item['active']);
                                @endphp

                                <a
                                    href="{{ route($item['route']) }}"
                                    @if ($isActive)
                                        aria-current="page"
                                    @endif
                                    class="{{ $isActive
                                        ? 'block rounded-md bg-[#173F35] px-3 py-2 text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2'
                                        : 'block rounded-md px-3 py-2 text-sm font-medium text-stone-600 transition hover:bg-stone-100 hover:text-stone-950 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2' }}"
                                >
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </details>
                </nav>

                <div class="ml-auto flex items-center gap-3 sm:gap-4">
                    <a
                        href="{{ route('admin.perfil.edit') }}"
                        @if (request()->routeIs('admin.perfil.*'))
                            aria-current="page"
                        @endif
                        aria-label="Acessar meu perfil"
                        class="max-w-32 rounded-md px-2 py-1 text-right transition hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2 sm:max-w-48 {{ request()->routeIs('admin.perfil.*') ? 'bg-stone-100' : '' }}"
                    >
                        <p class="truncate text-sm font-medium text-stone-900">
                            {{ auth()->user()->nome }}
                        </p>

                        <p class="hidden text-xs uppercase tracking-[0.12em] text-stone-500 sm:block">
                            {{ auth()->user()->role }}
                        </p>
                    </a>

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
