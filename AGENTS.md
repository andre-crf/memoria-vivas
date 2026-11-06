# Instruções para agentes

## Interfaces e Design System

Antes de criar ou alterar qualquer tela, componente Blade ou estilo, leia `docs/DESIGN_SYSTEM.md`.

- Procure um componente existente em `resources/views/components` (`x-catalog.*`, `x-admin.*`, `x-ui.*`, `x-public.*`) e no Style Guide (`/admin/_style-guide`) antes de escrever marcação própria.
- Não crie versões paralelas de botões, campos, mensagens de erro, alertas, badges, tabelas, cabeçalhos de página ou estados vazios. Se faltar algo, faça um ajuste pequeno e compatível no componente existente.
- Use os tokens de `resources/css/app.css` (`fg`, `line`, `surface-muted`, `institutional`, `danger`, `shadow-card`…). Não use `stone-*`, `red-*`, `gray-*` nem `focus:ring-*`.
- Mantenha a diferença entre as áreas: o admin é sem serifa e usa `x-catalog.*`/`x-admin.*`; o site público é editorial, serifado e usa `x-public.*`.
- Preserve atributos Alpine (`x-*`, `@*`, `:*`), Livewire (`wire:*`), `data-*`, ids, nomes de campos e `aria-*`: JavaScript e testes dependem deles.
- Campos inválidos: `aria-invalid` e `aria-describedby` apontando a mensagem; a mensagem nunca entra no nome acessível do campo. Mensagens de validação em português (`lang/pt_BR`).
- Ao criar ou alterar um componente ou variante, atualize o Style Guide (`resources/views/dev/style-guide`) com o componente real.

## Testes

- PHP: `php artisan test` (no container do projeto, com o limite de memória padrão) e `vendor/bin/pint --dirty`.
- Frontend: `npm run build`.
- Navegador (Playwright): rode os specs ligados ao que mudou (`style-guide`, `dialogos-menus`, `formularios-acessibilidade`, `hybrid-flows`, `public-navigation`). Não regenere os snapshots de `tests/Browser/visual.spec.js`, que estão desatualizados.
