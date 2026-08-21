# Git Flow — Memórias Vivas

Este documento define o fluxo de branches e integração de código adotado no projeto **Memórias Vivas**.

O objetivo é manter o desenvolvimento organizado, evitar alterações diretas na branch de produção e facilitar o trabalho simultâneo da equipe.

## 1. Branches principais

O projeto utiliza duas branches permanentes:

### `main`

Representa a versão estável do projeto.

Regras:

- deve conter apenas código validado;
- não deve receber commits diretos;
- recebe alterações por Pull Request;
- releases e hotfixes são integrados nela.

### `develop`

Representa a versão mais recente em desenvolvimento.

É a branch base para novas funcionalidades e correções comuns.

Regras:

- novas funcionalidades partem de `develop`;
- Pull Requests de desenvolvimento normalmente têm `develop` como destino;
- não deve receber commits diretos.

```text
feature/* ──┐
feature/* ──┼──> develop ──> release/* ──> main
fix/*     ──┘
```

## 2. Atualizar as branches locais

Antes de iniciar uma tarefa:

```bash
git fetch origin
git switch develop
git pull origin develop
```

Se a `develop` ainda não existir localmente:

```bash
git switch -c develop origin/develop
```

## 3. Feature branches

Novas funcionalidades devem partir de `develop`.

Padrão:

```text
feature/nome-da-funcionalidade
```

Exemplos:

```text
feature/cadastro-fotografias
feature/autenticacao
feature/listagem-acervo
feature/gerenciamento-categorias
```

Criando:

```bash
git switch develop
git pull origin develop
git switch -c feature/cadastro-fotografias
```

Depois do desenvolvimento:

```bash
git add .
git commit -m "feat: adiciona cadastro de fotografias"
git push -u origin feature/cadastro-fotografias
```

Abra um Pull Request para `develop`.

## 4. Correções durante o desenvolvimento

Use:

```text
fix/nome-da-correcao
```

Exemplos:

```text
fix/validacao-upload
fix/filtro-categorias
fix/permissao-storage
```

Essas branches também partem de `develop` e retornam para `develop` por Pull Request.

## 5. Release branches

Quando a `develop` estiver pronta para gerar uma versão estável:

```text
release/x.y.z
```

Exemplo:

```text
release/1.0.0
```

Criação:

```bash
git switch develop
git pull origin develop
git switch -c release/1.0.0
git push -u origin release/1.0.0
```

Na release devem entrar apenas correções finais, documentação e preparação da versão.

Depois, a release deve ser integrada em:

```text
main
develop
```

Após o merge em `main`, pode ser criada uma tag:

```bash
git tag -a v1.0.0 -m "Release v1.0.0"
git push origin v1.0.0
```

## 6. Hotfix branches

Hotfixes são correções urgentes em uma versão já estável.

Diferente de `fix/*`, partem de `main`.

```text
hotfix/nome-da-correcao
```

Exemplo:

```text
hotfix/falha-login
```

Criação:

```bash
git switch main
git pull origin main
git switch -c hotfix/falha-login
```

O hotfix deve ser integrado tanto em `main` quanto em `develop`.

## 7. Padrão de nomes

| Tipo       | Uso                                        |
| ---------- | ------------------------------------------ |
| `feature/` | Nova funcionalidade                        |
| `fix/`     | Correção durante o desenvolvimento         |
| `release/` | Preparação de versão                       |
| `hotfix/`  | Correção urgente da versão estável         |
| `docs/`    | Documentação                               |
| `chore/`   | Configuração, infraestrutura ou manutenção |

Exemplos:

```text
feature/cadastro-fotografias
fix/validacao-data
docs/gitflow
chore/docker-setup
release/1.0.0
hotfix/falha-autenticacao
```

## 8. Padrão de commits

Utilizamos mensagens inspiradas em Conventional Commits:

```text
tipo: descrição curta
```

Principais tipos:

| Tipo       | Uso                                  |
| ---------- | ------------------------------------ |
| `feat`     | Nova funcionalidade                  |
| `fix`      | Correção                             |
| `docs`     | Documentação                         |
| `refactor` | Refatoração                          |
| `test`     | Testes                               |
| `chore`    | Configuração e manutenção            |
| `style`    | Formatação sem alterar comportamento |

Exemplos:

```text
feat: adiciona cadastro de fotografias
fix: corrige validação da data da fotografia
docs: adiciona instruções de instalação
chore: configura ambiente Docker
refactor: reorganiza componente de categorias
test: adiciona testes para criação de usuário
```

## 9. Pull Requests

Fluxos esperados:

```text
feature/* ──> develop
fix/*     ──> develop

release/* ──> main
release/* ──> develop

hotfix/*  ──> main
hotfix/*  ──> develop
```

Antes de abrir um Pull Request:

1. confirme que a aplicação funciona;
2. execute os testes;
3. confira os arquivos alterados;
4. atualize sua branch quando necessário;
5. descreva claramente a alteração.

Exemplo:

```bash
docker compose exec app php artisan test
git status
```

## 10. Evitar commits diretos em `main` e `develop`

O fluxo normal deve ser:

```text
develop
   │
   ▼
feature/minha-tarefa
   │
   ▼
commits
   │
   ▼
push
   │
   ▼
Pull Request
   │
   ▼
develop
```

## 11. Atualizando uma feature

Se outras alterações entrarem em `develop`:

```bash
git switch develop
git pull origin develop

git switch feature/minha-feature
git merge develop
```

Resolva eventuais conflitos e continue o desenvolvimento.

Enquanto não houver outra convenção definida, utilizamos `merge` em vez de `rebase` por ser mais simples para o fluxo da equipe.

## 12. Conflitos

Confira:

```bash
git status
```

Resolva os arquivos manualmente.

Depois:

```bash
git add arquivo-resolvido
git commit
```

Nunca escolha uma versão de um conflito sem entender o que será descartado.

## 13. Remover branches concluídas

Depois do merge:

```bash
git switch develop
git pull origin develop
git branch -d feature/nome-da-feature
```

Para apagar a remota:

```bash
git push origin --delete feature/nome-da-feature
```

## 14. Exemplo completo

```bash
git switch develop
git pull origin develop

git switch -c feature/cadastro-fotografias
```

Depois do desenvolvimento:

```bash
git status
git add .
git commit -m "feat: adiciona cadastro de fotografias"

git push -u origin feature/cadastro-fotografias
```

No GitHub:

```text
feature/cadastro-fotografias
            ↓
       Pull Request
            ↓
         develop
```

Após aprovação:

```bash
git switch develop
git pull origin develop

git branch -d feature/cadastro-fotografias
```

## Visão geral

```text
                         feature/*
                        /
                       /
main <── release/* <── develop
  ▲                    ▲
  │                    │
  └──── hotfix/* ──────┘
```

### Regra principal

- nova funcionalidade → parte de `develop`;
- correção durante desenvolvimento → parte de `develop`;
- preparação de versão → parte de `develop`;
- correção urgente da versão publicada → parte de `main`;
- `main` e `develop` não recebem desenvolvimento direto.
