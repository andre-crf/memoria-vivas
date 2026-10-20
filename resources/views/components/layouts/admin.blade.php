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
            'label' => 'Contribuições',
            'route' => 'admin.contribuicoes.index',
            'active' => 'admin.contribuicoes.*',
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

    $navigation = array_values(array_filter(
        $navigation,
        fn (array $item): bool => ! ($item['adminOnly'] ?? false) || auth()->user()->isAdmin(),
    ));
@endphp

<x-layouts.app :title="$title ?? 'Administração | Memórias Vivas'">
    @php($focusRing = 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-institutional')

    <div class="min-h-screen bg-admin-shell text-stone-900">
        <header class="sticky top-0 z-40 shadow-[0_3px_18px_rgb(24_42_63/0.08)]">
            <div class="bg-institutional text-white">
                <div class="mx-auto flex min-h-[4.5rem] w-full max-w-7xl min-w-0 items-center justify-between gap-2 px-4 sm:gap-4 sm:px-6 lg:px-8">
                    <a href="{{ route('admin.dashboard') }}" class="inline-flex min-w-0 items-center gap-[0.7rem] {{ $focusRing }}">
                        <img
                            src="{{ asset('images/memorias-vivas-logo.png') }}"
                            alt=""
                            width="48"
                            height="48"
                            class="block size-[1.9rem] flex-none rounded-full border-2 border-white/82 bg-mist-250 object-cover sm:size-10 max-[359px]:hidden"
                        >
                        <span class="flex min-w-0 flex-col gap-[0.12rem]">
                            <span class="font-serif text-[0.9rem] leading-[1.1] whitespace-nowrap sm:text-[1.2rem]">Memórias Vivas</span>
                            <span class="hidden text-[0.65rem] leading-[1.2] font-bold whitespace-nowrap text-institutional-muted uppercase sm:block">Acervo de Umuarama</span>
                            <span class="sr-only">Administração do acervo</span>
                        </span>
                    </a>

                    <div class="flex items-center gap-[0.4rem] sm:gap-[0.875rem]">
                        <a
                            href="{{ route('admin.perfil.edit') }}"
                            aria-label="Acessar meu perfil"
                            @if (request()->routeIs('admin.perfil.*')) aria-current="page" @endif
                            class="flex min-w-0 items-center justify-end gap-2.5 rounded-md {{ $focusRing }}"
                        >
                            <div class="hidden min-w-0 text-right md:block">
                                <p class="max-w-56 truncate text-[0.8rem] font-semibold text-white">{{ auth()->user()->nome }}</p>
                                <p class="mt-[0.15rem] text-[0.7rem] text-institutional-muted">
                                    {{ auth()->user()->isAdmin() ? 'Administrador' : 'Operador' }}</p>
                            </div>

                            <span class="hidden size-9 flex-none place-items-center rounded-full bg-institutional-soft text-[0.8rem] font-bold text-institutional uppercase md:grid" aria-hidden="true">
                                {{ mb_substr(auth()->user()->nome, 0, 1) }}
                            </span>
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="inline-flex min-h-[2.125rem] items-center justify-center rounded-md border border-white/42 px-[0.8rem] py-[0.4rem] text-[0.75rem] font-semibold text-white transition-[background-color] duration-160 ease-[ease] hover:bg-white/12 {{ $focusRing }}">Sair</button>
                        </form>

                        <details class="group/mobile-nav relative md:hidden">
                            <summary class="flex min-h-[2.125rem] cursor-pointer list-none items-center gap-2 rounded-md border border-white/42 px-[0.7rem] py-[0.4rem] text-[0.75rem] font-semibold text-white [&::-webkit-details-marker]:hidden after:text-[0.9rem] after:leading-none after:content-['\2630'] group-open/mobile-nav:after:content-['\00d7'] {{ $focusRing }}">Menu</summary>

                            <nav class="absolute top-[calc(100%+0.75rem)] right-0 grid w-[min(19rem,calc(100vw-2rem))] rounded-md border border-mist-200 bg-white p-1.5 shadow-[0_16px_30px_rgb(24_42_63/0.18)]" aria-label="Navegação administrativa mobile">
                                @foreach ($navigation as $item)
                                    <x-admin.nav-link :href="route($item['route'])" :active="request()->routeIs($item['active'])" variant="menu">
                                        {{ $item['label'] }}
                                    </x-admin.nav-link>
                                @endforeach
                            </nav>
                        </details>
                    </div>
                </div>
            </div>

            <div class="hidden border-b border-mist-200 bg-white md:block">
                <nav class="mx-auto flex min-h-[3.25rem] w-full max-w-7xl items-stretch gap-6 px-4 sm:px-6 lg:px-8" aria-label="Navegação administrativa">
                    @foreach ($navigation as $item)
                        <x-admin.nav-link :href="route($item['route'])" :active="request()->routeIs($item['active'])">
                            {{ $item['label'] }}
                        </x-admin.nav-link>
                    @endforeach
                </nav>
            </div>
        </header>

        <main class="admin-main min-w-0 overflow-x-clip">
            {{ $slot }}
        </main>
    </div>

    <x-confirmation-dialog />
</x-layouts.app>
