@php
    $upload = config('acervo.uploads.original');
@endphp

<section id="formularios" class="scroll-mt-36" aria-labelledby="sg-formularios">
    <h2 id="sg-formularios" class="{{ $tituloSecao }}">Formulários</h2>
    <p class="{{ $descricaoSecao }}">Componentes x-catalog.* fora de qualquer formulário: nada é enviado nem gravado.</p>

    <div class="mt-6 grid gap-5">
        <x-dev.specimen title="Campos" component="x-catalog.label + x-catalog.control as" description="O label envolve o campo; o controle aceita as=input, select ou textarea." status="padronizado">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-catalog.label for="sg-titulo" text="Título" required>
                    <x-catalog.control id="sg-titulo" type="text" placeholder="Ex.: Inauguração da Praça Santos Dumont" />
                </x-catalog.label>

                <x-catalog.label for="sg-status" text="Status">
                    <x-catalog.control as="select" id="sg-status">
                        <option>Rascunho</option>
                        <option>Em revisão</option>
                        <option>Publicado</option>
                    </x-catalog.control>
                </x-catalog.label>

                <x-catalog.label for="sg-legenda" text="Legenda/descrição" class="sm:col-span-full" x-data="characterCounter(0, 300)">
                    <x-catalog.control as="textarea" id="sg-legenda" rows="3" maxlength="300" x-on:input="update($event.target.value)" />
                    <span
                        class="justify-self-end text-[0.68rem] font-[650] text-ink-400 tabular-nums data-[limit=near]:text-warning data-[limit=over]:text-danger"
                        :data-limit="count > limit ? 'over' : (count >= limit * 0.9 ? 'near' : null)"
                        x-text="formattedCount"
                    >0/300</span>
                </x-catalog.label>

                <x-catalog.label for="sg-codigo" text="Código de referência" class="sm:col-span-full">
                    <x-catalog.control id="sg-codigo" type="text" aria-describedby="sg-codigo-dica" />
                    <span id="sg-codigo-dica" class="text-caption leading-relaxed text-ink-500">Dica do campo: texto auxiliar com peso normal; só o rótulo usa o peso 650.</span>
                </x-catalog.label>
            </div>
            <p class="mt-4 text-caption text-fg-subtle">O contador de caracteres usa o characterCounter real (resources/js/fotografia-form.js), o mesmo da legenda da fotografia.</p>
        </x-dev.specimen>

        <div class="grid gap-5 lg:grid-cols-2">
            <x-dev.specimen title="Tamanhos" component="size=md | sm" status="padronizado">
                <div class="grid gap-4">
                    <x-catalog.label for="sg-tamanho-md" text="md (padrão) · min-h 2,65rem">
                        <x-catalog.control id="sg-tamanho-md" placeholder="Formulários e busca" />
                    </x-catalog.label>
                    <x-catalog.label for="sg-tamanho-sm" text="sm · min-h 2,35rem">
                        <x-catalog.control id="sg-tamanho-sm" size="sm" placeholder="Campos compactos" />
                    </x-catalog.label>
                </div>
            </x-dev.specimen>

            <x-dev.specimen title="Desabilitado" component="x-catalog.control disabled" status="padronizado">
                <div class="grid gap-4">
                    <x-catalog.label for="sg-desabilitado" text="Input desabilitado">
                        <x-catalog.control id="sg-desabilitado" value="Valor bloqueado" disabled />
                    </x-catalog.label>
                    <x-catalog.label for="sg-desabilitado-select" text="Select desabilitado">
                        <x-catalog.control as="select" id="sg-desabilitado-select" disabled><option>Administrador</option></x-catalog.control>
                    </x-catalog.label>
                </div>
                <p class="mt-4 text-caption text-fg-subtle">Fundo mist-100, borda mist-200, texto ink-500 e cursor bloqueado.</p>
            </x-dev.specimen>
        </div>

        <x-dev.specimen title="Estado inválido" component="x-catalog.control aria-invalid=true · x-catalog.error" description="A aparência segue o aria-invalid, o mesmo atributo que os leitores de tela anunciam. Ligue a mensagem ao campo com aria-describedby." status="padronizado">
            <div class="grid gap-4 md:grid-cols-3">
                <x-catalog.label for="sg-erro" text="Input" required>
                    <x-catalog.control id="sg-erro" value="Texto longo demais" aria-invalid="true" aria-describedby="sg-erro-mensagem" />
                    <x-catalog.error id="sg-erro-mensagem">O título deve ter no máximo 255 caracteres.</x-catalog.error>
                </x-catalog.label>
                <x-catalog.label for="sg-erro-select" text="Select" required>
                    <x-catalog.control as="select" id="sg-erro-select" aria-invalid="true" aria-describedby="sg-erro-select-mensagem"><option value="">Selecione</option></x-catalog.control>
                    <x-catalog.error id="sg-erro-select-mensagem">Selecione o perfil do usuário.</x-catalog.error>
                </x-catalog.label>
                <x-catalog.label for="sg-erro-textarea" text="Textarea (sm)">
                    <x-catalog.control as="textarea" size="sm" id="sg-erro-textarea" rows="2" aria-invalid="true" aria-describedby="sg-erro-textarea-mensagem">Pouco</x-catalog.control>
                    <x-catalog.error id="sg-erro-textarea-mensagem">Descreva a finalidade com pelo menos 10 caracteres.</x-catalog.error>
                </x-catalog.label>
            </div>
            <p class="mt-4 text-caption text-fg-subtle">Clique num campo inválido para ver o foco em vermelho. Aqui o aria-invalid é passado à mão; nos formulários, a prop error faz isso a partir dos erros de validação (abaixo).</p>
        </x-dev.specimen>

        <x-dev.specimen title="Campo com validação do Laravel" component="x-catalog.control error · x-catalog.error field" description="Padrão dos formulários do admin. Com erro na chave, o controle recebe aria-invalid e aria-describedby; a mensagem fica logo abaixo do rótulo, fora dele, com o id correspondente." status="padronizado">
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                <pre class="self-start overflow-x-auto rounded-md bg-ink-950 p-4 text-caption leading-relaxed text-mist-100"><code>&lt;div class="grid gap-1.5"&gt;
    &lt;x-catalog.label for="titulo" text="Título" required&gt;
        &lt;x-catalog.control id="titulo" name="titulo"
            :value="old('titulo')" required maxlength="255"
            error="titulo" /&gt;
    &lt;/x-catalog.label&gt;
    &lt;x-catalog.error field="titulo" as="p" /&gt;
&lt;/div&gt;</code></pre>
                <ul class="grid content-start gap-2 text-sm leading-6 text-fg-muted">
                    <li><strong class="text-fg">Rótulo:</strong> o x-catalog.label envolve o campo e usa o mesmo id no for; required no rótulo mostra "obrigatório" e acompanha o required do campo.</li>
                    <li><strong class="text-fg">Inválido:</strong> error="titulo" marca o campo quando há erro nessa chave. Em bags nomeados, use error-bag (no x-catalog.error, bag).</li>
                    <li><strong class="text-fg">Mensagem:</strong> x-catalog.error field="titulo" só aparece com erro e recebe o id "titulo-erro", o mesmo que o aria-describedby do campo aponta. Se o id do controle for diferente do nome, informe for.</li>
                    <li><strong class="text-fg">Dicas:</strong> passe aria-describedby com o id da dica; a mensagem de erro é acrescentada depois dela.</li>
                    <li><strong class="text-fg">Resumo:</strong> no topo do formulário, um x-ui.alert tone="danger" com título curto, sem repetir as mensagens dos campos.</li>
                </ul>
            </div>
        </x-dev.specimen>

        <x-dev.specimen title="Ações do formulário" component="x-catalog.button type=submit · variant=secondary href" description="Enviar é primary com type=submit; cancelar é um link secondary. No celular, as ações empilham com a principal em cima; a partir de sm, ficam à direita." status="padronizado">
            <div class="flex flex-col-reverse gap-3 border-t border-line pt-5 sm:flex-row sm:justify-end">
                <x-catalog.button variant="secondary" href="#formularios">Cancelar</x-catalog.button>
                <x-catalog.button type="submit">Salvar alterações</x-catalog.button>
            </div>
            <p class="mt-4 text-caption text-fg-subtle">Os demais botões têm type=button por padrão e não enviam o formulário por acidente.</p>
        </x-dev.specimen>

        <x-dev.specimen title="Arquivo (arrastar e soltar)" component="x-catalog.file-dropzone" description="Componente real com validação no navegador. Fora de um formulário, o arquivo escolhido não é enviado." status="padronizado">
            <x-catalog.file-dropzone
                name="sg-arquivo"
                :mime-types="$upload['mime_types']"
                :extensions="$upload['extensions']"
                :max-kb="$upload['max_kb']"
            />
        </x-dev.specimen>

        <x-dev.specimen title="Bloco de formulário" component="x-catalog.section title description" description="Sem title nem actions, é só o cartão: assim os cadastros simples envolvem o formulário." status="padronizado">
            <x-catalog.section title="Dados principais" description="Comece pelas informações que identificam a fotografia.">
                <x-slot:actions>
                    <x-catalog.button variant="secondary" size="sm" icon="plus">Ação do bloco</x-catalog.button>
                </x-slot:actions>
                <p class="text-sm text-fg-muted">Conteúdo do bloco.</p>
            </x-catalog.section>
        </x-dev.specimen>

        <x-dev.specimen title="Seletor de catálogo e cadastro rápido" component="livewire:admin.catalog-picker" description="Depende do banco e de um formulário de fotografia; não é reproduzido aqui." status="referencia">
            <p class="text-sm text-fg-muted">Ver em <a href="{{ route('admin.fotografias.create') }}" class="font-semibold text-institutional underline underline-offset-4">Cadastrar fotografia</a> (Autoria, Pessoas e Classificações). O mesmo vale para os campos de data histórica (historicalDateFields).</p>
        </x-dev.specimen>

        <x-dev.specimen title="Formulários nas telas" description="Todos os formulários do admin usam x-admin.page-header, x-catalog.section, x-catalog.label/control/error e x-catalog.button." status="padronizado">
            <p class="text-sm text-fg-muted">Ver em <a href="{{ route('admin.categorias.create') }}" class="font-semibold text-institutional underline underline-offset-4">Nova categoria</a>, <a href="{{ route('admin.usuarios.create') }}" class="font-semibold text-institutional underline underline-offset-4">Novo usuário</a>, <a href="{{ route('admin.perfil.edit') }}" class="font-semibold text-institutional underline underline-offset-4">Meu perfil</a> (dois bags de erro) e nos filtros do <a href="{{ route('admin.downloads.index') }}" class="font-semibold text-institutional underline underline-offset-4">Relatório de downloads</a>. No formulário de fotografia, as mensagens ficam dentro do rótulo com aria-hidden (fora do nome do campo, lidas como descrição), para não mexer na estrutura do Alpine; o contador da legenda segue o mesmo padrão.</p>
        </x-dev.specimen>
    </div>
</section>
