@php
    $secoes = [
        'tipografia' => 'Tipografia',
        'botoes' => 'Botões e links',
        'navegacao' => 'Navegação',
        'superficies' => 'Container e section',
        'registros' => 'Tags e registros',
        'pesquisa' => 'Busca',
        'formularios' => 'Formulários',
        'vazios' => 'Estados vazios',
        'compartilhados' => 'Compartilhados (x-ui)',
        'referencias' => 'Dependem de dados',
    ];
    // Classes de título de seção usadas hoje nas páginas Sobre e Como colaborar.
    $h2 = 'mt-[0.8rem] max-w-[42rem] text-[length:clamp(1.8rem,4vw,3rem)] leading-[1.12] font-semibold';
    $tituloGuia = 'text-[0.72rem] font-bold tracking-[0.12em] text-ink-500 uppercase';
@endphp

<x-layouts.public title="Style Guide público | Memórias Vivas">
    <section class="border-b border-mist-200 bg-white">
        <x-public.container class="py-10 md:py-14">
            <x-public.back-link :href="route('admin.style-guide.admin')">Style Guide do admin</x-public.back-link>
            <x-public.eyebrow class="mt-6">Desenvolvimento · somente local e testes</x-public.eyebrow>
            <x-public.heading as="h1" class="mt-3 text-[clamp(2rem,4.5vw,3.25rem)] leading-[1.08] font-medium">Componentes do site público</x-public.heading>
            <p class="mt-3 max-w-160 text-[1rem] leading-[1.65] text-ink-600">Componentes x-public.* reais, dentro do layout público, com o foco global de 3px da área pública. A tipografia serifada é exclusiva desta área.</p>

            <nav aria-label="Seções do Style Guide público" class="-mx-4 mt-7 overflow-x-auto px-4">
                <ol class="flex min-w-max gap-2 pb-1">
                    @foreach ($secoes as $ancora => $rotulo)
                        <li><a href="#{{ $ancora }}" class="inline-flex min-h-10 items-center rounded-full border border-mist-300 bg-white px-4 text-[0.82rem] font-semibold text-institutional hover:border-institutional hover:bg-institutional-soft">{{ $rotulo }}</a></li>
                    @endforeach
                </ol>
            </nav>
        </x-public.container>
    </section>

    <x-public.container class="grid gap-14 py-12">
        <section id="tipografia" class="scroll-mt-8" aria-labelledby="sgp-tipografia">
            <h2 id="sgp-tipografia" class="{{ $tituloGuia }}">Tipografia</h2>
            <div class="mt-4 grid gap-5">
                <x-dev.specimen title="Título de página" component="x-public.heading as=h1" description="Tamanho e peso vêm de quem usa; aqui, o título do catálogo." status="padronizado">
                    <x-public.heading as="h2" class="text-[clamp(2rem,4.5vw,3.25rem)] leading-[1.08] font-medium tracking-[-0.01em]">Explore o acervo</x-public.heading>
                </x-dev.specimen>
                <x-dev.specimen title="Título de seção com eyebrow" component="x-public.eyebrow + x-public.heading" description="Classes de título usadas em Sobre e Como colaborar." status="padronizado">
                    <x-public.eyebrow>Nosso propósito</x-public.eyebrow>
                    <x-public.heading class="{{ $h2 }}">Uma fotografia ganha sentido quando sua história pode ser encontrada.</x-public.heading>
                    <p class="mt-4 max-w-160 text-[1rem] leading-[1.75] text-ink-600">Texto corrido das páginas institucionais: 1rem, entrelinha 1,75, cor ink-600.</p>
                </x-dev.specimen>
                <p class="text-caption text-ink-500">Divergência registrada: cada página repete as classes do título de seção numa variável local ($h2). Candidato a tamanho padronizado no x-public.heading.</p>
            </div>
        </section>

        <section id="botoes" class="scroll-mt-8" aria-labelledby="sgp-botoes">
            <h2 id="sgp-botoes" class="{{ $tituloGuia }}">Botões e links</h2>
            <div class="mt-4 grid gap-5 md:grid-cols-2">
                <x-dev.specimen title="Botão" component="x-public.button" description="Variante única. Vira link com href; o span com a seta ganha tamanho próprio." status="padronizado">
                    <div class="flex flex-wrap items-center gap-4">
                        <x-public.button href="#botoes">Explorar o catálogo <span aria-hidden="true">&rarr;</span></x-public.button>
                        <x-public.button type="button">Aplicar filtros</x-public.button>
                        <x-public.button type="button" disabled aria-busy="true">Enviando...</x-public.button>
                    </div>
                    <p class="mt-4 text-caption text-ink-500">Desabilitado e em espera no próprio componente: opacidade reduzida, cursor bloqueado e, com aria-busy (envio da colaboração), cursor de espera.</p>
                </x-dev.specimen>
                <x-dev.specimen title="Links de texto" component="x-public.text-link · x-public.back-link" status="padronizado">
                    <div class="grid justify-items-start gap-3">
                        <x-public.text-link href="#botoes">Conheça formas de colaborar</x-public.text-link>
                        <x-public.back-link href="#botoes">Voltar à fotografia</x-public.back-link>
                    </div>
                    <p class="mt-4 text-caption text-ink-500">O back-link já traz a seta (decorativa). Usado na página de download; o visualizador mantém o botão translúcido próprio sobre a imagem.</p>
                </x-dev.specimen>
            </div>
        </section>

        <section id="navegacao" class="scroll-mt-8" aria-labelledby="sgp-navegacao">
            <h2 id="sgp-navegacao" class="{{ $tituloGuia }}">Navegação</h2>
            <div class="mt-4 grid gap-5 md:grid-cols-2">
                <x-dev.specimen title="Barra (desktop)" component="x-public.nav-link variant=bar" status="padronizado">
                    <nav aria-label="Exemplo de navegação em barra" class="flex flex-wrap items-center gap-7 text-[0.85rem] font-[650] text-ink-600">
                        <x-public.nav-link href="#navegacao" :active="true">Catálogo</x-public.nav-link>
                        <x-public.nav-link href="#navegacao">Sobre nós</x-public.nav-link>
                        <x-public.nav-link href="#navegacao">Como colaborar</x-public.nav-link>
                    </nav>
                </x-dev.specimen>
                <x-dev.specimen title="Menu (celular e tablet)" component="x-public.nav-link variant=menu" status="padronizado">
                    <nav aria-label="Exemplo de navegação em menu" class="grid max-w-xs gap-1 text-[0.95rem] font-[650] text-ink-700">
                        <x-public.nav-link href="#navegacao" :active="true" variant="menu" class="flex min-h-11 items-center">Catálogo</x-public.nav-link>
                        <x-public.nav-link href="#navegacao" variant="menu" class="flex min-h-11 items-center">Sobre nós</x-public.nav-link>
                    </nav>
                </x-dev.specimen>
            </div>
        </section>

        <section id="superficies" class="scroll-mt-8" aria-labelledby="sgp-superficies">
            <h2 id="sgp-superficies" class="{{ $tituloGuia }}">Container e section</h2>
            <div class="mt-4 grid gap-5">
                <x-dev.specimen title="Seções" component="x-public.section tinted" description="Padding vertical fluido clamp(4rem, 8vw, 7rem). Abaixo, as duas superfícies com o padding real." status="padronizado">
                    <div class="overflow-hidden rounded-md border border-mist-200">
                        <x-public.section>
                            <x-public.container><p class="text-[0.9rem] text-ink-600">Seção padrão: fundo branco.</p></x-public.container>
                        </x-public.section>
                        <x-public.section tinted>
                            <x-public.container><p class="text-[0.9rem] text-ink-600">Seção tinted: borda superior e fundo mist-75.</p></x-public.container>
                        </x-public.section>
                    </div>
                </x-dev.specimen>
                <x-dev.specimen title="Container" component="x-public.container" description="Largura min(100% - 2rem, 76rem), centralizada. É o contêiner de todas as páginas públicas, incluindo cabeçalho e rodapé." status="padronizado">
                    <div class="bg-mist-100 py-3">
                        <x-public.container class="border border-dashed border-mist-500 bg-white py-3 text-center text-[0.82rem] text-ink-600">x-public.container</x-public.container>
                    </div>
                </x-dev.specimen>
            </div>
        </section>

        <section id="registros" class="scroll-mt-8" aria-labelledby="sgp-registros">
            <h2 id="sgp-registros" class="{{ $tituloGuia }}">Tags e registros</h2>
            <div class="mt-4 grid gap-5 md:grid-cols-2">
                <x-dev.specimen title="Lista de tags" component="x-public.tag-list :items" status="padronizado">
                    <x-public.tag-list :items="['Arquitetura', 'Patrimônio histórico', 'Praça Santos Dumont', 'Década de 1970']" />
                </x-dev.specimen>
                <x-dev.specimen title="Bloco documental" component="x-public.record-section + x-public.record-groups" description="Coluna de informações do visualizador de fotografia." status="padronizado">
                    <x-public.record-section title="Classificações">
                        <x-public.record-groups :groups="['Categorias' => ['Arquitetura'], 'Assuntos' => ['Patrimônio histórico', 'Comércio']]" />
                    </x-public.record-section>
                </x-dev.specimen>
                <x-dev.specimen title="Etapas numeradas" component="x-public.steps :items" class="md:col-span-2" status="padronizado">
                    <x-public.steps :items="[
                        ['Preservar', 'Manter o arquivo original e preparar versões para consulta.'],
                        ['Organizar', 'Associar informações que ajudam a identificar cada fotografia.'],
                        ['Compartilhar', 'Publicar os registros revisados no catálogo.'],
                    ]" />
                </x-dev.specimen>
            </div>
        </section>

        <section id="pesquisa" class="scroll-mt-8" aria-labelledby="sgp-busca">
            <h2 id="sgp-busca" class="{{ $tituloGuia }}">Busca</h2>
            <div class="mt-4 grid gap-5">
                <x-dev.specimen title="Busca do catálogo" component="x-public.search-form" description="Componente real: pesquisar abre o catálogo público (GET, sem gravar dados)." status="padronizado">
                    <x-public.search-form />
                </x-dev.specimen>
            </div>
        </section>

        <section id="formularios" class="scroll-mt-8" aria-labelledby="sgp-formularios">
            <h2 id="sgp-formularios" class="{{ $tituloGuia }}">Formulários</h2>
            <div class="mt-4 grid gap-5">
                <x-dev.specimen title="Campos públicos" component=".public-label · .public-control · .public-field-error · .public-required" description="Classes do app.css usadas em Como colaborar e na página de download. Campos maiores que os do admin; o foco é o da área pública (3px), agora também em select e textarea. Nada aqui é enviado." status="padronizado">
                    <div class="grid gap-5 md:grid-cols-2">
                        <p class="text-[0.82rem] text-ink-600 md:col-span-2"><span class="public-required" aria-hidden="true">*</span> Campo obrigatório</p>
                        <div class="grid content-start gap-2">
                            <label for="sgp-nome" class="public-label">Seu nome <span class="public-required" aria-hidden="true">*</span></label>
                            <input id="sgp-nome" type="text" required class="public-control">
                        </div>
                        <div class="grid content-start gap-2">
                            <label for="sgp-email" class="public-label">E-mail para contato <span class="public-required" aria-hidden="true">*</span></label>
                            <input id="sgp-email" type="email" value="ana@" required class="public-control" aria-invalid="true" aria-describedby="sgp-email-erro">
                            <p id="sgp-email-erro" class="public-field-error">Informe um endereço de e-mail válido.</p>
                        </div>
                        <div class="grid content-start gap-2">
                            <label for="sgp-perfil" class="public-label">Seu perfil</label>
                            <select id="sgp-perfil" class="public-control"><option>Estudante</option><option>Pesquisador</option></select>
                        </div>
                        <div class="grid content-start gap-2">
                            <label for="sgp-local" class="public-label">Local retratado <span class="font-normal text-ink-450">opcional</span></label>
                            <input id="sgp-local" type="text" class="public-control" disabled value="Campo desabilitado">
                        </div>
                    </div>
                    <p class="mt-4 text-caption text-ink-500">Erro: aria-invalid deixa a borda vermelha e aria-describedby aponta a mensagem logo abaixo. O resumo no topo do formulário usa x-ui.alert (seção abaixo).</p>
                </x-dev.specimen>
            </div>
        </section>

        <section id="vazios" class="scroll-mt-8" aria-labelledby="sgp-vazios">
            <h2 id="sgp-vazios" class="{{ $tituloGuia }}">Estados vazios</h2>
            <div class="mt-4 grid gap-5">
                <x-dev.specimen title="Estado vazio" component="x-public.empty-state title" description="Usado só na página inicial; o catálogo sem resultados tem marcação própria, com ícone." status="padronizado">
                    <x-public.empty-state title="Nenhuma fotografia pública disponível ainda">
                        <span>As fotografias publicadas aparecerão aqui conforme o acervo for revisado.</span>
                    </x-public.empty-state>
                </x-dev.specimen>
            </div>
        </section>

        <section id="compartilhados" class="scroll-mt-8" aria-labelledby="sgp-compartilhados">
            <h2 id="sgp-compartilhados" class="{{ $tituloGuia }}">Compartilhados (x-ui)</h2>
            <div class="mt-4 grid gap-5 md:grid-cols-2">
                <x-dev.specimen title="Alerta no layout público" component="x-ui.alert" description="Adotado na Etapa 6C para o resumo de erros (Como colaborar e Baixar fotografia) e a confirmação de envio da colaboração. As condições de uso do download continuam no bloco editorial com borda lateral, porque são conteúdo, não feedback." status="padronizado">
                    <div class="grid gap-3">
                        <x-ui.alert tone="success" title="Fotografias recebidas" role="note">Obrigado por colaborar com a memória de Umuarama.</x-ui.alert>
                        <x-ui.alert tone="danger" title="Confira os campos indicados abaixo" role="note">As fotografias precisam ser selecionadas novamente após a correção.</x-ui.alert>
                    </div>
                </x-dev.specimen>
                <x-dev.specimen title="Badges no layout público" component="x-ui.badge" description="Mesma tipografia sans das tags públicas." status="padronizado">
                    <div class="flex flex-wrap gap-2">
                        <x-ui.badge tone="heritage">Década de 1970</x-ui.badge>
                        <x-ui.badge tone="info">Novo no acervo</x-ui.badge>
                    </div>
                </x-dev.specimen>
            </div>
        </section>

        <section id="referencias" class="scroll-mt-8" aria-labelledby="sgp-referencias">
            <h2 id="sgp-referencias" class="{{ $tituloGuia }}">Dependem de dados</h2>
            <div class="mt-4 grid gap-5">
                <x-dev.specimen title="Componentes com fotografias reais" description="Não são reproduzidos aqui para não depender de imagens do acervo." status="referencia">
                    <ul class="grid list-disc gap-1.5 pl-5 text-[0.9rem] text-ink-700">
                        <li><strong>x-public.gallery-item</strong>, filtros em drawer e paginação pública: <a href="{{ route('public.catalogo') }}" class="font-semibold text-institutional underline underline-offset-4">Catálogo</a>.</li>
                        <li>Visualizador em tela cheia e botões translúcidos: página de uma fotografia pública.</li>
                        <li>Carrossel de destaque e de fotografias recentes: <a href="{{ route('public.home') }}" class="font-semibold text-institutional underline underline-offset-4">Página inicial</a>.</li>
                        <li><strong>x-public.page-hero</strong> e <strong>x-public.invite</strong>: <a href="{{ route('public.sobre') }}" class="font-semibold text-institutional underline underline-offset-4">Sobre nós</a> e <a href="{{ route('public.colaborar') }}" class="font-semibold text-institutional underline underline-offset-4">Como colaborar</a>.</li>
                    </ul>
                </x-dev.specimen>
            </div>
        </section>
    </x-public.container>
</x-layouts.public>
