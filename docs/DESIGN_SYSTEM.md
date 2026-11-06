# Design System do Memórias Vivas

Guia das interfaces do projeto: onde ficam os tokens, quais componentes existem, quando usar cada um e as convenções que mantêm o admin e o site público consistentes.

Antes de criar marcação nova, procure aqui e no Style Guide (`/admin/_style-guide`) se já existe um componente para isso.

## 1. Arquitetura visual

### Tecnologias

- **Blade**: componentes anônimos em `resources/views/components`.
- **Tailwind CSS 4**: tokens em `@theme` e regras compartilhadas em `@layer components`, ambos em `resources/css/app.css`. Não há `tailwind.config.js`.
- **Alpine.js** (`resources/js`) e **Livewire 4** (`app/Livewire`) para interações.
- **Lucide** (`blade-lucide-icons`): ícones como `<x-lucide-nome />` ou pela prop `icon` dos componentes.

### Famílias de componentes

| Prefixo | Responsabilidade | Onde |
|---|---|---|
| `x-catalog.*` | Formulários e botões do admin: rótulo, campo, mensagem de erro, botão, bloco de formulário, envio de arquivo. | `components/catalog` |
| `x-admin.*` | Estrutura das telas do admin: cabeçalho de página, tabela, busca, ordenação, linha clicável, blocos de informação, status de fotografia. | `components/admin` |
| `x-ui.*` | Feedback compartilhado pelo admin e pelo site público: alertas, badges, estados vazios. | `components/ui` |
| `x-public.*` | Linguagem editorial do site público: títulos serifados, seções, botão público, links, tags, busca do catálogo. | `components/public` |
| `x-layouts.*` | Layouts: `app` (base), `admin` e `public`. | `components/layouts` |

O nome `x-catalog.*` é histórico (nasceu no formulário de catalogação). Ele continua sendo a família de formulários do admin e não deve ser renomeado.

### Tokens

Todos em `resources/css/app.css`, no bloco `@theme`. Cada token gera as classes do Tailwind (`bg-*`, `text-*`, `border-*`, `shadow-*`).

| Grupo | Tokens | Uso |
|---|---|---|
| Marca | `institutional` (`-strong`, `-soft`, `-muted`, `-light`), `heritage-*` | Ações, links, destaques; dourado do eyebrow e das contagens |
| Neutros | `ink-*` (texto), `mist-*` (superfícies e bordas), `paper` | Escala completa; prefira os aliases abaixo |
| Aliases semânticos | `fg`, `fg-muted`, `fg-subtle`, `line`, `line-strong`, `surface-muted` | Texto principal, secundário e de apoio; bordas; fundo suave |
| Estados | `danger`, `error`, `error-strong`, `error-soft`, `error-ink`, `success`, `success-soft`, `warning`, `warning-ink`, `warning-soft` | Erros, sucesso, atenção. Para texto de atenção sobre fundo claro, use `warning-ink` (`warning` não atinge 4,5:1) |
| Tipografia do admin | `text-caption` (0,72rem), `text-label` (0,78rem), `text-ui` (0,8rem), `text-section` (1,05rem) | Legendas e erros; rótulos; botões; títulos de bloco |
| Sombras | `shadow-card`, `shadow-popover`, `shadow-modal`, `shadow-focus` | Cartões e painéis; menus; diálogos; foco de campo |

Não use `stone-*`, `red-*` nem `gray-*` do Tailwind: o projeto não tem mais usos fora do Style Guide (que os mostra apenas como referência).

### Style Guide

Duas páginas internas com os componentes reais:

- `/admin/_style-guide`: tokens, botões, formulários, cabeçalhos, badges, alertas, tabelas, painéis, diálogos, menus, estados vazios.
- `/admin/_style-guide/publico`: componentes públicos dentro do layout público.

Acesso: somente administradores autenticados, nos ambientes `local`, `testing` e `e2e`. Em produção a rota não é registrada e o controller responde 404 (`StyleGuideAccessTest`). As capturas de `tests/Browser/style-guide.spec.js` protegem a aparência dessas páginas.

## 2. Componentes

### Formulários do admin (`x-catalog.*`)

**`x-catalog.label` + `x-catalog.control` + `x-catalog.error`**: um campo completo.

```blade
<div class="grid gap-1.5">
    <x-catalog.label for="titulo" text="Título" required>
        <x-catalog.control id="titulo" name="titulo" :value="old('titulo')" required maxlength="255" error="titulo" />
    </x-catalog.label>
    <x-catalog.error field="titulo" as="p" />
</div>
```

- `x-catalog.label`: envolve o campo; `required` mostra "obrigatório" (use junto com o `required` do campo).
- `x-catalog.control`: `as="input|select|textarea"`, `size="md|sm"`. `error="chave"` marca o campo com `aria-invalid` e acrescenta `{id}-erro` ao `aria-describedby` quando há erro nessa chave; `error-bag` para bags nomeados (ex.: `profileUpdate` no perfil).
- `x-catalog.error`: com `field`, só aparece se houver erro e recebe o id `{for ?? field}-erro`; `bag` para bags nomeados. Sem `field`, mostra o texto do slot.
- A mensagem fica **fora** do `<label>`, para não entrar no nome acessível. Exceção documentada: no formulário de fotografia, a mensagem e o contador da legenda ficam dentro do rótulo com `aria-hidden="true"` (lidos como descrição), para não mexer na estrutura do Alpine (`x-show` dos campos de data, `x-data` do contador).

**`x-catalog.button`**: todos os botões do admin.

```blade
<x-catalog.button type="submit">Salvar alterações</x-catalog.button>
<x-catalog.button variant="secondary" :href="route('admin.categorias.index')">Cancelar</x-catalog.button>
<x-catalog.button variant="danger" size="sm" type="submit">Excluir</x-catalog.button>
```

| Variante | Quando |
|---|---|
| `primary` (padrão) | A ação principal da tela ou do formulário |
| `secondary` | Cancelar, editar na linha, ações de apoio |
| `outline` | Destaque sem competir com a principal: restaurar, exportar |
| `danger` | Ação destrutiva na tela (discreta) |
| `danger-solid` | Somente a confirmação final de uma ação destrutiva (diálogo de confirmação) |
| `ghost` | Ações discretas, botões de fechar (`icon="x"`) |

`type="button"` é o padrão; use `type="submit"` para enviar. Com `href` vira link; com `disabled` ou `loading`, o link perde o `href`. `loading` desabilita e mostra o spinner com `aria-busy`. Atributos HTML, Alpine, Livewire e `data-*` passam direto.

**`x-catalog.section`**: bloco de formulário e painel de detalhes.

```blade
<x-catalog.section title="Dados de catalogação" description="…">…</x-catalog.section>
<x-catalog.section flush title="Histórico de auditoria" title-id="photo-audit-history-title">…lista…</x-catalog.section>
<x-catalog.section>…formulário sem título…</x-catalog.section>
```

`flush` remove o espaçamento interno (listas e tabelas de borda a borda); `title-id` rotula a seção pelo título; slot `actions` à direita do título.

**`x-catalog.file-dropzone`**: envio de arquivo com validação no navegador (`name`, `mime-types`, `extensions`, `max-kb`, `error`). O `[data-dropzone]` e o `clear()` são usados pelo modal de substituição do original.

### Estrutura do admin (`x-admin.*`)

- **`x-admin.page-header`**: o cabeçalho de toda página do admin. O título é o `h1`.

  ```blade
  <x-admin.page-header class="mb-6" eyebrow="Catalogação" title="Categorias" description="…"
      :back-href="route('admin.fotografias.index')" back-label="Voltar para fotografias">
      <x-ui.badge tone="success">Publicado</x-ui.badge>                {{-- slot padrão: abaixo da descrição --}}
      <x-slot:actions><x-catalog.button icon="plus" :href="…">Nova categoria</x-catalog.button></x-slot:actions>
  </x-admin.page-header>
  ```

  Não tem margem externa: quem usa define (`class="mb-6"`).
- **`x-admin.table`**: contêiner das tabelas (borda, sombra, rolagem horizontal localizada, aviso "Deslize a tabela…" abaixo de `xl`, paginação no rodapé, slot `vazio`). Cada listagem define a largura mínima da tabela em `table-class` (ex.: `min-w-[60rem]`) e, se preciso, a das colunas principais nas células (`min-w-56`); abaixo dela a tabela rola em vez de espremer as colunas. `thead`, `tbody`, `tr` e `td` ficam na listagem; o estilo comum de `th` e `td` vem da classe `.admin-table` no `app.css`. Tabelas fora do componente (ex.: dentro de um painel) usam `x-admin.aviso-rolagem-tabela` e `.admin-table` diretamente.

  ```blade
  <x-admin.table table-class="min-w-[60rem]" :paginator="$categorias" :vazia="$categorias->isEmpty()">
      <x-slot:vazio>…x-ui.empty-state ou x-admin.busca-sem-resultados…</x-slot:vazio>
      <thead><tr><x-admin.coluna-ordenavel rotulo="Título" chave="titulo" :ordenacao="$ordenacao" :action="…" :parametros="$parametros" /><th scope="col" class="text-right">Ações</th></tr></thead>
      <tbody>…</tbody>
  </x-admin.table>
  ```
- **`x-admin.coluna-ordenavel`**: cabeçalho de coluna ordenável (`aria-sort`, link que mantém pesquisa e filtros).
- **`x-admin.link-linha`**: torna a linha inteira clicável. A `<tr>` precisa de `class="relative"` e as ações da linha ficam num contêiner `relative z-10`.
- **`x-admin.busca-listagem`** e **`x-admin.filtros-fotografias`**: pesquisa das listagens (GET; a URL guarda os critérios).
- **`x-admin.busca-sem-resultados`**: pesquisa ou filtros sem resultado, com a ação de limpar (por dentro é o `x-ui.empty-state`).
- **`x-admin.info-block`**: par rótulo/valor somente leitura, dentro de um `<dl>`; `empty` para "Não informado".
- **`x-admin.status-fotografia`**: badge do status de catalogação (escolhe o tom).

### Feedback compartilhado (`x-ui.*`)

- **`x-ui.alert`**: `tone="info|success|warning|danger"`, `title` opcional. O `role` é automático: `alert` para `danger`, `status` para `success`, nenhum para `info` e `warning`. Sem título, um prefixo oculto diz o tipo da mensagem.

  ```blade
  <x-ui.alert tone="danger" title="Verifique os dados informados." class="mb-5">
      <p>Corrija os campos indicados abaixo.</p>
  </x-ui.alert>
  ```
- **`x-ui.badge`**: `tone="neutral|heritage|info|success|warning|danger"`, `icon` opcional. O texto sempre identifica o estado; a cor só reforça.
- **`x-ui.empty-state`**: `title`, `description`, `icon`, `heading="h2|h3|h4"` e slot `action`. A moldura (cartão, borda tracejada) é de quem usa.

### Site público (`x-public.*`)

| Componente | Uso |
|---|---|
| `x-public.heading` | Títulos serifados (`as="h1|h2|…"`); tamanho e peso vêm de quem usa |
| `x-public.eyebrow` | Rótulo em caixa alta acima dos títulos |
| `x-public.container`, `x-public.section` | Largura e espaçamento vertical das seções (`tinted` para fundo suave) |
| `x-public.button` | Botão público (variante única; link com `href`; estados desabilitado e `aria-busy`) |
| `x-public.text-link`, `x-public.back-link` | Links de texto sublinhados; voltar com seta |
| `x-public.page-hero`, `x-public.invite`, `x-public.steps` | Abertura, faixa de chamada e etapas das páginas institucionais |
| `x-public.tag-list`, `x-public.record-groups`, `x-public.record-section` | Classificações e ficha documental da fotografia |
| `x-public.search-form`, `x-public.gallery-item`, `x-public.empty-state` | Catálogo |

Campos dos formulários públicos (colaboração e download) usam as classes `.public-label`, `.public-control`, `.public-field-error` e `.public-required` do `app.css`, não `x-catalog.control`: são maiores e seguem a linguagem editorial.

### Classes compartilhadas no `app.css`

Usadas quando o elemento tem atributos de Alpine ou Livewire no próprio nó, e um componente Blade obrigaria a reescrevê-los:

- `.admin-table`: cabeçalhos e células das tabelas.
- `.admin-dialog`: casca dos `<dialog>` do admin (posição, borda, `shadow-modal`, sobreposição). A largura fica em cada diálogo.
- `.admin-menu` e `.admin-menu-item`: menus suspensos do admin.
- `.public-*`: campos dos formulários públicos.

## 3. Convenções

As regras **obrigatórias** protegem acessibilidade e contratos; as **preferências** mantêm a consistência visual e admitem exceções justificadas.

### Hierarquia tipográfica

- **Obrigatório**: um único `h1` por página; títulos de bloco e painel em `h2`; níveis sem saltos.
- **Obrigatório**: o admin é sem serifa. A fonte serifada (`font-serif`, via `x-public.heading`) é exclusiva do site público.
- Preferência: no admin, `h1` vem do `x-admin.page-header`; blocos e painéis usam `text-section` (via `x-catalog.section`).

### Cabeçalhos do admin

- **Obrigatório**: toda página do admin usa `x-admin.page-header` (formulários, listagens, detalhes, painel).
- Preferência: eyebrow com o contexto ("Catalogação", "Acervo", "Administração"); link de retorno nas páginas de detalhes e edição; ações no slot `actions`.

### Botões

- **Obrigatório**: botões do admin são `x-catalog.button`; os do site público, `x-public.button`. Não recrie classes de botão.
- **Obrigatório**: `type="submit"` só em quem envia; ações destrutivas passam pelo diálogo de confirmação (`data-confirm-title`, `data-confirm-message`, `data-confirm-label` e, para não destrutivas, `data-confirm-variant="primary"` no `<form>`).
- Preferência: uma ação `primary` por área; `secondary` para cancelar; `danger` discreto nas telas e `danger-solid` só no diálogo.

### Campos e mensagens de erro

- **Obrigatório**: todo campo com rótulo associado (`for` + `id`); campos inválidos com `aria-invalid="true"` e `aria-describedby` apontando a mensagem (props `error` e `field` no admin; `@error … aria-invalid … aria-describedby` no público).
- **Obrigatório**: a mensagem de erro não entra no nome acessível do campo (fora do `<label>` ou com `aria-hidden` dentro dele, sempre referenciada por `aria-describedby`).
- **Obrigatório**: mensagens em português. As traduções ficam em `lang/pt_BR`; nomes de campos para as mensagens, em `validation.attributes`. Mensagens específicas continuam nos Form Requests (`messages()`).
- Preferência: mensagem junto ao campo e um resumo curto no topo (`x-ui.alert tone="danger"`) sem repetir as mensagens; o resumo lista só erros sem campo visível (seletores Livewire, campos ocultos).
- Preferência: indicar obrigatoriedade no rótulo (`required` no admin; asterisco `.public-required` com a legenda "Campo obrigatório" no público).

### Tabelas e listagens

- **Obrigatório**: tabelas do admin dentro de `x-admin.table`; `th` com `scope="col"` (ou `scope="row"` no rótulo da linha).
- **Obrigatório**: a página nunca rola na horizontal; a rolagem fica no contêiner da tabela.
- Preferência: título da linha com `x-admin.link-linha` e `truncate`; ações por linha em `size="sm"`; contagens em `x-ui.badge tone="heritage"`.

### Badges e alertas

- **Obrigatório**: o texto identifica o estado; não dependa só da cor.
- Tons usados: Publicado `success`; Em revisão `info`; Rascunho e Arquivado `neutral`; Público `heritage`; Privado `neutral`; processamento Pendente/Em processamento `info`, Concluído `success`, Sem progresso `warning`, Falhou `danger`; conta Ativa `success`, Inativa `neutral`.
- Preferência: `x-ui.alert` para feedback; blocos de conteúdo (ex.: condições de uso do download) mantêm a aparência editorial.

### Cards, seções e painéis

- Preferência: `x-catalog.section` para blocos de formulário e painéis de detalhes; `x-admin.info-block` para dados somente leitura; superfícies novas com `rounded-lg border border-line bg-white shadow-card`.
- Não crie um componente de "card" genérico: os cartões de indicadores do painel e do relatório de downloads compartilham apenas os tokens.

### Modais e menus

- **Obrigatório**: diálogos com `<dialog>` nativo, título ligado por `aria-labelledby`, fechar com nome acessível, Escape funcionando e foco devolvido a quem abriu.
- **Obrigatório**: menus com `aria-expanded` no gatilho, Escape devolvendo o foco e clique fora fechando.
- Preferência: `.admin-dialog` (cabeçalho com título e fechar, conteúdo em `surface-muted`, rodapé com as ações à direita e empilhadas no celular) e `.admin-menu`/`.admin-menu-item`.

### Estados vazios

- Preferência: `x-ui.empty-state` no admin, diferenciando "nada cadastrado" de "nada encontrado" (`x-admin.busca-sem-resultados`, com a ação de limpar). No site público, `x-public.empty-state`; o catálogo tem um estado vazio próprio, com título e ação de recuperação.

### Foco e acessibilidade

- **Obrigatório**: foco visível só no teclado (`focus-visible`). Admin: `focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-institutional` (já incluso nos componentes); campos: `focus:border-institutional focus:shadow-focus`. Público: regra global `.public-shell` (3px), que cobre links, botões, `summary`, `input`, `select` e `textarea`.
- **Obrigatório**: não use `focus:ring-*` nem `outline-none` sem substituto visível.
- **Obrigatório**: ícones decorativos com `aria-hidden="true"`; botões só com ícone com `aria-label`.

### Responsividade

- **Obrigatório**: sem rolagem horizontal da página em 390px; conferir 390px e 1280px.
- Preferência: contêineres das páginas com `px-4 sm:px-6 lg:px-8` (alinhados ao cabeçalho do layout); formulários estreitos (`max-w-3xl`) e listagens largas (`max-w-7xl`); ações empilhadas no celular (`flex-col-reverse … sm:flex-row`).

### Regras globais do admin

`.admin-main` aplica `overflow-wrap: anywhere` e alinha ao centro o texto de botões e links (para conteúdo longo não estourar o layout). Há exceções no próprio `app.css` para tabelas (`.admin-table`), para `.truncate` e para os itens de menu. Se um componente novo precisar de outro comportamento, restrinja a regra no `app.css` em vez de acumular exceções nas views.

## 4. Criando ou alterando componentes

1. **Procure um equivalente** neste guia, no Style Guide e em `resources/views/components`. Um ajuste pequeno e compatível num componente existente é preferível a um componente novo.
2. **Reutilize** os componentes existentes na marcação nova.
3. **Use tokens.** Valores arbitrários (`[…]`) só para necessidades de layout específicas e sem token equivalente; se o valor se repetir, ele vira token.
4. **Crie componentes só com benefício concreto**: o mesmo padrão em mais de um lugar, ou uma API que esconda detalhes de acessibilidade. Uma estrutura usada uma única vez fica na view.
5. **Preserve contratos.** Atributos Alpine (`x-*`, `@*`, `:*`), Livewire (`wire:*`), `data-*`, ids, nomes de campos e `aria-*` são usados por JavaScript e testes. Ao mover marcação, mantenha-os idênticos.
6. **Atualize o Style Guide** ao criar uma variante, um estado ou um componente; exemplos usam o componente real.
7. **Teste na medida do risco**: testes de renderização em `tests/Feature` para componentes e contratos; testes de navegador em `tests/Browser` para interações (foco, diálogos, Alpine, Livewire). Rode o `style-guide.spec.js` quando a aparência do Style Guide mudar e revise as capturas antes de atualizá-las.

Não documente aqui componentes que ainda não existem.

## 5. Testes relacionados

| Arquivo | Cobre |
|---|---|
| `tests/Feature/ComponentesFundamentaisTest.php` | Botões, campos, rótulos, cabeçalho, badges, alertas, estados vazios |
| `tests/Feature/FormulariosAdministrativosTest.php` | Formulários do admin: contratos e validação acessível |
| `tests/Feature/ListagensAdministrativasTest.php` | `x-admin.table`, cabeçalhos, badges, ações por linha, ordenação e paginação |
| `tests/Feature/DetalhesAdministrativosTest.php` | Páginas de detalhes, painel, `x-catalog.section` |
| `tests/Feature/DialogosMenusTest.php` | Contratos dos diálogos e menus |
| `tests/Feature/FormularioFotografiaAcessibilidadeTest.php` | Formulário de fotografia: erros, contador, datas ocultas |
| `tests/Feature/FormulariosPublicosTest.php` | Formulários e componentes públicos |
| `tests/Feature/MensagensValidacaoPortuguesTest.php` | Mensagens de validação em português |
| `tests/Feature/StyleGuideAccessTest.php` | Acesso ao Style Guide |
| `tests/Browser/style-guide.spec.js` | Capturas e estados do Style Guide |
| `tests/Browser/dialogos-menus.spec.js` | Diálogos e menus no navegador |
| `tests/Browser/formularios-acessibilidade.spec.js` | Nome e descrição acessíveis, foco público, estados inválidos |

A suíte `tests/Browser/visual.spec.js` está suspensa: seus snapshots estão desatualizados e não devem ser regenerados sem uma revisão própria.
