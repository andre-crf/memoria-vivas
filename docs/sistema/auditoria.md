# Sistema de auditoria

## Fundação

A auditoria registra eventos históricos imutáveis em `audit_events`. Esta
tabela complementa os campos de autoria corrente existentes em `item_acervos`;
ela não os substitui.

O `AuditRecorder` é o único componente da aplicação autorizado a inserir
eventos. O model `AuditEvent` e seu query builder rejeitam inserções,
alterações e exclusões diretas.

A integração é feita gradualmente por serviços de aplicação, sempre dentro da
mesma transação da alteração de negócio. A gestão administrativa de usuários,
o perfil pessoal e os itens do acervo já seguem esse padrão.

## Componentes

- `AuditContext`: DTO imutável com responsável, origem, `request_id` e
  `correlation_id`.
- `AuditContextFactory`: cria o contexto das requisições autenticadas e reutiliza
  os mesmos identificadores durante toda a requisição.
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

## Usuários e perfil

As mutações de usuários são executadas pelos serviços em
`App\Services\Usuarios`. Os controllers validam a entrada, obtêm o usuário
autenticado, criam o contexto da requisição e delegam a operação. Os serviços
refazem a autorização e carregam os registros com bloqueio dentro da transação,
evitando que uma decisão baseada em dados desatualizados quebre as proteções de
perfil, situação ou último administrador ativo.

O snapshot de usuário possui uma lista positiva e centralizada de campos:
`nome`, `email`, `role` e `status`. Senha, hash, token de lembrança e quaisquer
credenciais ficam fora do snapshot por construção, além da sanitização geral do
`AuditRecorder`.

| Operação | Ação de auditoria | Conteúdo |
|---|---|---|
| Cadastro administrativo | `created` | Estado inicial permitido, sempre com situação ativa |
| Alteração de nome, e-mail ou perfil | `updated` | Somente os campos efetivamente alterados |
| Alteração conjunta, incluindo situação | `updated` | Uma única diferença consolidada |
| Somente ativação | `activated` | Diferença de `status` |
| Somente inativação | `deactivated` | Diferença de `status` |
| Alteração do próprio nome/e-mail | `updated` | Somente diferenças de identidade |
| Alteração da própria senha | `password_changed` | Sem valores anteriores ou novos |

Uma submissão que mantém os mesmos valores não produz evento. Uma nova senha
igual à senha atual também é tratada como ausência de alteração. Falhas de
validação, autorização, proteção do último administrador ou persistência não
deixam evento nem mudança de negócio parcial.

O `actor` representa quem iniciou a operação e o `subject`, o usuário afetado.
Na alteração do próprio perfil ambos têm o mesmo ID. Nome e perfil do ator são
capturados no início da requisição para preservar o contexto histórico mesmo
quando a operação altera o próprio nome.

Os metadados distinguem a intenção da operação (`admin_user_create`,
`admin_user_update`, `self_profile_update` e `self_password_change`) e podem
informar os grupos modificados (`identity`, `authorization` e `status`). Eles
não devem duplicar dados pessoais já presentes nas diferenças nem carregar
segredos.

## Itens do acervo e relacionamentos

As mutações de itens do acervo são executadas pelos serviços em
`App\Services\Acervo`. O `FotografiaController` valida a entrada, garante que o
registro é uma fotografia, cria o contexto da requisição e delega a operação.
Os serviços refazem a autorização e carregam o item com bloqueio dentro da
transação.

O `ItemAcervoObserver` continua responsável apenas pela autoria resumida
(`created_by_user_id`, `updated_by_user_id` e `deleted_by_user_id`). Ele não
grava em `audit_events`; alterações feitas fora dos serviços atualizam a
autoria resumida, mas não geram histórico.

O `ItemAcervoAuditSnapshot` possui a lista positiva de campos do item e inclui
os relacionamentos auditados:

| Chave | Conteúdo |
|---|---|
| `autor` | `{id, label}` do autor ou `null` |
| `categorias` | Lista de `{id, label}` ordenada por ID |
| `assuntos` | Lista de `{id, label}` ordenada por ID |
| `palavras_chave` | Lista de `{id, label}` ordenada por ID |
| `pessoas` | Lista de `{id, label}` ordenada por ID |

O rótulo é gravado junto do ID para que o histórico continue legível depois
que o item ou a entidade relacionada forem excluídos. Campos de autoria
resumida e timestamps ficam fora do snapshot.

| Operação | Serviço | Ação | Conteúdo |
|---|---|---|---|
| Cadastro | `CriarItemAcervo` | `created` | Campos e vínculos iniciais |
| Edição de campos e/ou vínculos | `AtualizarItemAcervo` | `updated` | Uma única diferença consolidada |
| Exclusão lógica | `ExcluirItemAcervo` | `deleted` | Snapshot completo anterior |
| Restauração | `RestaurarItemAcervo` | `restored` | Diferença de `deleted_at` |
| Exclusão definitiva | `ExcluirItemAcervoDefinitivamente` | `force_deleted` | Snapshot completo, incluindo `deleted_at` |

Na edição, os relacionamentos são sincronizados pelo serviço auxiliar
`SincronizarRelacionamentosItemAcervo`, dentro da mesma transação. Uma chave de
relacionamento ausente preserva os vínculos atuais; uma lista vazia remove
todos. Alterações somente de vínculos também atualizam a autoria resumida do
item. Uma submissão sem diferenças efetivas não produz evento.

Os metadados informam a intenção (`acervo_item_create`, `acervo_item_update`,
`acervo_item_soft_delete`, `acervo_item_restore` e
`acervo_item_force_delete`). Na edição, `change_groups` indica os grupos
alterados (`descriptive`, `publication` e `relationships`) e
`relationship_changes` resume os IDs adicionados e removidos de cada
relacionamento N:N.

A exclusão definitiva captura o snapshot antes da remoção, pois o banco apaga
por cascade vínculos, arquivos, registros de download e participações em
coleções e conjuntos contextuais. Essas consequências são registradas em
`metadata.cascade` (`arquivo_ids`, `colecao_ids`, `conjunto_contextual_ids`,
`colecao_capa_ids` e `registro_downloads_count`). Os arquivos digitais são a
exceção: o original recebe também um evento `deleted`, descrito a seguir.

## Arquivos digitais

O arquivo original é a unidade principal do histórico. Seu snapshot usa uma
lista positiva: `item_acervo_id`, `nome_original`, `provider`,
`external_file_id`, `storage_path`, `mime_type`, `file_size`, `tipo_arquivo`,
`sha256`, `versao_arquivo`, `width` e `height`. Conteúdo binário, temporários,
headers, credenciais e configurações do provider não são capturados.

`CriarItemAcervo` e `AtualizarItemAcervo` recebem explicitamente o upload
opcional e coordenam item, vínculos, arquivo e eventos na mesma
`AuditTransaction`. A substituição é executada por
`SubstituirArquivoOriginal`, que bloqueia o item e o original e preserva o ID
do registro principal. O controller não abre transações nem manipula o
storage.

| Operação | Ação | Metadados principais |
|---|---|---|
| Upload | `uploaded` | `operation=arquivo_original_upload`, item e resumo das derivações |
| Substituição | `replaced` | `operation=arquivo_original_replace`, derivações removidas, geradas e falhas |
| Exclusão definitiva do item | `deleted` | `operation=arquivo_group_delete` e derivações removidas |

As versões `thumbnail`, `medium` e `large` são consequências técnicas: não
geram eventos próprios. Seus metadados permitidos aparecem em
`metadata.derivations.generated`; versões que falharam aparecem apenas pelo
nome em `failed_versions`. PDFs marcam a geração como não aplicável. Se uma
estrutura inconsistente possuir somente derivações, cada registro remanescente
recebe um evento `deleted` para que o histórico não seja perdido.

O provider do model é resolvido para um disco do Laravel pela configuração
`acervo.storage_disks`. Nesta etapa somente `local` oferece processamento. A
interface `ArquivoStorage` mantém os serviços independentes do disco e permite
adicionar providers externos sem alterar o formato dos eventos.

### Compensação do storage

Banco e armazenamento não compartilham uma transação distribuída. Por isso,
cada operação mantém um diário com os caminhos físicos envolvidos:

1. caminhos novos são registrados antes da escrita;
2. erro de negócio ou do `AuditRecorder` reverte o banco e remove os objetos
   novos;
3. o original e as derivações anteriores permanecem durante a transação;
4. após a confirmação, os caminhos substituídos ou excluídos são removidos.

A gravação do original é estrita e cancela toda a operação quando falha. A
geração das derivações é de melhor esforço: cada falha é enviada ao log, o
arquivo parcial é limpo e o original permanece confirmado. Falhas na limpeza
pós-confirmação também vão para o log com provider e caminhos, sem reverter o
banco ou os eventos já persistidos. Download e visualização não produzem
auditoria.

## Consulta administrativa

A consulta de auditoria é exclusiva para administradores ativos e permanece
estritamente somente leitura. A listagem permite combinar período,
responsável, ação, entidade e ID da entidade, sempre utilizando os aliases
estáveis persistidos. Os resultados são ordenados por `occurred_at` e `id`, do
mais recente para o mais antigo, e paginados em grupos de 25 eventos.

A página de detalhes utiliza exclusivamente os snapshots do evento. Ela não
consulta o estado atual da entidade para completar nomes ou valores, pois o
registro pode ter sido alterado ou excluído. `old_values` e `new_values` são
alinhados campo a campo, enquanto estruturas conhecidas e enums recebem labels
em português apenas na apresentação; o conteúdo persistido não é modificado.

Outros eventos com o mesmo `correlation_id` são apresentados como parte da
mesma operação. O histórico contextual da fotografia combina os eventos cujo
subject é o próprio `item_acervo` com os eventos de `arquivo` que registram o
item em `metadata.item_acervo.id` ou no snapshot permitido do arquivo. Essa
consulta contextual também é exclusiva para administradores e não substitui
os campos resumidos de autoria mantidos no item.

## Imutabilidade

A aplicação não oferece APIs para atualizar ou excluir eventos. A proteção do
model impede mutações acidentais pelo Eloquent. Acesso direto ao banco deve ser
restrito operacionalmente às migrations e ao usuário da aplicação, pois
nenhuma proteção em PHP impede comandos SQL executados fora do sistema.
