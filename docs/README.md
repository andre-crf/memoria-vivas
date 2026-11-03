# Memórias Vivas — Ambiente de Desenvolvimento

Este documento explica como preparar e executar o projeto **Memórias Vivas** em ambiente de desenvolvimento usando Docker.

## Stack

O ambiente atual utiliza:

- Laravel 13
- PHP 8.4 + PHP-FPM
- Nginx
- MySQL 8.4
- Livewire 4
- Alpine.js
- Tailwind CSS 4
- Vite
- Node.js 24
- Docker Compose

## Pré-requisitos

### Linux

Instale:

- Git
- Docker
- Docker Compose

Confirme a instalação:

```bash
git --version
docker --version
docker compose version
```

Também confirme que o Docker está em execução:

```bash
docker info
```

### Windows

Recomenda-se utilizar:

- Git
- Docker Desktop
- WSL2

Para evitar diferenças de permissões e melhorar o desempenho dos volumes, prefira clonar e executar o projeto **dentro do WSL2**, por exemplo:

```text
~/projetos/memoria-vivas
```

em vez de manter o projeto em uma pasta do Windows montada em `/mnt/c/...`.

Os comandos apresentados neste documento podem ser executados normalmente dentro do terminal do WSL2.

---

## 1. Clonar o repositório

Clone o projeto:

```bash
git clone https://github.com/andre-crf/memoria-vivas.git
```

Entre na pasta:

```bash
cd memoria-vivas
```

---

## 2. Criar o arquivo `.env`

O arquivo `.env` contém configurações locais e não deve ser versionado.

Crie-o a partir do exemplo:

```bash
cp .env.example .env
```

### Linux e WSL2: configurar UID e GID

Os containers PHP e Node utilizam o UID e o GID do desenvolvedor para evitar que arquivos sejam criados como `root` no computador.

Descubra seus valores:

```bash
id -u
id -g
```

Normalmente o resultado será:

```text
1000
1000
```

No `.env`, configure:

```env
HOST_UID=1000
HOST_GID=1000
```

Use os valores retornados pela sua máquina caso sejam diferentes.

### Windows sem WSL2

O Windows não possui os comandos `id -u` e `id -g`.

Se estiver usando Docker Desktop diretamente pelo PowerShell, mantenha os valores padrão:

```env
HOST_UID=1000
HOST_GID=1000
```

Como o Docker Desktop virtualiza o ambiente Linux dos containers, esses valores geralmente são suficientes. Para este projeto, porém, **WSL2 é o ambiente recomendado no Windows**.

---

## 3. Conferir a configuração do banco

O projeto utiliza MySQL dentro do Docker.

No `.env`, a configuração de desenvolvimento deve utilizar o nome do serviço Docker como host:

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=memorias_vivas
DB_USERNAME=laravel
DB_PASSWORD=laravel
```

Não utilize `localhost` ou `127.0.0.1` em `DB_HOST`, pois o Laravel e o MySQL estão em containers diferentes.

Também utilizamos atualmente:

```env
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=360
```

---

## 4. Construir as imagens Docker

Na primeira execução, construa as imagens do projeto:

```bash
docker compose build
```

Esse comando lê os Dockerfiles do projeto e prepara as imagens necessárias.

---

## 5. Subir os containers

Inicie o ambiente:

```bash
docker compose up -d
```

O parâmetro `-d` executa os containers em segundo plano.

Confira se os serviços estão em execução:

```bash
docker compose ps
```

Os principais serviços são:

```text
app      PHP-FPM / Laravel
nginx    servidor web
mysql    banco de dados
node     Node.js / Vite
```

O MySQL deve aparecer como `healthy`.

---

## 6. Instalar as dependências PHP

A pasta `vendor/` não é versionada no Git.

Depois do primeiro clone, execute:

```bash
docker compose exec app composer install
```

---

## 7. Gerar a chave do Laravel

Gere a `APP_KEY` local:

```bash
docker compose exec app php artisan key:generate
```

A chave será gravada no `.env`.

---

## 8. Instalar as dependências do frontend

A pasta `node_modules/` também não é versionada.

Execute:

```bash
docker compose exec node npm install
```

Isso instalará as dependências definidas em `package.json` e `package-lock.json`, incluindo Vite e Tailwind CSS.

---

## 9. Executar o Vite

Para desenvolvimento, execute em um terminal separado:

```bash
docker compose exec node npm run dev
```

Mantenha esse processo em execução enquanto estiver desenvolvendo o frontend.

O Vite utiliza:

```text
http://localhost:5173
```

A aplicação Laravel utiliza:

```text
http://localhost:8080
```

Acesse a aplicação pelo navegador em:

```text
http://localhost:8080
```

---

# Fluxo diário

Depois que o projeto já estiver configurado, normalmente basta:

```bash
docker compose up -d
```

Em outro terminal:

```bash
docker compose exec node npm run dev
```

Ao terminar o trabalho:

```bash
docker compose stop
```

---

# Comandos úteis

## Docker

Ver os containers do projeto:

```bash
docker compose ps
```

Subir os containers:

```bash
docker compose up -d
```

Parar os containers:

```bash
docker compose stop
```

Remover os containers e a rede do projeto:

```bash
docker compose down
```

Ver logs:

```bash
docker compose logs -f
```

Ver logs de um serviço específico:

```bash
docker compose logs -f app
docker compose logs -f nginx
docker compose logs -f mysql
docker compose logs -f node
```

Entrar no container PHP:

```bash
docker compose exec app bash
```

---

## Laravel / Artisan

Os comandos Artisan devem ser executados dentro do container `app`:

```bash
docker compose exec app php artisan <comando>
```

Exemplos:

```bash
docker compose exec app php artisan about
docker compose exec app php artisan route:list
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan test
```

---

## Filas e worker

A fila usa o driver `database` (tabelas `jobs` e `failed_jobs`), sem Redis. O serviço `worker` usa a mesma imagem PHP do `app`, com as mesmas extensões de imagem, o mesmo `.env`, o mesmo banco e o mesmo `storage/`. Ele atende a fila `imagens` primeiro e depois a `default`.

A geração das versões otimizadas (miniatura, média e grande) pode rodar na própria requisição ou na fila:

```env
ACERVO_DERIVADOS_ASSINCRONO=false   # padrão: as versões são geradas no upload, como antes
ACERVO_DERIVADOS_ASSINCRONO=true    # o upload grava o original e o job GerarVersoesOtimizadas gera as versões
```

- **Depois de mudar o valor:** rode `php artisan config:clear`, se a configuração estiver em cache, e reinicie o worker.
- **Modo assíncrono:**
  - exige o worker em execução;
  - enquanto o job não roda, a fotografia fica com as versões `pendente` e não aparece no catálogo público;
  - o "Regenerar versões" do painel também passa pelo job.
- **Modo síncrono:** as versões são geradas na requisição e o worker não é necessário.
- **Comando `acervo:regenerar-versoes`:** roda na hora, nos dois modos.

O estado das versões fica no original (`arquivos.derivacoes_status`): `pendente`, `processando`, `concluido`, `falhou` ou `nao_aplicavel`, este último para PDFs. Originais anteriores ao controle ficam sem estado até a próxima geração.

Nos testes, o `phpunit.xml` força `QUEUE_CONNECTION=sync` e o modo síncrono.

Iniciar, ver o estado e acompanhar os logs:

```bash
docker compose up -d worker
docker compose ps worker
docker compose logs -f worker
```

O worker mantém o código e o `.env` carregados em memória. Depois de mudar código, configuração ou `.env`, reinicie-o:

```bash
docker compose exec app php artisan queue:restart   # termina o job atual e reinicia
docker compose restart worker                       # alternativa pelo Docker
```

O `queue:restart` encerra o processo depois do job em andamento, e o `restart: unless-stopped` sobe o worker de novo. Depois de alterar o `Dockerfile`, reconstrua a imagem com `docker compose up -d --build worker`.

Ver jobs pendentes e falhos:

```bash
docker compose exec app php artisan queue:monitor database:imagens,database:default
docker compose exec app php artisan queue:failed
docker compose exec app php artisan queue:retry <id>   # ou: queue:retry all
docker compose exec app php artisan queue:forget <id>
docker compose exec app php artisan queue:flush        # apaga todos os jobs falhos
```

Diagnóstico, quando o worker não processa:

```bash
docker compose logs --tail=50 worker
docker compose exec worker php -m | grep -E "pcntl|imagick|gd|exif"
docker compose exec worker php artisan config:show queue.connections.database
docker compose exec worker php artisan migrate:status
```

- Sem `pcntl`, o worker não interrompe jobs que passam do `--timeout`.
- `migrate:status` deve mostrar a migration `create_jobs_table` como executada.
- `QUEUE_CONNECTION` precisa ser `database`. Com `sync`, os jobs rodariam na própria requisição.

### Ativar o modo assíncrono

A ordem importa: o modo assíncrono só deve ser ligado com o worker já funcionando. Caso contrário, as fotografias enviadas ficam pendentes.

1. **Código e banco:** publique o código e rode `php artisan migrate`. A migration `add_derivacoes_status_to_arquivos_table` precisa estar aplicada.
2. **Infraestrutura:** reconstrua a imagem (`docker compose build app worker`), que inclui o `pcntl`, e suba o worker com `docker compose up -d worker`.
   - Fora do Docker, rode o mesmo comando do `compose.yaml` (`php artisan queue:work database --queue=imagens,default ...`) num supervisor (systemd ou supervisord), com reinício automático.
3. **Verificações:**
   - `docker compose exec worker php -m | grep -E "pcntl|imagick|gd"`;
   - `php artisan queue:monitor database:imagens,database:default`;
   - os logs do worker mostrando `Processing jobs from the [imagens,default] queues`.
4. **Ativação:** coloque `ACERVO_DERIVADOS_ASSINCRONO=true` no `.env`, rode `php artisan config:clear` (ou `config:cache` em produção) e `php artisan queue:restart`.
5. **Conferência:** envie uma fotografia de teste. A tela mostra "Pendente" e, depois de atualizar a página, "Concluído".

**Armazenamento privado:**
- O worker lê os originais em `storage/app/private/acervo/originais` e grava as versões em `storage/app/private/acervo/derivados`.
- Ele precisa rodar com o mesmo usuário do PHP-FPM (no compose, `HOST_UID:HOST_GID`) e enxergar o mesmo `storage/`: o mesmo volume ou disco, nunca uma cópia.
- Também precisa do mesmo `.env`, para usar o mesmo banco e a mesma configuração.

### Voltar ao modo síncrono

1. Coloque `ACERVO_DERIVADOS_ASSINCRONO=false` e rode `php artisan config:clear` (ou `config:cache`). Os novos uploads voltam a gerar as versões na própria requisição.
2. Deixe o worker ligado até a fila esvaziar (`queue:monitor`). Se ele estiver parado, rode `php artisan acervo:regenerar-versoes --ausentes`, que processa na hora o que ficou pendente.
3. **Não desfaça a migration de estado.** As colunas `derivacoes_status` e `derivacoes_atualizadas_em` também são usadas no modo síncrono.

### Pendências, falhas e recuperação

**Onde ver:**
- **Listagem de fotografias:** no lugar da miniatura aparece "Gerando" ou "Falhou".
- **Detalhe da fotografia:** o selo ao lado de "Versões disponíveis" mostra Pendente, Em processamento, Concluído, Falhou ou Sem progresso. Não há atualização automática; atualize a página.
- **Consulta direta:**

  ```bash
  docker compose exec app php artisan tinker --execute="App\Models\Arquivo::where('versao_arquivo', 'original')->whereIn('derivacoes_status', ['pendente', 'processando', 'falhou'])->get(['item_acervo_id', 'derivacoes_status', 'derivacoes_atualizadas_em'])->each(fn (\$a) => print(\$a->item_acervo_id.' '.\$a->derivacoes_status->value.' '.\$a->derivacoes_atualizadas_em.PHP_EOL));"
  ```

**Como recuperar:**
- **Pelo painel:** "Regenerar versões", no menu do cartão Arquivos. Fica bloqueado enquanto a geração está em andamento e volta a funcionar depois de 5 minutos sem progresso.
- **Pelo comando:** `php artisan acervo:regenerar-versoes --ausentes` (ou com os IDs) processa na hora todas as fotografias que não estão `concluido`.
- **Jobs que esgotaram as tentativas:**
  - aparecem em `php artisan queue:failed`, com o estado da foto em `falhou`;
  - depois de corrigir a causa (veja `docker compose logs worker` e `storage/logs`), use `php artisan queue:retry <id>`, a regeneração pelo painel ou o comando;
  - `queue:forget <id>` só remove o registro da falha, sem mudar a fotografia.

**Situações conhecidas:**
- **Worker parado:**
  - os jobs se acumulam na tabela `jobs` e as fotografias ficam pendentes, fora do catálogo;
  - ao religar o worker, tudo é processado;
  - depois de 5 minutos a tela mostra "Sem progresso".
- **Worker encerrado no meio de um job** (falta de memória, por exemplo):
  - o job volta para a fila depois de `DB_QUEUE_RETRY_AFTER` (360 s) e retoma o registro em `processando`;
  - ao esgotar as tentativas, o estado vira `falhou`;
  - os arquivos gravados pela tentativa interrompida ficam órfãos em `acervo/derivados`.
- **Job apagado da fila** (`queue:clear`, por exemplo): a fotografia fica "Sem progresso" e é recuperada pelo painel ou pelo comando. Não existe estado `processando` sem saída: depois de 5 minutos, os dois caminhos voltam a aceitar a fotografia.

### Valores do worker

| Opção | Valor | Motivo |
| --- | --- | --- |
| `--timeout` | 300 s | Uma imagem grande foi processada em 3 a 4,5 s, mas TIFFs muito grandes passam do limite de área do Imagick e usam cache em disco, o que os torna bem mais lentos. Acima desse tempo, o job é interrompido. |
| `DB_QUEUE_RETRY_AFTER` | 360 s | Tempo até um job reservado voltar para a fila. Precisa ser **maior** que o `--timeout`; caso contrário, um job ainda em execução seria entregue a outra execução ao mesmo tempo. |
| `--tries` / `--backoff` | 3 / 60 s | Padrão do worker. Cada job pode definir os próprios valores. |
| `--memory` | 192 MB | Fica abaixo do `memory_limit` de 256M, para o worker reiniciar sozinho antes de estourar. A memória usada pelo Imagick fica fora dessa conta e é controlada pelos limites de recurso do Imagick em `app/Support/OptimizedImageVersions.php`. |
| `--max-jobs` / `--max-time` | 100 / 3600 s | Renovam o processo periodicamente. |
| `stop_grace_period` | 330 s | Na parada, o Docker espera o job em andamento terminar. Sem job em andamento, a parada é imediata. |

O job `GerarVersoesOtimizadas` define os próprios valores: 3 tentativas, intervalos de 30 s e 120 s, e tempo limite de 300 s (`GerarVersoesOtimizadas::TIMEOUT`). Esse tempo limite também é o prazo depois do qual uma geração é considerada sem progresso.

Ao mudar o `--timeout` no `compose.yaml`, ajuste também o `DB_QUEUE_RETRY_AFTER`, o `stop_grace_period` e o `TIMEOUT` do job.

---

## Composer

Execute o Composer dentro do container PHP:

```bash
docker compose exec app composer <comando>
```

Exemplos:

```bash
docker compose exec app composer install
docker compose exec app composer update
docker compose exec app composer require pacote/nome
```

---

## Node / npm

Execute npm dentro do container Node:

```bash
docker compose exec node npm <comando>
```

Exemplos:

```bash
docker compose exec node npm install
docker compose exec node npm run dev
docker compose exec node npm run build
```

---

# Banco de dados

O MySQL roda no serviço:

```text
mysql
```

Dentro da rede Docker, o Laravel acessa o banco por:

```text
mysql:3306
```

Os dados são armazenados em um volume Docker para que não sejam perdidos ao simplesmente parar ou recriar o container.

> **Atenção:** evite executar `docker compose down -v` sem saber exatamente o que está fazendo. A opção `-v` remove os volumes do projeto e pode apagar o banco de dados local.

---

# Conflito de portas

O projeto utiliza atualmente:

```text
8080  Nginx / aplicação
5173  Vite
3306  MySQL
```

Se uma dessas portas já estiver em uso, o Docker exibirá um erro parecido com:

```text
port is already allocated
```

Descubra quais containers estão utilizando portas:

```bash
docker ps --format "table {{.Names}}\t{{.Ports}}"
```

Para parar um container específico:

```bash
docker stop NOME_DO_CONTAINER
```

Para iniciá-lo novamente:

```bash
docker start NOME_DO_CONTAINER
```

---

# Problemas de permissão no Linux

Se o Laravel não conseguir escrever em `storage/` ou `bootstrap/cache/`, primeiro confira o UID e o GID configurados no `.env`:

```bash
id -u
id -g
```

Depois confira o usuário utilizado pelo container:

```bash
docker compose exec app id
```

Os valores devem corresponder ao `HOST_UID` e `HOST_GID` do `.env`.

Evite utilizar:

```bash
chmod -R 777
```

como solução para problemas de permissão.

---

# Primeira instalação — resumo

Para uma máquina nova, o fluxo completo é:

```bash
git clone <URL_DO_REPOSITORIO>
cd memoria-vivas

cp .env.example .env

# Linux / WSL2:
id -u
id -g

# Ajustar HOST_UID e HOST_GID no .env, se necessário.

docker compose build
docker compose up -d

docker compose exec app composer install
docker compose exec app php artisan key:generate

docker compose exec node npm install
docker compose exec node npm run dev
```

Depois acesse:

```text
http://localhost:8080
```

## Observação sobre migrations

As migrations específicas do sistema Memórias Vivas ainda estão em definição. Quando o banco da aplicação estiver modelado, este documento deverá ser atualizado com os comandos necessários para criação e preparação do banco local.
