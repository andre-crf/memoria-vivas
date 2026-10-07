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

    $navigation = array_values(array_filter(
        $navigation,
        fn (array $item): bool => ! ($item['adminOnly'] ?? false) || auth()->user()->isAdmin(),
    ));
@endphp

<x-layouts.app :title="$title ?? 'Administração | Memórias Vivas'">
    <div class="admin-shell">
        <header class="admin-header">
            <div class="admin-header__primary">
                <div class="admin-header__inner admin-header__bar">
                    <a href="{{ route('admin.dashboard') }}" class="admin-brand">
                        <span class="admin-brand__mark" aria-hidden="true">MV</span>
                        <span class="admin-brand__text">
                            <span class="admin-brand__title">Memórias Vivas</span>
                            <span class="admin-brand__subtitle">Acervo de Umuarama</span>
                            <span class="sr-only">Administração do acervo</span>
                        </span>
                    </a>

                    <div class="admin-header__actions">
                        <a
                            href="{{ route('admin.perfil.edit') }}"
                            aria-label="Acessar meu perfil"
                            @if (request()->routeIs('admin.perfil.*')) aria-current="page" @endif
                            class="admin-user"
                        >
                            <div class="admin-user__meta">
                                <p class="admin-user__name">{{ auth()->user()->nome }}</p>
                                <p class="admin-user__role">
                                    {{ auth()->user()->isAdmin() ? 'Administrador' : 'Operador' }}</p>
                            </div>

                            <span class="admin-user__avatar" aria-hidden="true">
                                {{ mb_substr(auth()->user()->nome, 0, 1) }}
                            </span>
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="admin-logout">Sair</button>
                        </form>

                        <details class="admin-mobile-nav">
                            <summary class="admin-mobile-nav__summary">Menu</summary>

                            <nav class="admin-mobile-nav__panel" aria-label="Navegação administrativa mobile">
                                @foreach ($navigation as $item)
                                    @php($isActive = request()->routeIs($item['active']))

                                    <a href="{{ route($item['route']) }}"
                                        @if ($isActive) aria-current="page" @endif
                                        class="admin-nav__link">
                                        {{ $item['label'] }}
                                    </a>
                                @endforeach
                            </nav>
                        </details>
                    </div>
                </div>
            </div>

            <div class="admin-header__navigation">
                <nav class="admin-header__inner admin-nav" aria-label="Navegação administrativa">
                    @foreach ($navigation as $item)
                        @php($isActive = request()->routeIs($item['active']))

                        <a href="{{ route($item['route']) }}"
                            @if ($isActive) aria-current="page" @endif class="admin-nav__link">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
            </div>
        </header>

        <main class="admin-main">
            {{ $slot }}
        </main>
    </div>

    <x-confirmation-dialog />
</x-layouts.app>
