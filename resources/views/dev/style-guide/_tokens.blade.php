@php
    // Classes escritas por extenso para o Tailwind gerar cada token usado nas amostras.
    $paletas = [
        'Institucional' => [
            ['institutional', 'bg-institutional', 'Marca, ação primária e foco'],
            ['institutional-strong', 'bg-institutional-strong', 'Hover da ação primária'],
            ['institutional-light', 'bg-institutional-light', 'Rodapé público e gráficos'],
            ['institutional-muted', 'bg-institutional-muted', 'Bordas de chips, texto sobre azul'],
            ['institutional-soft', 'bg-institutional-soft', 'Fundo de item ativo e destaque'],
        ],
        'Heritage (dourado)' => [
            ['heritage-900', 'bg-heritage-900', 'Texto sobre heritage-100'],
            ['heritage-700', 'bg-heritage-700', 'Eyebrow do admin'],
            ['heritage-300', 'bg-heritage-300', 'Detalhes da identidade'],
            ['heritage-100', 'bg-heritage-100', 'Fundo de selo heritage'],
        ],
        'Estados' => [
            ['danger', 'bg-danger', 'Texto de erro e ação de perigo'],
            ['error', 'bg-error', 'Borda de aviso de erro (público)'],
            ['error-strong', 'bg-error-strong', 'Mensagem de erro (público)'],
            ['error-ink', 'bg-error-ink', 'Texto sobre error-soft'],
            ['error-soft', 'bg-error-soft', 'Fundo de erro'],
            ['warning', 'bg-warning', 'Aviso e sucesso parcial'],
            ['warning-ink', 'bg-warning-ink', 'Texto sobre warning-soft'],
            ['warning-soft', 'bg-warning-soft', 'Fundo de atenção'],
            ['success', 'bg-success', 'Texto e ícone de sucesso'],
            ['success-soft', 'bg-success-soft', 'Fundo de sucesso'],
        ],
        'Visualizador' => [
            ['night-950', 'bg-night-950', 'Fundo do visualizador e gradientes'],
            ['night-700', 'bg-night-700', ''],
            ['night-500', 'bg-night-500', ''],
        ],
    ];
    $ink = [
        ['ink-950', 'bg-ink-950'], ['ink-900', 'bg-ink-900'], ['ink-850', 'bg-ink-850'],
        ['ink-800', 'bg-ink-800'], ['ink-700', 'bg-ink-700'], ['ink-600', 'bg-ink-600'],
        ['ink-500', 'bg-ink-500'], ['ink-450', 'bg-ink-450'], ['ink-400', 'bg-ink-400'],
    ];
    $mist = [
        ['paper', 'bg-paper'], ['mist-25', 'bg-mist-25'], ['mist-50', 'bg-mist-50'], ['mist-75', 'bg-mist-75'],
        ['mist-100', 'bg-mist-100'], ['mist-120', 'bg-mist-120'], ['mist-150', 'bg-mist-150'],
        ['mist-200', 'bg-mist-200'], ['mist-250', 'bg-mist-250'], ['mist-300', 'bg-mist-300'],
        ['mist-350', 'bg-mist-350'], ['mist-400', 'bg-mist-400'], ['mist-500', 'bg-mist-500'],
        ['mist-600', 'bg-mist-600'],
    ];
    $semanticos = [
        ['fg', 'bg-fg', 'text-fg', 'ink-950', 'Texto principal'],
        ['fg-muted', 'bg-fg-muted', 'text-fg-muted', 'ink-600', 'Texto secundário e descrições'],
        ['fg-subtle', 'bg-fg-subtle', 'text-fg-subtle', 'ink-500', 'Dicas, metadados e vazio'],
        ['line', 'bg-line', 'border-line', 'mist-200', 'Bordas de blocos e divisórias'],
        ['line-strong', 'bg-line-strong', 'border-line-strong', 'mist-300', 'Bordas de campos e botão secundário'],
        ['surface-muted', 'bg-surface-muted', 'bg-surface-muted', 'mist-50', 'Fundo de bloco informativo'],
    ];
    $escala = [
        ['caption', 'text-caption', 'Dicas, erros de campo, metadados', 'x-catalog.error'],
        ['label', 'text-label', 'Rótulos de campo', 'x-catalog.label'],
        ['ui', 'text-ui', 'Botões e itens de menu', 'x-catalog.button (md)'],
        ['section', 'text-section', 'Título de bloco de formulário', 'x-catalog.section'],
    ];
    $sombras = [
        ['card', 'shadow-card', 'Blocos e painéis', 'x-catalog.section'],
        ['popover', 'shadow-popover', 'Menus suspensos e toasts', 'menu do usuário, x-admin.toaster'],
        ['modal', 'shadow-modal', 'Diálogos', 'substituir original, cadastro rápido'],
        ['focus', 'focus:shadow-focus', 'Anel de foco dos campos', 'x-catalog.control'],
    ];
@endphp

<section id="cores" class="scroll-mt-36" aria-labelledby="sg-cores">
    <h2 id="sg-cores" class="{{ $tituloSecao }}">Cores</h2>
    <p class="{{ $descricaoSecao }}">Tokens do <code class="font-mono text-[0.8em]">@theme</code> em <code class="font-mono text-[0.8em]">resources/css/app.css</code>. Os valores abaixo são lidos do CSS gerado.</p>

    <div class="mt-6 grid gap-5">
        <x-dev.specimen title="Aliases semânticos" description="Papel da cor nos componentes. Apontam para os tokens de escala e são a escolha preferida em componentes novos." status="padronizado">
            <ul class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($semanticos as [$nome, $amostra, $uso, $origem, $descricao])
                    <li class="flex min-w-0 items-center gap-3">
                        <span class="size-11 flex-none rounded-md ring-1 ring-black/10 {{ $amostra }}" aria-hidden="true"></span>
                        <span class="grid min-w-0 gap-0.5 text-left">
                            <span class="text-label font-semibold text-fg">{{ $nome }} <span class="font-normal text-fg-subtle">→ {{ $origem }}</span></span>
                            <code class="font-mono text-caption text-institutional">{{ $uso }}</code>
                            <span class="text-caption text-fg-subtle">{{ $descricao }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </x-dev.specimen>

        @foreach ($paletas as $grupo => $cores)
            <x-dev.specimen :title="$grupo">
                <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">
                    @foreach ($cores as [$nome, $amostra, $uso])
                        <li class="grid min-w-0 content-start gap-1.5 text-left">
                            <span class="h-14 rounded-md ring-1 ring-black/10 {{ $amostra }}" aria-hidden="true"></span>
                            <span class="text-label font-semibold text-fg">{{ $nome }}</span>
                            <x-dev.token-value :name="'--color-'.$nome" />
                            @if ($uso)
                                <span class="text-caption text-fg-subtle">{{ $uso }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </x-dev.specimen>
        @endforeach

        <div class="grid gap-5 xl:grid-cols-2">
            @foreach (['Ink (textos)' => $ink, 'Mist (fundos e bordas)' => $mist] as $grupo => $escalaCor)
                <x-dev.specimen :title="$grupo">
                    <ul class="grid gap-1.5">
                        @foreach ($escalaCor as [$nome, $amostra])
                            <li class="grid grid-cols-[3rem_minmax(0,1fr)_auto] items-center gap-3">
                                <span class="h-7 rounded ring-1 ring-black/10 {{ $amostra }}" aria-hidden="true"></span>
                                <span class="text-left text-label font-semibold text-fg">{{ $nome }}</span>
                                <x-dev.token-value :name="'--color-'.$nome" />
                            </li>
                        @endforeach
                    </ul>
                </x-dev.specimen>
            @endforeach
        </div>
        <p class="text-caption text-fg-subtle">Tons quase idênticos (paper, mist-25, mist-50 e mist-100; mist-300 e mist-350; ink-400 e ink-450) continuam disponíveis e serão revistos na limpeza final. Ainda usam stone-* e red-* do Tailwind: as páginas de cadastro e edição de fotografia.</p>
    </div>
</section>

<section id="tipografia" class="scroll-mt-36" aria-labelledby="sg-tipografia">
    <h2 id="sg-tipografia" class="{{ $tituloSecao }}">Tipografia</h2>
    <p class="{{ $descricaoSecao }}">O admin usa somente a Instrument Sans. A Newsreader (serifada) é exclusiva do site público.</p>

    <div class="mt-6 grid gap-5">
        <div class="grid gap-5 md:grid-cols-2">
            <x-dev.specimen title="Sans-serif · Instrument Sans" component="font-sans" description="Toda a interface administrativa e os textos de apoio do site público.">
                <p class="font-sans text-3xl font-semibold text-fg">Acervo de Umuarama</p>
                <p class="mt-2 font-sans text-sm leading-6 text-fg-muted">Fotografias, lugares e pessoas catalogados para consulta. 0123456789</p>
            </x-dev.specimen>
            <x-dev.specimen title="Serifada · Newsreader" component="font-serif" description="Títulos editoriais do site público (x-public.heading). Não usar no admin.">
                <p class="font-serif text-3xl font-medium text-institutional">Memórias Vivas</p>
                <p class="mt-2 font-serif text-base leading-7 text-fg-muted">Uma fotografia ganha sentido quando sua história pode ser encontrada.</p>
            </x-dev.specimen>
        </div>

        <x-dev.specimen title="Escala de interface" description="Tokens --text-* gerados pelo Tailwind v4 como utilitários text-*. Para tamanhos maiores, a escala padrão do Tailwind (text-sm, text-2xl, text-3xl)." status="padronizado">
            <ul class="divide-y divide-line">
                @foreach ($escala as [$nome, $classe, $uso, $componente])
                    <li class="grid gap-1 py-3 text-left sm:grid-cols-[9rem_minmax(0,1fr)_auto] sm:items-baseline sm:gap-4">
                        <span class="grid gap-0.5">
                            <code class="font-mono text-caption text-institutional">{{ $classe }}</code>
                            <x-dev.token-value :name="'--text-'.$nome" />
                        </span>
                        <span class="{{ $classe }} text-fg">{{ $uso }}</span>
                        <span class="text-caption text-fg-subtle">{{ $componente }}</span>
                    </li>
                @endforeach
            </ul>
        </x-dev.specimen>

        <x-dev.specimen title="Hierarquia das páginas do admin" description="Padrão de todas as páginas do admin: formulários (Etapa 4), listagens (Etapa 5), páginas de detalhes e painel (Etapa 6A)." status="padronizado">
            <div class="grid gap-6">
                <div class="text-left">
                    <p class="text-sm font-medium text-heritage-700">Eyebrow · text-sm font-medium text-heritage-700</p>
                    <p class="mt-2 text-3xl font-semibold text-fg">Título da página · h1 text-3xl font-semibold</p>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-fg-muted">Descrição da página · text-sm leading-6, cor fg-muted.</p>
                </div>
                <div class="border-t border-line pt-5 text-left">
                    <p class="text-section font-semibold text-fg">Título de bloco ou painel · h2 text-section</p>
                    <p class="mt-1 text-[0.82rem] leading-normal text-fg-subtle">Descrição do bloco (x-catalog.section). O mesmo bloco serve aos formulários e às páginas de detalhes: editar e ver uma fotografia mostram os mesmos títulos.</p>
                </div>
            </div>
            <p class="mt-5 rounded-md bg-surface-muted px-3 py-2 text-caption text-fg-muted">Esta hierarquia está implementada no x-admin.page-header (seção Cabeçalho de página) e no x-catalog.section. O painel usa o mesmo cabeçalho; o título maior (text-4xl) que ele tinha foi abandonado em favor da escala comum.</p>
        </x-dev.specimen>
    </div>
</section>

<section id="superficies" class="scroll-mt-36" aria-labelledby="sg-superficies">
    <h2 id="sg-superficies" class="{{ $tituloSecao }}">Sombras, bordas e foco</h2>
    <p class="{{ $descricaoSecao }}">Raios e espaçamentos seguem a escala padrão do Tailwind; não há tokens próprios para eles.</p>

    <div class="mt-6 grid gap-5">
        <x-dev.specimen title="Sombras" status="padronizado">
            <ul class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($sombras as [$nome, $classe, $uso, $onde])
                    <li class="grid content-start gap-2 text-left">
                        <span @class(['grid h-20 place-items-center rounded-lg border border-line bg-white text-caption text-fg-subtle', $classe => $nome !== 'focus', 'shadow-focus border-institutional' => $nome === 'focus'])>{{ $nome }}</span>
                        <code class="font-mono text-caption text-institutional">{{ $classe }}</code>
                        <span class="text-caption text-fg-muted">{{ $uso }}</span>
                        <span class="text-caption text-fg-subtle">Em: {{ $onde }}</span>
                    </li>
                @endforeach
            </ul>
            <p class="mt-4 text-caption text-fg-subtle">Cartões, tabelas, busca, formulários e painéis usam shadow-card; menus, shadow-popover; diálogos, shadow-modal; foco de campo, shadow-focus.</p>
        </x-dev.specimen>

        <x-dev.specimen title="Bordas e raios" status="referencia">
            <ul class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <li class="grid gap-2 text-left"><span class="h-14 rounded-md border border-line bg-white"></span><code class="font-mono text-caption text-institutional">rounded-md border-line</code><span class="text-caption text-fg-subtle">Campos, botões, menus</span></li>
                <li class="grid gap-2 text-left"><span class="h-14 rounded-lg border border-line bg-white"></span><code class="font-mono text-caption text-institutional">rounded-lg border-line</code><span class="text-caption text-fg-subtle">Blocos, tabelas, diálogos</span></li>
                <li class="grid gap-2 text-left"><span class="h-14 rounded-md border border-line-strong bg-white"></span><code class="font-mono text-caption text-institutional">border-line-strong</code><span class="text-caption text-fg-subtle">Contorno de campo e botão secundário</span></li>
                <li class="grid gap-2 text-left"><span class="h-14 rounded-full border border-institutional-muted bg-institutional-soft"></span><code class="font-mono text-caption text-institutional">rounded-full</code><span class="text-caption text-fg-subtle">Chips, selos e avatares</span></li>
            </ul>
        </x-dev.specimen>

        <x-dev.specimen title="Foco por teclado" description="Use Tab para percorrer os elementos abaixo. O foco é o da implementação real." status="padronizado">
            <div class="flex flex-wrap items-center gap-4">
                <x-catalog.button>Botão (outline 2px)</x-catalog.button>
                <a href="#superficies" class="rounded text-sm font-semibold text-institutional hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-institutional">Link de texto</a>
                <x-catalog.control aria-label="Campo de exemplo para foco" placeholder="Campo (borda + anel)" class="max-w-60" />
            </div>
            <dl class="mt-5 grid gap-3 text-left text-caption md:grid-cols-2">
                <div class="rounded-md bg-surface-muted px-3 py-2">
                    <dt class="font-semibold text-fg">Botões e links do admin</dt>
                    <dd class="mt-1 font-mono text-fg-muted">focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-institutional</dd>
                </div>
                <div class="rounded-md bg-surface-muted px-3 py-2">
                    <dt class="font-semibold text-fg">Campos</dt>
                    <dd class="mt-1 font-mono text-fg-muted">focus:border-institutional focus:shadow-focus</dd>
                </div>
                <div class="rounded-md bg-surface-muted px-3 py-2">
                    <dt class="font-semibold text-fg">Site público</dt>
                    <dd class="mt-1 text-fg-muted">Regra global <code class="font-mono">.public-shell</code>: outline de 3px em links, botões, summary, inputs, selects e textareas.</dd>
                </div>
                <div class="rounded-md bg-surface-muted px-3 py-2">
                    <dt class="font-semibold text-fg">Não usar</dt>
                    <dd class="mt-1 text-fg-muted"><code class="font-mono">focus:ring-*</code> e <code class="font-mono">focus:outline-none</code> sem substituto: o anel aparece também no clique e some no teclado. Não há mais usos no projeto.</dd>
                </div>
            </dl>
        </x-dev.specimen>
    </div>
</section>
