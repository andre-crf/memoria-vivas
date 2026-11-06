@php
    $paginaAtual = route('admin.style-guide.admin');
    $link = 'font-semibold text-institutional underline underline-offset-4';
    $referencias = [
        ['Gráficos sem dados', 'Relatório de downloads (série e distribuições)', 'admin/downloads/index.blade.php'],
        ['Prévia indisponível (proporção da imagem)', 'Detalhes de uma fotografia sem versões', 'admin/fotografias/show.blade.php'],
    ];
@endphp

<section id="listagens" class="scroll-mt-36" aria-labelledby="sg-listagens">
    <h2 id="sg-listagens" class="{{ $tituloSecao }}">Busca, tabelas e paginação</h2>
    <p class="{{ $descricaoSecao }}">Componentes reais com dados de exemplo. A busca, a ordenação e a paginação recarregam esta página com os parâmetros na URL, como nas listagens.</p>

    <div class="mt-6 grid gap-5">
        <x-dev.specimen title="Busca da listagem" component="x-admin.busca-listagem" status="padronizado">
            <x-admin.busca-listagem
                :action="$paginaAtual"
                :termo="$termo"
                placeholder="Título ou descrição"
                :total="$paginacao->total()"
                singular="registro encontrado"
                plural="registros encontrados"
                :search-error="request()->boolean('erro_busca') ? 'Use no máximo 200 caracteres na pesquisa.' : null"
                :ordenacao="$ordenacao"
            />
            <p class="mt-3 text-caption text-fg-subtle">
                Estados: pesquise algo para ver o contador e o botão de limpar;
                <a href="{{ $paginaAtual }}?erro_busca=1#listagens" class="{{ $link }}">ver o estado de erro</a>.
                A variante com filtros (x-admin.filtros-fotografias) está em <a href="{{ route('admin.fotografias.index') }}" class="{{ $link }}">Fotografias</a>.
            </p>
        </x-dev.specimen>

        <x-dev.specimen id="tabela" title="Tabela da listagem" component="x-admin.table · x-admin.coluna-ordenavel · x-admin.link-linha" description="O x-admin.table cuida do contêiner, da rolagem horizontal e da paginação; thead, tbody, linhas e células ficam na listagem. Cabeçalhos e células recebem o estilo comum de .admin-table (app.css), sem classes repetidas." status="padronizado">
            @php($tonsExemplo = ['success', 'neutral', 'info'])
            @php($statusExemplo = ['Publicado', 'Rascunho', 'Em revisão'])
            <x-admin.table :paginator="$paginacao">
                <thead>
                    <tr>
                        <x-admin.coluna-ordenavel rotulo="Título" chave="titulo" :ordenacao="$ordenacao" :action="$paginaAtual" :parametros="array_filter(['q' => $termo])" />
                        <x-admin.coluna-ordenavel rotulo="Data" chave="data" :ordenacao="$ordenacao" :action="$paginaAtual" :parametros="array_filter(['q' => $termo])" />
                        <th scope="col">Status</th>
                        <x-admin.coluna-ordenavel rotulo="Itens associados" chave="itens" :ordenacao="$ordenacao" :action="$paginaAtual" :parametros="array_filter(['q' => $termo])" />
                        <th scope="col" class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (array_slice($paginacao->items(), 0, 3) as $indice => $item)
                        @php($numero = ($paginacao->currentPage() - 1) * $paginacao->perPage() + $item)
                        <tr class="relative">
                            <td class="max-w-sm">
                                <p class="truncate font-semibold text-fg"><x-admin.link-linha href="#tabela">Registro de exemplo {{ $numero }}</x-admin.link-linha></p>
                                <p class="mt-1 text-xs text-fg-subtle">#{{ $numero }}</p>
                            </td>
                            <td class="whitespace-nowrap text-ink-700">Década de 1970</td>
                            <td class="whitespace-nowrap"><x-ui.badge :tone="$tonsExemplo[$indice]">{{ $statusExemplo[$indice] }}</x-ui.badge></td>
                            <td class="whitespace-nowrap"><x-ui.badge tone="heritage">{{ $numero * 3 }}</x-ui.badge></td>
                            <td>
                                <div class="relative z-10 flex justify-end gap-2">
                                    <x-catalog.button variant="secondary" size="sm" href="#tabela">Editar</x-catalog.button>
                                    <x-catalog.button variant="danger" size="sm">Excluir</x-catalog.button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-admin.table>
            <p class="mt-3 text-caption text-fg-subtle">Ordenação atual: <code class="font-mono">{{ $ordenacao->chave }}</code>, <code class="font-mono">{{ $ordenacao->direcao }}</code>. A linha inteira é um link (Tab chega no título); as ações ficam acima dele com relative z-10. Ações por linha: secondary size=sm para editar, outline para restaurar, danger só para o que é destrutivo.</p>
        </x-dev.specimen>

        <x-dev.specimen title="Tabela sem registros" component="x-admin.table vazia · slot vazio" description="Com vazia, o slot vazio ocupa o mesmo contêiner no lugar da tabela. Diferencie listagem sem cadastro (x-ui.empty-state) de pesquisa ou filtros sem resultado (x-admin.busca-sem-resultados)." status="padronizado">
            <x-admin.table :vazia="true">
                <x-slot:vazio>
                    <x-ui.empty-state heading="h3" icon="folder-open" title="Nenhuma categoria cadastrada" description="Quando houver categorias cadastradas, elas aparecerão nesta listagem." />
                </x-slot:vazio>
            </x-admin.table>
        </x-dev.specimen>

        <x-dev.specimen id="paginacao" title="Paginação" component="x-paginacao via paginator links()" description="A mesma paginação atende o admin, o catálogo público e os componentes Livewire (modo livewire)." status="padronizado">
            {{ $paginacao->links() }}
        </x-dev.specimen>
    </div>
</section>

<section id="informacao" class="scroll-mt-36" aria-labelledby="sg-informacao">
    <h2 id="sg-informacao" class="{{ $tituloSecao }}">Blocos informativos</h2>

    <div class="mt-6 grid gap-5 lg:grid-cols-2">
        <x-dev.specimen title="Campo somente leitura" component="x-admin.info-block label empty" status="padronizado">
            <dl class="grid gap-3">
                <x-admin.info-block label="Local na época">Avenida Paraná, centro</x-admin.info-block>
                <x-admin.info-block label="Cedente" :empty="true">Não informado</x-admin.info-block>
                <x-admin.info-block label="Request ID"><span class="block break-all font-mono text-xs">9f1c2a7e-4b8d-4e1f-9c3a-5d2e8b7f6a10</span></x-admin.info-block>
            </dl>
            <p class="mt-4 text-caption text-fg-subtle">Um só padrão de rótulo e valor nos detalhes de fotografia, auditoria e contribuição. Valores técnicos vão no slot com font-mono; uma segunda linha (ex.: perfil e ID do responsável) vai como span block.</p>
        </x-dev.specimen>

        <x-dev.specimen title="Data e hora no fuso de quem vê" component="x-data-hora valor formato vazio" status="padronizado">
            <dl class="grid gap-3 text-left text-sm">
                <div><dt class="text-caption text-fg-subtle">formato completo</dt><dd class="text-fg"><x-data-hora :valor="now()" /></dd></div>
                <div><dt class="text-caption text-fg-subtle">formato curto</dt><dd class="text-fg"><x-data-hora :valor="now()" formato="curto" /></dd></div>
                <div><dt class="text-caption text-fg-subtle">sem valor</dt><dd class="text-fg-subtle"><x-data-hora :valor="null" vazio="Não registrada" /></dd></div>
            </dl>
        </x-dev.specimen>

        <x-dev.specimen title="Painel de detalhes com lista" component="x-catalog.section flush title-id" description="Com flush, a lista ou tabela ocupa a largura do bloco; title-id rotula a seção pelo título (aria-labelledby)." status="padronizado" class="lg:col-span-2">
            <x-catalog.section flush title="Eventos correlacionados" title-id="sg-painel-titulo" description="Outras alterações que fizeram parte da mesma operação.">
                <ul class="divide-y divide-line">
                    @foreach (['Atualização · Praça Arthur Thomas', 'Criação · Versão média'] as $evento)
                        <li class="flex flex-col justify-between gap-3 px-5 py-4 sm:flex-row sm:items-center sm:px-6">
                            <p class="text-sm font-semibold text-fg">{{ $evento }}</p>
                            <a href="#informacao" class="shrink-0 self-start rounded text-sm font-semibold text-institutional hover:text-institutional-strong hover:underline hover:underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-institutional sm:self-auto">Ver evento</a>
                        </li>
                    @endforeach
                </ul>
            </x-catalog.section>
        </x-dev.specimen>

        <x-dev.specimen title="Indicadores" description="Sem componente: o painel e o relatório de downloads têm conteúdos diferentes (link e ícone num, legenda e borda de destaque no outro). Compartilham a superfície (rounded-lg, border-line, shadow-card, p-5) e o número (text-3xl leading-none font-semibold tabular-nums)." status="referencia" class="lg:col-span-2">
            <p class="text-sm text-fg-muted">Ver no <a href="{{ route('admin.dashboard') }}" class="{{ $link }}">Painel</a> e no <a href="{{ route('admin.downloads.index') }}" class="{{ $link }}">Relatório de downloads</a>.</p>
        </x-dev.specimen>
    </div>
</section>

<section id="feedback" class="scroll-mt-36" aria-labelledby="sg-feedback">
    <h2 id="sg-feedback" class="{{ $tituloSecao }}">Feedback e diálogos</h2>

    <div class="mt-6 grid gap-5 lg:grid-cols-2">
        <x-dev.specimen title="Notificações" component="x-admin.toaster · evento toast" description="O toaster do layout recebe o evento de navegador toast, o mesmo disparado pelo Livewire." status="padronizado">
            <div class="flex flex-wrap gap-2" x-data>
                <x-catalog.button variant="secondary" size="sm" icon="circle-check" x-on:click="$dispatch('toast', { message: 'Fotografia salva com sucesso.', type: 'success' })">Sucesso</x-catalog.button>
                <x-catalog.button variant="secondary" size="sm" icon="triangle-alert" x-on:click="$dispatch('toast', { message: 'Fotografia salva, mas uma versão não foi gerada.', type: 'warning' })">Aviso</x-catalog.button>
                <x-catalog.button variant="secondary" size="sm" icon="circle-x" x-on:click="$dispatch('toast', { message: 'Não foi possível salvar a fotografia.', type: 'error' })">Erro</x-catalog.button>
            </div>
        </x-dev.specimen>

        <x-dev.specimen title="Diálogo de confirmação" component="x-confirmation-dialog · data-confirm-*" description="Interceptação real do envio. O formulário de exemplo só recarrega esta página (GET)." status="padronizado">
            <div class="flex flex-wrap gap-2">
                <form method="GET" action="{{ $paginaAtual }}#feedback" data-confirm-title="Confirmar exclusão" data-confirm-message="Exemplo do Style Guide: nada será excluído." data-confirm-label="Excluir registro">
                    <input type="hidden" name="confirmado" value="perigo">
                    <x-catalog.button type="submit" variant="secondary" size="sm" icon="trash-2">Variante danger (padrão)</x-catalog.button>
                </form>
                <form method="GET" action="{{ $paginaAtual }}#feedback" data-confirm-title="Regenerar versões" data-confirm-message="Exemplo do Style Guide: nada será processado." data-confirm-label="Confirmar" data-confirm-variant="primary">
                    <input type="hidden" name="confirmado" value="primaria">
                    <x-catalog.button type="submit" variant="secondary" size="sm" icon="refresh-cw">Variante primary</x-catalog.button>
                </form>
            </div>
            @if (request()->filled('confirmado'))
                <p class="mt-3 text-caption text-fg-muted" role="status">Confirmação recebida ({{ request()->query('confirmado') === 'perigo' ? 'danger' : 'primary' }}).</p>
            @endif
        </x-dev.specimen>

        <x-dev.specimen title="Alertas nas telas" description="Todos os resumos de erro do admin e do site público usam x-ui.alert. O aviso fixo da lixeira é warning, sem role de alerta." status="referencia">
            <p class="text-sm text-fg-muted">Ver na <a href="{{ route('admin.fotografias.trashed') }}" class="{{ $link }}">Lixeira</a> (aviso) e no envio inválido de qualquer cadastro. As condições de uso do download continuam num bloco editorial com borda lateral: são conteúdo, não feedback.</p>
        </x-dev.specimen>

        <x-dev.specimen id="modal" title="Casca de diálogo" component=".admin-dialog · x-catalog.button ghost icon=x" description="Classe compartilhada pelos três diálogos (posição, borda, shadow-modal e fundo de sobreposição); a largura é de cada um. Cabeçalho com título e fechar, conteúdo em surface-muted, rodapé com as ações à direita (empilhadas no celular)." status="padronizado">
            <div x-data>
                <x-catalog.button variant="secondary" size="sm" icon="panel-top" x-ref="abrirModal" x-on:click="$refs.modal.showModal()">Abrir exemplo</x-catalog.button>
                <dialog x-ref="modal" class="admin-dialog w-[min(28rem,calc(100%-2rem))]" aria-labelledby="sg-modal-titulo" x-on:close="$refs.abrirModal.focus()">
                    <div class="flex items-start justify-between gap-4 border-b border-line px-5 py-4">
                        <h3 id="sg-modal-titulo" class="text-section font-semibold">Título do diálogo</h3>
                        <x-catalog.button variant="ghost" size="sm" icon="x" class="-mt-1 -mr-2 flex-none" x-on:click="$refs.modal.close()" aria-label="Fechar" title="Fechar" />
                    </div>
                    <div class="bg-surface-muted p-5 text-left text-sm leading-6 text-fg-muted">Conteúdo do diálogo: campos, dropzone ou texto. Nada aqui é enviado.</div>
                    <div class="flex flex-col-reverse gap-2 border-t border-line px-5 py-4 sm:flex-row sm:justify-end">
                        <x-catalog.button variant="secondary" x-on:click="$refs.modal.close()">Cancelar</x-catalog.button>
                        <x-catalog.button x-on:click="$refs.modal.close()">Confirmar</x-catalog.button>
                    </div>
                </dialog>
            </div>
            <p class="mt-4 text-caption text-fg-subtle">Sem componente Blade de propósito: cada diálogo real tem no próprio &lt;dialog&gt; atributos de Alpine (x-ref, @cancel, @close, @click.self) e, no cadastro rápido, wire:ignore.self dentro de um x-teleport. Fechar com Escape é o comportamento nativo do &lt;dialog&gt;.</p>
        </x-dev.specimen>

        <x-dev.specimen id="menu" title="Menu suspenso" component=".admin-menu · .admin-menu-item" description="Superfície com shadow-popover e itens de 2,5rem com hover e foco institucionais; item desabilitado em cinza, sem hover. Exemplo com o mesmo Alpine dos menus reais: aria-expanded, Escape devolve o foco e clique fora fecha." status="padronizado">
            {{-- Altura reservada: o cartão do exemplo corta o que passa da borda (overflow-hidden). --}}
            <div class="min-h-44">
            <div class="relative inline-block" x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape="if (open) { open = false; $refs.gatilho.focus() }">
                <x-catalog.button variant="secondary" size="sm" icon="ellipsis" x-ref="gatilho" x-on:click="open = !open" x-bind:aria-expanded="open" aria-expanded="false" aria-controls="sg-menu">Ações</x-catalog.button>
                <div id="sg-menu" x-show="open" x-cloak x-transition.origin.top.left class="admin-menu absolute top-[calc(100%+0.35rem)] left-0 z-30 w-56">
                    <a href="#menu" class="admin-menu-item"><x-lucide-pencil class="size-4 flex-none" aria-hidden="true" /> Item de navegação</a>
                    <button type="button" class="admin-menu-item" x-on:click="open = false"><x-lucide-refresh-cw class="size-4 flex-none" aria-hidden="true" /> Item de ação</button>
                    <button type="button" class="admin-menu-item" disabled aria-disabled="true"><x-lucide-loader-circle class="size-4 flex-none" aria-hidden="true" /> Item desabilitado</button>
                </div>
            </div>
            </div>
        </x-dev.specimen>

        <x-dev.specimen title="Modais e menus das telas" description="Os reais dependem de dados, de permissões e, no cadastro rápido, do Livewire." status="referencia">
            <p class="text-sm text-fg-muted">Cadastro rápido em <a href="{{ route('admin.fotografias.create') }}" class="{{ $link }}">Cadastrar fotografia</a> (botões Criar categoria, Cadastrar novo autor…); substituição do original e menu de ações na página de detalhes de uma fotografia com original; menu do usuário no cabeçalho desta página. A interação é coberta por tests/Browser/dialogos-menus.spec.js e hybrid-flows.spec.js.</p>
        </x-dev.specimen>
    </div>
</section>

<section id="estados-vazios" class="scroll-mt-36" aria-labelledby="sg-vazios">
    <h2 id="sg-vazios" class="{{ $tituloSecao }}">Estados vazios</h2>

    <div class="mt-6 grid gap-5">
        <x-dev.specimen title="Variações" component="x-ui.empty-state title description icon heading · slot action" description="Um só componente. A moldura (card, borda tracejada) é de quem usa." status="padronizado">
            <div class="grid gap-4 lg:grid-cols-3">
                <div class="rounded-lg border border-line bg-white">
                    <x-ui.empty-state heading="h3" icon="inbox" title="Nenhuma categoria cadastrada" description="Quando houver categorias cadastradas, elas aparecerão nesta listagem.">
                        <x-slot:action>
                            <x-catalog.button size="sm" icon="plus" href="#estados-vazios">Nova categoria</x-catalog.button>
                        </x-slot:action>
                    </x-ui.empty-state>
                </div>
                <div class="rounded-lg border border-line bg-white">
                    <x-ui.empty-state heading="h3" icon="search-x" title="Nenhum registro encontrado para a pesquisa" description="Revise o texto pesquisado ou remova algum filtro.">
                        <x-slot:action>
                            <x-catalog.button variant="secondary" size="sm" href="#estados-vazios">Limpar pesquisa</x-catalog.button>
                        </x-slot:action>
                    </x-ui.empty-state>
                </div>
                <div class="rounded-lg border border-dashed border-line-strong bg-surface-muted">
                    <x-ui.empty-state heading="h3" title="Nenhum item cadastrado" description="Os itens cadastrados no acervo aparecerão aqui automaticamente." />
                </div>
            </div>
            <p class="mt-4 text-caption text-fg-subtle">Listagem sem registros, pesquisa sem resultados e conteúdo ainda não cadastrado (sem ícone, em bloco tracejado). Use heading conforme a hierarquia da página.</p>
        </x-dev.specimen>

        <x-dev.specimen title="Busca sem resultados" component="x-admin.busca-sem-resultados" description="Mesma API de antes (action, mensagem, dica, limpar); por dentro, é o x-ui.empty-state com a ação de limpar a pesquisa ou os filtros." status="padronizado">
            <div class="rounded-lg border border-line bg-white">
                <x-admin.busca-sem-resultados :action="$paginaAtual" mensagem="Nenhum registro encontrado para a pesquisa" />
            </div>
        </x-dev.specimen>

        <x-dev.specimen title="Estados vazios nas telas atuais" description="Estados vazios mantidos específicos: a prévia da fotografia ocupa a proporção da imagem, e os gráficos do relatório ficam dentro de painéis próprios." status="referencia">
            <ul class="divide-y divide-line text-left text-sm">
                @foreach ($referencias as [$nome, $onde, $arquivo])
                    <li class="grid gap-0.5 py-2.5 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] sm:gap-4">
                        <span class="font-semibold text-fg">{{ $nome }}</span>
                        <span class="text-fg-muted">{{ $onde }} <code class="block font-mono text-caption text-fg-subtle">{{ $arquivo }}</code></span>
                    </li>
                @endforeach
            </ul>
        </x-dev.specimen>
    </div>
</section>

<section id="pendencias" class="scroll-mt-36" aria-labelledby="sg-pendencias">
    <h2 id="sg-pendencias" class="{{ $tituloSecao }}">Diferenças mantidas e pendências</h2>
    <p class="{{ $descricaoSecao }}">O que continua fora dos componentes de propósito e o que fica para depois. As convenções completas estão em docs/DESIGN_SYSTEM.md.</p>

    <div class="mt-6 grid gap-5 lg:grid-cols-2">
        <x-dev.specimen title="Diferenças mantidas" status="referencia">
            <ul class="grid list-disc gap-1.5 pl-5 text-left text-sm text-fg-muted">
                <li>Sombras arbitrárias de uso único: anel de foco do campo inválido, cabeçalho fixo do admin e sombras editoriais do site público (logotipo, cartões da home, drawer).</li>
                <li>Regras globais .admin-main (quebra de palavras e alinhamento de botões e links): tabelas e .truncate têm exceções no app.css; menus usam .admin-menu-item, que alinha à esquerda.</li>
                <li>Grupo de botões da paginação com shadow-sm: a paginação é compartilhada com o site público.</li>
            </ul>
        </x-dev.specimen>

        <x-dev.specimen title="Pendências fora do Design System" status="planejado">
            <ul class="grid list-disc gap-1.5 pl-5 text-left text-sm text-fg-muted">
                <li>Snapshots da suíte visual antiga (tests/Browser/visual.spec.js) desatualizados.</li>
                <li>Filtros do relatório de downloads perdidos após erro de período.</li>
            </ul>
        </x-dev.specimen>
    </div>
</section>
