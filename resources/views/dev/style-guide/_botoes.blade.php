@php
    $variantes = [
        'primary' => 'Ação principal da tela ou do formulário.',
        'secondary' => 'Ações secundárias: cancelar, filtrar, editar na linha.',
        'outline' => 'Ação de destaque sem competir com a principal: restaurar, exportar.',
        'danger' => 'Ação destrutiva. Discreta na tela; a confirmação fica no diálogo.',
        'danger-solid' => 'Só a confirmação final de uma ação destrutiva, no diálogo de confirmação.',
        'ghost' => 'Ação discreta, sem fundo: itens de menu, ações de apoio.',
    ];
@endphp

<section id="botoes" class="scroll-mt-36" aria-labelledby="sg-botoes">
    <h2 id="sg-botoes" class="{{ $tituloSecao }}">Botões</h2>
    <p class="{{ $descricaoSecao }}">Passe o mouse para ver o hover e use Tab para ver o foco. Todos os exemplos são o componente real.</p>

    <div class="mt-6 grid gap-5">
        <x-dev.specimen title="Variantes e tamanhos" component="x-catalog.button variant size icon" description="Button com type=button por padrão (use type=submit para enviar); vira link com href. Atributos HTML, Alpine, Livewire e data-* passam direto ao elemento." status="padronizado">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[44rem] text-left">
                    <thead>
                        <tr class="text-caption font-semibold text-fg-subtle uppercase">
                            <th scope="col" class="pr-4 pb-3">Variante</th>
                            <th scope="col" class="pr-4 pb-3">md (padrão)</th>
                            <th scope="col" class="pr-4 pb-3">sm</th>
                            <th scope="col" class="pr-4 pb-3">Com ícone</th>
                            <th scope="col" class="pr-4 pb-3">Desabilitado</th>
                            <th scope="col" class="pb-3">Carregando</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($variantes as $variante => $uso)
                            <tr>
                                <th scope="row" class="max-w-56 py-3 pr-4 align-middle font-normal">
                                    <code class="font-mono text-caption text-institutional">{{ $variante }}</code>
                                    <span class="mt-0.5 block text-caption text-fg-subtle">{{ $uso }}</span>
                                </th>
                                <td class="py-3 pr-4 align-middle"><x-catalog.button :variant="$variante">Salvar</x-catalog.button></td>
                                <td class="py-3 pr-4 align-middle"><x-catalog.button :variant="$variante" size="sm">Salvar</x-catalog.button></td>
                                <td class="py-3 pr-4 align-middle"><x-catalog.button :variant="$variante" :icon="$variante === 'danger' ? 'trash-2' : 'plus'">{{ $variante === 'danger' ? 'Excluir' : 'Adicionar' }}</x-catalog.button></td>
                                <td class="py-3 pr-4 align-middle"><x-catalog.button :variant="$variante" disabled>Salvar</x-catalog.button></td>
                                <td class="py-3 align-middle"><x-catalog.button :variant="$variante" loading>Salvando</x-catalog.button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-dev.specimen>

        <div class="grid gap-5 lg:grid-cols-2">
            <x-dev.specimen title="Links como botão" component="x-catalog.button href" description="Desabilitado ou carregando, o link perde o href: não navega nem recebe foco, e é anunciado com aria-disabled." status="padronizado">
                <div class="flex flex-wrap items-center gap-3">
                    <x-catalog.button href="#botoes" icon="arrow-left">Voltar</x-catalog.button>
                    <x-catalog.button variant="outline" size="sm" icon="download" href="#botoes">Exportar CSV</x-catalog.button>
                    <x-catalog.button variant="secondary" href="#botoes" disabled>Link desabilitado</x-catalog.button>
                </div>
            </x-dev.specimen>

            <x-dev.specimen title="Carregamento dinâmico" component="x-catalog.button x-bind:aria-busy" description="Com x-bind:aria-busy, o spinner e o texto (processando) para leitores de tela aparecem só enquanto o valor for true. Combine com x-bind:disabled para bloquear novos cliques." status="padronizado">
                <div x-data="{ enviando: false, enviar() { this.enviando = true; setTimeout(() => this.enviando = false, 2000) } }" class="flex flex-wrap items-center gap-3">
                    <x-catalog.button icon="send" x-on:click="enviar()" x-bind:aria-busy="enviando ? 'true' : 'false'" x-bind:disabled="enviando">
                        <span x-text="enviando ? 'Enviando…' : 'Enviar (simula 2 s)'">Enviar (simula 2 s)</span>
                    </x-catalog.button>
                    <span class="text-caption text-fg-subtle" role="status" x-text="enviando ? 'Enviando…' : ''"></span>
                </div>
                <p class="mt-4 text-caption text-fg-subtle">Com a prop loading (renderização no servidor), o botão já sai desabilitado e com aria-busy=true. Em Livewire, o padrão atual é wire:loading.attr=disabled (cadastro rápido); o spinner exige aria-busy=true.</p>
            </x-dev.specimen>
        </div>

        <x-dev.specimen title="Botões com marcação própria" description="Os poucos botões que continuam fora do x-catalog.button, de propósito." status="referencia">
            <p class="text-sm text-fg-muted">O botão do menu de ações do arquivo (ícone, size-9) e o botão do menu do usuário (nome e avatar) têm forma própria e atributos de Alpine que dependem dela. Todos os demais botões do admin, inclusive os do diálogo de confirmação e os botões de fechar dos modais, são x-catalog.button.</p>
        </x-dev.specimen>

        <x-dev.specimen title="x-public.button" component="x-public.button" description="Variante única do site público. Exemplos dentro do layout público." status="padronizado">
            <x-catalog.button variant="secondary" size="sm" icon="arrow-right" :href="route('admin.style-guide.publico').'#botoes'">Ver no Style Guide público</x-catalog.button>
        </x-dev.specimen>
    </div>
</section>
