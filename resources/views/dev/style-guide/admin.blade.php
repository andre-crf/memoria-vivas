@php
    $secoes = [
        'cores' => 'Cores',
        'tipografia' => 'Tipografia',
        'superficies' => 'Sombras, bordas e foco',
        'botoes' => 'Botões',
        'formularios' => 'Formulários',
        'cabecalho' => 'Cabeçalho de página',
        'badges' => 'Badges',
        'alertas' => 'Alertas',
        'listagens' => 'Busca, tabelas e paginação',
        'informacao' => 'Blocos informativos',
        'feedback' => 'Feedback e diálogos',
        'estados-vazios' => 'Estados vazios',
        'pendencias' => 'Diferenças e pendências',
    ];
    $tituloSecao = 'text-2xl font-semibold text-fg';
    $descricaoSecao = 'mt-1 max-w-3xl text-sm leading-6 text-fg-muted';
@endphp

<x-layouts.admin title="Style Guide | Memórias Vivas">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="border-b border-line pb-6">
            <x-admin.page-header
                eyebrow="Desenvolvimento · somente administradores, local e testes"
                title="Style Guide"
                description="Tokens e componentes reais do Memórias Vivas. Cada exemplo usa o componente da aplicação; quando um padrão ainda não virou componente, o exemplo indica onde ele aparece no sistema."
            >
                <x-slot:actions>
                    <x-catalog.button variant="secondary" size="sm" icon="layout-template" :href="route('admin.style-guide.publico')">Componentes públicos</x-catalog.button>
                </x-slot:actions>
            </x-admin.page-header>
            <ul class="mt-5 grid gap-2 text-[0.82rem] leading-normal text-fg-muted md:grid-cols-3">
                <li class="rounded-md bg-surface-muted px-3 py-2"><strong class="text-fg">x-catalog.*</strong> continua como a família de componentes de formulário do admin.</li>
                <li class="rounded-md bg-surface-muted px-3 py-2"><strong class="text-fg">Admin sans-serif:</strong> a fonte serifada fica reservada à identidade editorial do site público.</li>
                <li class="rounded-md bg-surface-muted px-3 py-2"><strong class="text-fg">Badges semânticos discretos:</strong> tom suave e texto sempre presente, sem depender só da cor (x-ui.badge).</li>
            </ul>
        </div>

        <div class="mt-6 grid gap-8 lg:grid-cols-[12rem_minmax(0,1fr)] lg:gap-10">
            <nav aria-label="Seções do Style Guide" class="-mx-4 overflow-x-auto px-4 lg:sticky lg:top-36 lg:mx-0 lg:self-start lg:overflow-visible lg:px-0">
                <ol class="flex gap-1.5 pb-1 lg:grid lg:gap-0.5">
                    @foreach ($secoes as $ancora => $rotulo)
                        <li class="flex-none">
                            <a href="#{{ $ancora }}" class="block rounded-md px-3 py-2 text-left text-[0.8125rem] font-semibold whitespace-nowrap text-fg-muted hover:bg-institutional-soft hover:text-institutional focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-institutional max-lg:border max-lg:border-line max-lg:bg-white">{{ $rotulo }}</a>
                        </li>
                    @endforeach
                </ol>
            </nav>

            <div class="grid min-w-0 gap-14">
                @include('dev.style-guide._tokens')
                @include('dev.style-guide._botoes')
                @include('dev.style-guide._formularios')
                @include('dev.style-guide._ui')
                @include('dev.style-guide._componentes')
            </div>
        </div>
    </div>
</x-layouts.admin>
