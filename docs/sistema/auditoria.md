# Sistema de auditoria

## Fundação

A auditoria registra eventos históricos imutáveis em `audit_events`. Esta
tabela complementa os campos de autoria corrente existentes em `item_acervos`;
ela não os substitui.

O `AuditRecorder` é o único componente da aplicação autorizado a inserir
eventos. O model `AuditEvent` e seu query builder rejeitam inserções,
alterações e exclusões diretas.

Nesta primeira etapa nenhum CRUD existente produz eventos automaticamente. A
integração será feita gradualmente por serviços de aplicação, sempre dentro da
mesma transação da alteração de negócio.

## Componentes

- `AuditContext`: DTO imutável com responsável, origem, `request_id` e
  `correlation_id`.
- `AuditEventData`: dados normalizados do evento a ser persistido.
- `AuditRecorder`: sanitiza e persiste o evento de forma síncrona.
- `AuditSanitizer`: remove campos sensíveis recursivamente.
- `AuditAction`: vocabulário central de ações.
- `AuditEntity`: aliases estáveis das entidades.
- `AuditSource`: origens aceitas para o evento.

## Regras

1. Serviços de aplicação devem registrar o evento antes de confirmar a
   transação de negócio.
2. Controllers, observers e models não podem escrever diretamente em
   `audit_events`.
3. `old_values` e `new_values` devem conter apenas campos efetivamente
   alterados e permitidos para a entidade.
4. Senhas, tokens, cookies, segredos e credenciais nunca são persistidos.
5. Alterações de senha registram somente a ação, sem valores anteriores ou
   novos.
6. Operações relacionadas compartilham um `correlation_id`; eventos da mesma
   requisição compartilham um `request_id`.
7. Ações automáticas usam contexto sem usuário e origem `system`, `console` ou
   `job`.

## Imutabilidade

A aplicação não oferece APIs para atualizar ou excluir eventos. A proteção do
model impede mutações acidentais pelo Eloquent. Acesso direto ao banco deve ser
restrito operacionalmente às migrations e ao usuário da aplicação, pois
nenhuma proteção em PHP impede comandos SQL executados fora do sistema.
