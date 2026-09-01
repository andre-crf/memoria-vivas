@props([
    'title' => 'Admin | Memórias Vivas',
    'active' => 'dashboard',
])

@php
    $user = auth()->user();

    $mainNav = [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'active' => 'dashboard'],
        ['label' => 'Fotografias', 'route' => 'admin.fotografias.index', 'active' => 'fotografias'],
    ];

    $futureNav = ['Usuários', 'Arquivos digitais', 'Categorias', 'Coleções', 'Autores'];
@endphp

<x-layouts.app :title="$title">
    <div class="min-h-screen bg-[#F4F8FC] text-[#12366A]">
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-72 flex-col bg-[#183D70] text-white shadow-2xl lg:flex">
            <div class="flex h-24 items-center gap-3 border-b border-white/10 px-6">
                <div class="grid h-12 w-12 place-items-center rounded-full border border-white/60 text-base font-semibold">
                    MV
                </div>
                <div>
                    <p class="font-serif text-lg font-semibold leading-tight">Memórias Vivas</p>
                    <p class="text-xs uppercase tracking-[0.18em] text-blue-100">de Umuarama</p>
                </div>
            </div>

            <nav class="flex-1 px-5 py-5" aria-label="Menu administrativo">
                <div class="space-y-2">
                    @foreach ($mainNav as $item)
                        <a
                            href="{{ route($item['route']) }}"
                            @if ($active === $item['active']) aria-current="page" @endif
                            class="flex items-center gap-3 rounded-md px-4 py-2.5 text-sm font-semibold transition {{ $active === $item['active'] ? 'bg-[#3FA39B] text-white shadow-lg shadow-black/10' : 'text-blue-50 hover:bg-white/10' }}"
                        >
                            <span class="grid h-7 w-7 place-items-center rounded-md bg-white/10 text-xs">{{ strtoupper(substr($item['label'], 0, 1)) }}</span>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>

                <div class="mt-8">
                    <p class="px-4 text-xs font-semibold uppercase tracking-[0.16em] text-blue-200">Próximos módulos</p>
                    <div class="mt-3 space-y-1">
                        @foreach ($futureNav as $label)
                            <span class="flex items-center gap-3 rounded-md px-4 py-2.5 text-sm font-medium text-blue-100/65">
                                <span class="grid h-7 w-7 place-items-center rounded-md border border-white/10 text-xs">{{ strtoupper(substr($label, 0, 1)) }}</span>
                                {{ $label }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </nav>

            <div class="border-t border-white/10 p-4">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="flex w-full items-center justify-center rounded-md border border-white/20 px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-[#183D70]"
                    >
                        Sair do sistema
                    </button>
                </form>
            </div>
        </aside>

        <div class="lg:pl-72">
            <header class="sticky top-0 z-20 border-b border-[#D8E2EF] bg-white/90 backdrop-blur">
                <div class="flex h-20 items-center justify-between gap-4 px-5 py-4 sm:px-8">
                    <div class="lg:hidden">
                        <p class="font-serif text-lg font-semibold text-[#173F7A]">Memórias Vivas</p>
                        <p class="text-xs uppercase tracking-[0.16em] text-[#55709B]">Administração</p>
                    </div>

                    <div class="hidden items-center gap-3 lg:flex">
                        <span class="h-px w-7 bg-[#173F7A]"></span>
                        <span class="h-px w-5 bg-[#173F7A]"></span>
                        <span class="text-sm font-semibold text-[#173F7A]">Administração do acervo</span>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="hidden text-right sm:block">
                            <p class="text-sm font-semibold text-[#173F7A]">{{ $user?->nome }}</p>
                            <p class="text-xs capitalize text-[#55709B]">{{ $user?->role }}</p>
                        </div>
                        <div class="grid h-11 w-11 place-items-center rounded-full bg-[#244D86] text-sm font-bold text-white">
                            {{ strtoupper(substr((string) $user?->nome, 0, 1)) }}
                        </div>
                    </div>
                </div>
            </header>

            <main class="px-5 py-8 sm:px-8">
                {{ $slot }}
            </main>

            <footer class="border-t border-[#D8E2EF] px-5 py-6 text-xs text-[#55709B] sm:px-8">
                <div class="mx-auto flex max-w-7xl flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <p>&copy; {{ date('Y') }} Memórias Vivas de Umuarama.</p>
                    <p>Versão 1.0.0</p>
                </div>
            </footer>
        </div>
    </div>
</x-layouts.app>
