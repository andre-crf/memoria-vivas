@php
    $tons = [
        'neutral' => 'Estado sem destaque',
        'heritage' => 'Classificação da identidade (ex.: visibilidade)',
        'info' => 'Em andamento ou informativo',
        'success' => 'Concluído ou disponível',
        'warning' => 'Requer atenção',
        'danger' => 'Falha ou bloqueio',
    ];
@endphp

<section id="cabecalho" class="scroll-mt-36" aria-labelledby="sg-cabecalho">
    <h2 id="sg-cabecalho" class="{{ $tituloSecao }}">Cabeçalho de página</h2>
    <p class="{{ $descricaoSecao }}">O título é sempre o h1 da página. Nos exemplos abaixo, o h1 do componente aparece dentro desta página só para demonstração.</p>

    <div class="mt-6 grid gap-5">
        <x-dev.specimen title="Listagem com ações" component="x-admin.page-header eyebrow title description · slot actions" description="As ações quebram linha no celular e ficam alinhadas à direita a partir de md." status="padronizado">
            <x-admin.page-header eyebrow="Acervo" title="Fotografias cadastradas" description="Lista administrativa das fotografias ativas cadastradas no acervo.">
                <x-slot:actions>
                    <x-catalog.button variant="secondary" href="#cabecalho" icon="trash-2">Lixeira</x-catalog.button>
                    <x-catalog.button variant="outline" href="#cabecalho" icon="download">Exportar</x-catalog.button>
                    <x-catalog.button href="#cabecalho" icon="plus">Nova fotografia</x-catalog.button>
                </x-slot:actions>
            </x-admin.page-header>
        </x-dev.specimen>

        <div class="grid gap-5 lg:grid-cols-2">
            <x-dev.specimen title="Detalhe com retorno e badges" component="back-href back-label · slot padrão" status="padronizado">
                <x-admin.page-header :back-href="route('admin.style-guide.admin').'#cabecalho'" back-label="Voltar para fotografias" eyebrow="Fotografia #42" title="Inauguração da Praça Santos Dumont">
                    <div class="flex flex-wrap gap-2">
                        <x-ui.badge tone="success" icon="circle-check">Publicado</x-ui.badge>
                        <x-ui.badge tone="heritage" icon="globe">Público</x-ui.badge>
                    </div>
                    <x-slot:actions>
                        <x-catalog.button variant="secondary" href="#cabecalho">Editar</x-catalog.button>
                        <x-catalog.button variant="danger" icon="trash-2">Excluir</x-catalog.button>
                    </x-slot:actions>
                </x-admin.page-header>
            </x-dev.specimen>

            <x-dev.specimen title="Mínimo" component="x-admin.page-header title" description="Só o título; eyebrow, descrição, retorno e ações são opcionais." status="padronizado">
                <x-admin.page-header title="Meu perfil" />
            </x-dev.specimen>
        </div>
    </div>
</section>

<section id="badges" class="scroll-mt-36" aria-labelledby="sg-badges">
    <h2 id="sg-badges" class="{{ $tituloSecao }}">Badges</h2>
    <p class="{{ $descricaoSecao }}">Tons suaves com texto escuro. O componente é só visual: quem usa escolhe o tom de cada estado. O texto está sempre presente, e o ícone opcional reforça o significado sem depender da cor.</p>

    <div class="mt-6 grid gap-5">
        <x-dev.specimen title="Tons" component="x-ui.badge tone icon" status="padronizado">
            <ul class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($tons as $tom => $uso)
                    <li class="flex min-w-0 items-center gap-3 text-left">
                        <x-ui.badge :tone="$tom">{{ ucfirst($tom) }}</x-ui.badge>
                        <span class="min-w-0">
                            <code class="block font-mono text-caption text-institutional">tone="{{ $tom }}"</code>
                            <span class="text-caption text-fg-subtle">{{ $uso }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </x-dev.specimen>

        <x-dev.specimen title="Exemplos de uso" description="Tons usados nas telas: status e visibilidade das fotografias, processamento das versões e situação das contas." status="padronizado">
            <dl class="grid gap-4 text-left sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <dt class="text-caption font-semibold text-fg-subtle uppercase">Catalogação</dt>
                    <dd class="mt-2 flex flex-wrap gap-2">
                        <x-ui.badge>Rascunho</x-ui.badge>
                        <x-ui.badge tone="info">Em revisão</x-ui.badge>
                        <x-ui.badge tone="success" icon="circle-check">Publicado</x-ui.badge>
                    </dd>
                </div>
                <div>
                    <dt class="text-caption font-semibold text-fg-subtle uppercase">Visibilidade</dt>
                    <dd class="mt-2 flex flex-wrap gap-2">
                        <x-ui.badge tone="heritage" icon="globe">Público</x-ui.badge>
                        <x-ui.badge tone="neutral" icon="lock">Privado</x-ui.badge>
                    </dd>
                </div>
                <div>
                    <dt class="text-caption font-semibold text-fg-subtle uppercase">Processamento</dt>
                    <dd class="mt-2 flex flex-wrap gap-2">
                        <x-ui.badge tone="info" icon="clock">Pendente</x-ui.badge>
                        <x-ui.badge tone="info" icon="loader-circle">Processando</x-ui.badge>
                        <x-ui.badge tone="warning" icon="triangle-alert">Sem progresso</x-ui.badge>
                        <x-ui.badge tone="danger" icon="circle-x">Falhou</x-ui.badge>
                    </dd>
                </div>
                <div>
                    <dt class="text-caption font-semibold text-fg-subtle uppercase">Contagem e conta</dt>
                    <dd class="mt-2 flex flex-wrap gap-2">
                        <x-ui.badge tone="heritage">12</x-ui.badge>
                        <x-ui.badge tone="success">Ativo</x-ui.badge>
                        <x-ui.badge>Inativo</x-ui.badge>
                    </dd>
                </div>
            </dl>
            <p class="mt-4 text-caption text-fg-subtle">Textos longos são truncados dentro da largura disponível. O status da fotografia tem componente próprio (x-admin.status-fotografia), que escolhe o tom.</p>
        </x-dev.specimen>
    </div>
</section>

<section id="alertas" class="scroll-mt-36" aria-labelledby="sg-alertas">
    <h2 id="sg-alertas" class="{{ $tituloSecao }}">Alertas</h2>
    <p class="{{ $descricaoSecao }}">Mensagens na própria página. Erro usa role=alert (anunciado na hora) e sucesso usa role=status; informação e atenção não interrompem o leitor de tela. O papel pode ser trocado pela prop role.</p>

    <div class="mt-6 grid gap-5">
        <x-dev.specimen title="Tons" component="x-ui.alert tone title role" status="padronizado">
            <div class="grid gap-3">
                <x-ui.alert tone="info" title="Conteúdo interno">Este dado não aparece no catálogo público.</x-ui.alert>
                <x-ui.alert tone="success" title="Fotografia salva">As versões otimizadas serão geradas em segundo plano.</x-ui.alert>
                <x-ui.alert tone="warning" title="Atenção">A exclusão permanente remove o registro e seus vínculos definitivamente. Essa operação é irreversível.</x-ui.alert>
                <x-ui.alert tone="danger" title="Revise os campos destacados" role="note">
                    <ul>
                        <li>O título é obrigatório.</li>
                        <li>A década deve ter quatro dígitos.</li>
                    </ul>
                </x-ui.alert>
            </div>
            <p class="mt-4 text-caption text-fg-subtle">No exemplo de erro, o role foi trocado para note, para o Style Guide não ser anunciado como erro ao carregar. Nas telas, o resumo de erros após o envio fica com o padrão alert.</p>
        </x-dev.specimen>

        <x-dev.specimen title="Sem título" component="x-ui.alert tone" description="Sem título, o tipo da mensagem é dito em texto oculto (Informação:, Sucesso:, Atenção:, Erro:)." status="padronizado">
            <div class="grid gap-3 md:grid-cols-2">
                <x-ui.alert>Período interpretado no fuso America/Sao_Paulo.</x-ui.alert>
                <x-ui.alert tone="warning">Uma versão otimizada não foi gerada. <a href="#alertas">Ver detalhes</a></x-ui.alert>
            </div>
        </x-dev.specimen>
    </div>
</section>
