# TBDev — runbook do laboratório e release

## Escopo e estado atual

Este runbook descreve somente a execução do **laboratório descartável** do TBDev modernizado. Ele não autoriza deploy no VPS de produção, não aponta para o MariaDB existente, não reutiliza volumes de Nextcloud/Capital/Databasus e não altera templates ou vhosts do HestiaCP.

A branch de trabalho deve ser validada primeiro com lint, testes e `docker compose config`. Uma imagem que apenas compila não é considerada release: a compatibilidade de schema, autenticação legada, announce/scrape e armazenamento de `.torrent` precisa ser demonstrada no laboratório.

## Preparação segura

Execute os comandos abaixo em um diretório isolado no host de laboratório. Não coloque o conteúdo real de `.env.lab` no Git, em tickets ou no chat.

```sh
cd /opt/tbdev-lab
umask 077
cp .env.lab.example .env.lab
chmod 600 .env.lab
```

Preencha os valores do `.env.lab` fora do repositório. Gere valores aleatórios para a chave da aplicação e para as credenciais do banco; nunca reutilize credenciais do VPS produtivo. Confirme que `TBDEV_BASE_URL` aponta para `http://127.0.0.1:18180`, que `TBDEV_TORRENT_DIR` aponta para `/var/lib/tbdev/torrents` e que `TBDEV_CACHE_DIR` aponta para `/var/lib/tbdev/runtime/cache`.

O cadastro exige transporte de e-mail quando há usuários prévios. Como o laboratório não possui SMTP, você pode habilitar temporariamente a confirmação automática somente nele, adicionando `TBDEV_SIGNUP_CONFIRM_AUTO=1` ao `.env.lab` e recriando apenas o serviço web. Essa variável não deve ser usada em produção; o padrão é `0`.

## Validação antes de iniciar

```sh
cd /opt/tbdev-lab
php -l tests/security_auth_test.php
php tests/security_auth_test.php
docker compose --env-file .env.lab -f compose.lab.yml config --quiet
```

O comando `config --quiet` precisa terminar sem erro. Se a versão do Docker Compose não suportar `--quiet`, use `docker compose --env-file .env.lab -f compose.lab.yml config` e revise a saída para garantir que nenhum segredo seja impresso ou persistido em log.

## Build e inicialização do laboratório

A imagem recebe um tag explícito. O build deve usar exatamente o checkout revisado; ele não baixa código mutável de SVN ou de outro repositório.

```sh
docker build \
  --file Dockerfile \
  --tag tbdev-web:0.2.0-auth-security \
  .

docker compose --env-file .env.lab -f compose.lab.yml up -d --build
docker compose --env-file .env.lab -f compose.lab.yml ps
```

O serviço web deve estar limitado a `127.0.0.1:18180`. O banco do laboratório não deve publicar a porta 3306. Os limites esperados são 384 MiB e 0,50 CPU para o web, e 512 MiB e 0,50 CPU para o MariaDB. Os logs do Docker devem permanecer rotacionados conforme o Compose.

## Inicialização do schema e migração

Inicialize uma base nova usando o schema do projeto somente no banco de laboratório. Depois aplique a migração aditiva de autenticação. O comando abaixo usa a variável de ambiente que já está dentro do container e não exige colar a senha no terminal.

```sh
docker compose --env-file .env.lab -f compose.lab.yml exec -T tbdev-db \
  sh -c 'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"' \
  < SQL/tb.sql

docker compose --env-file .env.lab -f compose.lab.yml exec -T tbdev-db \
  sh -c 'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"' \
  < database/migrations/001_add_password_hash.sql
```

A migração é aditiva: mantém `passhash` e `editsecret` legados, cria `password_hash` e `recovery_expires`, e não deve ser aplicada contra a base produtiva sem backup verificado, dry-run contra cópia e autorização explícita.

Para disponibilizar categorias legais de teste no laboratório, aplique o fixture idempotente depois do schema:

```sh
docker compose --env-file .env.lab -f compose.lab.yml exec -T tbdev-db \
  sh -c 'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"' \
  < docs/tbdev-lab-seed.sql
```

O arquivo usa nomes `cat_*.gif` compatíveis com o gerenciador administrativo e não é executado automaticamente em produção.

## Smoke tests mínimos

```sh
curl --fail --silent --show-error \
  -H 'Cache-Control: no-cache, no-store, max-age=0' \
  'http://127.0.0.1:18180/?_cb='"$(date +%s%N)" \
  -o /tmp/tbdev-lab-home.html

grep -q 'TBDev' /tmp/tbdev-lab-home.html

docker compose --env-file .env.lab -f compose.lab.yml ps

docker compose --env-file .env.lab -f compose.lab.yml logs --tail=100 tbdev-web
```

Depois valide manualmente, ainda somente no laboratório, o cadastro com senha moderna, login, recuperação por link expirável, edição de perfil com senha atual, criação de usuário pelo admin, upload de um `.torrent` autorizado e download com passkey. Verifique também que um POST sem CSRF retorna erro, que um destino externo não é aceito em `returnto` e que o diretório do volume contém os arquivos runtime sem alterações no source da imagem.

O contrato do tracker deve ser testado com fixtures controladas para `started`, `completed`, `stopped`, peer list e scrape. Não execute payloads de exploração, scans agressivos ou testes contra o VPS de produção.

## Parada e limpeza do laboratório

```sh
docker compose --env-file .env.lab -f compose.lab.yml down
```

Para descartar completamente o laboratório, incluindo o banco e os arquivos de teste, confirme que não há dados necessários e execute:

```sh
docker compose --env-file .env.lab -f compose.lab.yml down -v
```

Nunca use `down -v` em uma pilha que contenha volumes produtivos.

## Release, tag e rollback

Antes de uma release, registre o SHA Git, a tag da imagem, a configuração resolvida do Compose sem segredos e o digest efetivo da imagem base e das imagens de dependência. A imagem publicada para Hestia deve usar tag imutável ou digest; `latest` não é permitido.

O rollback de laboratório para uma imagem anterior segue este padrão:

```sh
# exemplo: alterar somente o tag de imagem no compose de release
sed -i 's/tbdev-web:[^[:space:]]*/tbdev-web:0.1.0-compat/' compose.release.yml
docker compose --env-file .env.production -f compose.release.yml up -d tbdev-web
docker compose --env-file .env.production -f compose.release.yml ps
```

Em produção, o rollback só pode ocorrer após confirmar saúde do container, `nginx -t` e que o proxy Hestia continua apontando para a porta loopback correta. Templates persistentes do Hestia devem ser usados; vhosts gerados não devem ser editados diretamente. O procedimento de rollback precisa manter a base e os arquivos `.torrent` consistentes com a versão escolhida.

## Checklist de bloqueio para produção

Não fazer cutover enquanto qualquer item abaixo estiver pendente: build reproduzível com digest registrado; `docker compose config` sem segredos; schema e migrações testados em cópia; backup e restauração de banco e arquivos verificados; smoke tests web e announce/scrape aprovados; cache e uploads fora da imagem; instalador legado ausente do checkout e da imagem; prepared statements nos módulos críticos; CSRF, rate limiting, sessões e redirects revisados; logs e limites monitorados; e plano de rollback ensaiado.

**Estado nesta etapa:** somente código, documentação e testes foram alterados no sandbox e publicados na branch `work/modernize-tbdev`. Nenhum container TBDev foi criado no VPS compartilhado, nenhum domínio TBDev foi configurado e nenhuma base ou arquivo produtivo foi tocado.
