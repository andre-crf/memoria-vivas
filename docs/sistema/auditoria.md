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
- `AuditSnapshot`: captura apenas campos explicitamente permitidos e normaliza
  enums, datas e estruturas aninhadas.
- `AuditDiff`: calcula diferenças no nível dos campos e descarta valores iguais.
- `AuditEventCollector`: acumula e consolida as mudanças por entidade.
- `AuditTransaction`: mantém alteração de negócio e eventos na mesma transação.
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

## Padrão dos serviços de aplicação

Toda alteração de dados auditáveis deve ser coordenada por um serviço de
aplicação e executada por `AuditTransaction`. O fluxo obrigatório é:

1. Autorizar e validar o comando antes ou no início do serviço.
2. Criar o `AuditContext` na fronteira da aplicação.
3. Entrar em `AuditTransaction::run()`.
4. Capturar o snapshot anterior usando uma lista positiva de campos.
5. Executar todas as alterações da entidade e seus relacionamentos.
6. Capturar o snapshot final.
7. Entregar os snapshots ao `AuditEventCollector`.
8. Deixar a transação persistir os eventos consolidados e realizar o commit.

Se a alteração de negócio ou a auditoria falhar, ambas são revertidas. Se os
snapshots forem equivalentes, o collector não produz evento.

```php
return $auditTransaction->run(
    $context,
    function (AuditEventCollector $audit) use ($categoria, $data) {
        $before = AuditSnapshot::fromModel($categoria, ['titulo', 'descricao']);

        $categoria->update($data);

        $after = AuditSnapshot::fromModel(
            $categoria->refresh(),
            ['titulo', 'descricao'],
        );

        $audit->capture(
            AuditAction::Updated,
            AuditEntity::Categoria,
            $categoria->id,
            $before,
            $after,
            $categoria->titulo,
        );

        return $categoria;
    },
);
```

Controllers não devem conter `DB::transaction` nem montar payloads de
auditoria. Eles criam comandos/contextos, chamam o serviço e tratam a resposta.
Somente o serviço orquestrador abre a `AuditTransaction`; serviços auxiliares
recebem o mesmo `AuditEventCollector`. Abrir transações auditadas independentes
dentro da mesma operação impediria a consolidação global dos eventos.

### Consolidação

O collector mantém no máximo um evento por entidade em cada execução de
`AuditTransaction`:

- o primeiro valor anterior de cada campo é preservado;
- o último valor novo é preservado;
- campos alterados em etapas diferentes são combinados;
- uma mudança revertida dentro da operação desaparece do evento;
- o rótulo mais recente da entidade é usado;
- ações diferentes para a mesma entidade provocam exceção.

Um serviço que cria e depois ajusta uma entidade deve capturar somente o estado
final como `created`. Um serviço que atualiza e depois exclui deve escolher a
ação final `deleted` e fornecer o snapshot anterior à operação. Não deve chamar
o collector com duas ações para a mesma entidade.

Entidades independentes alteradas pelo mesmo comando geram eventos separados,
todos com o mesmo `correlation_id`.

## Uso das ações

| Ação | Uso | Valores armazenados |
|---|---|---|
| `created` | Entidade criada | Somente `new_values` |
| `updated` | Campos ou relacionamentos alterados | Somente diferenças anteriores e novas |
| `deleted` | Exclusão regular ou lógica | Snapshot em `old_values` |
| `restored` | Restauração de exclusão lógica | Diferenças, incluindo `deleted_at` |
| `force_deleted` | Exclusão física | Snapshot completo permitido em `old_values` |
| `password_changed` | Troca de senha confirmada | Nenhum valor de senha |
| `activated` | Conta ou recurso ativado | Diferença do campo de situação |
| `deactivated` | Conta ou recurso inativado | Diferença do campo de situação |
| `uploaded` | Arquivo criado no armazenamento | Metadados permitidos em `new_values` |
| `replaced` | Arquivo substituído | Diferenças dos metadados permitidos |

`updated` é a ação padrão para uma submissão que modifica vários campos ou
relacionamentos da mesma entidade. Não devem ser criados eventos adicionais
para cada campo. `changed_fields` é incluído automaticamente nos metadados.

Exclusão e `force_deleted` exigem o snapshot antes da remoção. Restauração exige
snapshots anterior e posterior. Operações de alteração sem diferenças não
geram eventos, inclusive para `activated`, `deactivated`, `restored` e
`replaced`.

## Operações em massa

Operações auditáveis não podem usar diretamente `query()->update()`,
`query()->delete()`, `upsert()` ou SQL bruto, pois não oferecem snapshots
individuais nem eventos dos models.

O serviço de lote deve:

1. Selecionar os registros por ID em ordem estável.
2. Carregar e processar cada entidade como instância.
3. Gerar um evento por entidade afetada.
4. Compartilhar o mesmo `correlation_id` em todo o lote.
5. Registrar no metadata o identificador e a posição do lote quando útil.

Lotes pequenos podem usar uma única `AuditTransaction`. Lotes grandes devem ser
divididos em chunks, cada um com sua própria transação e o mesmo
`correlation_id`. Nesse caso, o serviço deve ser idempotente e registrar fora
da auditoria histórica o progresso operacional necessário para retomada. Não
se promete atomicidade entre chunks; a atomicidade é garantida dentro de cada
chunk.

Alterações por cascade do banco não geram eventos individuais. O evento da ação
principal deve informar em `metadata` as consequências conhecidas, como IDs ou
quantidades de vínculos removidos.

## Imutabilidade

A aplicação não oferece APIs para atualizar ou excluir eventos. A proteção do
model impede mutações acidentais pelo Eloquent. Acesso direto ao banco deve ser
restrito operacionalmente às migrations e ao usuário da aplicação, pois
nenhuma proteção em PHP impede comandos SQL executados fora do sistema.
