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
QUEUE_CONNECTION=sync
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
