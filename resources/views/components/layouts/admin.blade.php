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
    @php
        $focusRing = 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-institutional';
    @endphp

    <div class="min-h-screen bg-admin-shell text-stone-900">
        <header class="sticky top-0 z-40 shadow-[0_3px_18px_rgb(24_42_63/0.08)]">
            <div class="bg-institutional text-white">
                <div class="mx-auto flex min-h-[4.5rem] w-full max-w-7xl min-w-0 items-center justify-between gap-2 px-4 sm:gap-4 sm:px-6 lg:px-8">
                    <a href="{{ route('admin.dashboard') }}" class="inline-flex min-w-0 items-center gap-[0.7rem] {{ $focusRing }}">
                        <img
                            src="{{ asset('images/memorias-vivas-logo.jpeg') }}"
                            alt=""
                            width="48"
                            height="48"
                            class="block size-[1.9rem] flex-none rounded-full border-2 border-white/82 bg-mist-250 object-cover sm:size-10 max-[359px]:hidden"
                        >
                        <span class="flex min-w-0 flex-col gap-[0.12rem]">
                            <span class="text-[0.9rem] leading-[1.1] font-semibold whitespace-nowrap sm:text-[1.1rem]">Memórias Vivas</span>
                            <span class="hidden text-[0.65rem] leading-[1.2] font-bold whitespace-nowrap text-institutional-muted uppercase sm:block">Acervo de Umuarama</span>
                            <span class="sr-only">Administração do acervo</span>
                        </span>
                    </a>

                    <div class="flex items-center gap-[0.4rem] sm:gap-[0.875rem]">
                        @php
                            $usuario = auth()->user();
                            $papel = $usuario->isAdmin() ? 'Administrador' : 'Operador';
                            $userMenuItem = 'flex min-h-10 w-full items-center justify-between gap-3 rounded px-3 text-left text-[0.8125rem] font-semibold text-ink-600 hover:bg-institutional-soft hover:text-institutional aria-[current=page]:bg-institutional-soft aria-[current=page]:text-institutional '.$focusRing;
                        @endphp

                        <div
                            class="relative"
                            x-data="{ open: false }"
                            @click.outside="open = false"
                            @keydown.escape.window="if (open) { open = false; $refs.userTrigger.focus() }"
                        >
                            <button
                                type="button"
                                x-ref="userTrigger"
                                @click="open = !open"
                                :aria-expanded="open"
                                aria-expanded="false"
                                aria-controls="admin-user-menu"
                                class="flex min-w-0 cursor-pointer items-center justify-end gap-2.5 rounded-md {{ $focusRing }}"
                            >
                                <x-lucide-chevron-down class="hidden size-4 flex-none text-institutional-muted transition-transform duration-160 md:block" x-bind:class="open && 'rotate-180'" aria-hidden="true" />
                                <span class="hidden min-w-0 text-right md:block">
                                    <span class="block max-w-56 truncate text-[0.8rem] font-semibold text-white">{{ $usuario->nome }}</span>
                                    <span class="mt-[0.15rem] block text-[0.7rem] text-institutional-muted">{{ $papel }}</span>
                                </span>
                                <span class="sr-only md:hidden">Menu do usuário {{ $usuario->nome }}</span>

                                <span class="grid size-9 flex-none place-items-center rounded-full bg-institutional-soft text-[0.8rem] font-bold text-institutional uppercase" aria-hidden="true">
                                    {{ mb_substr($usuario->nome, 0, 1) }}
                                </span>
                            </button>

                            <div
                                id="admin-user-menu"
                                x-show="open"
                                x-cloak
                                x-transition.origin.top.right
                                class="absolute top-[calc(100%+0.75rem)] right-0 z-50 w-64 rounded-md border border-mist-200 bg-white p-1.5 text-ink-950 shadow-[0_16px_30px_rgb(24_42_63/0.18)]"
                            >
                                <div class="border-b border-mist-150 px-3 pt-2 pb-2.5">
                                    <p class="truncate text-[0.8125rem] font-semibold">{{ $usuario->nome }}</p>
                                    <p class="mt-0.5 truncate text-[0.72rem] text-ink-500">{{ $usuario->email }} · {{ $papel }}</p>
                                </div>

                                <div class="grid pt-1.5">
                                    <a
                                        href="{{ route('admin.perfil.edit') }}"
                                        aria-label="Acessar meu perfil"
                                        @if (request()->routeIs('admin.perfil.*')) aria-current="page" @endif
                                        class="{{ $userMenuItem }}"
                                    >Meu perfil</a>
                                    <a href="{{ route('public.home') }}" target="_blank" rel="noopener" class="{{ $userMenuItem }}">
                                        Página pública
                                        {{-- O &#xFE0E; força a seta em texto; sem ele a fonte de emoji a desenha colorida. --}}
                                        <span aria-hidden="true">&nearr;&#xFE0E;</span>
                                        <span class="sr-only">(abre em nova aba)</span>
                                    </a>
                                </div>

                                <form method="POST" action="{{ route('logout') }}" class="mt-1.5 border-t border-mist-150 pt-1.5">
                                    @csrf
                                    <button type="submit" class="{{ $userMenuItem }} cursor-pointer">Sair</button>
                                </form>
                            </div>
                        </div>

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

    <x-admin.toaster />
    <x-confirmation-dialog />
</x-layouts.app>
