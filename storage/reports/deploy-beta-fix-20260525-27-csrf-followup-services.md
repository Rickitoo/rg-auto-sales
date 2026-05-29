# RG Auto Sales - Fix 27 - CSRF servicos de follow-up

Data: 2026-05-25  
Fase: Deploy Beta Privado / hardening final  
Escopo: P0 de servicos de follow-up que alteravam CRM por GET/page load

## Problema encontrado

A auditoria v6 identificou servicos administrativos de follow-up que executavam mutacoes ao carregar a rota por GET:

- `admin/services/cron_followup.php`
  - inseria mensagens em `mensagens`;
  - reagendava `leads.proximo_followup`.

- `admin/services/auto_followup.php`
  - gerava link/manual output;
  - atualizava `leads.last_contact`.

Ambos ja exigiam admin autenticado via `require_admin()`, mas nao bloqueavam GET/page load nem validavam CSRF antes das mutacoes.

## Arquivos reais identificados

Buscas realizadas:

```powershell
rg -n "cron_followup\.php|auto_followup\.php|followup\.php|follow_up\.php|runFollowUps|autoFollowUp" admin app views public includes -g "*.php"
rg -n "INSERT INTO mensagens|UPDATE leads|last_contact|proximo_followup|lead_interacoes|lead_followups|follow-up|followup" admin\services views\crm\services -g "*.php"
```

Arquivos corrigidos neste fix:

- `admin/services/cron_followup.php`
- `admin/services/auto_followup.php`

Arquivos verificados e nao alterados:

- `admin/services/follow_up.php` ja estava protegido por POST + CSRF desde o fix 23.
- `admin/services/followup.php` define a funcao `runFollowUps()` e nao executa mutacao no carregamento direto.
- `views/crm/services/auto_followup.php` define funcao chamada por engine, sem execucao direta de page-load no arquivo.

Nao foram encontradas chamadas ativas para `cron_followup.php` ou `auto_followup.php` em views/paginas que exigissem conversao para formulario/fetch.

## Alteracao aplicada

### `admin/services/cron_followup.php`

- Bloqueia qualquer metodo diferente de POST com `HTTP 405`.
- Valida `csrf_token` com `hash_equals()` antes de consultar/mutar.
- POST sem CSRF valido retorna `HTTP 403`.
- Valida `lead_id` como inteiro positivo antes de `INSERT`/`UPDATE`.
- Preserva a regra atual:
  - selecionar leads com `proximo_followup <= NOW()`;
  - escolher mensagem por status;
  - inserir em `mensagens`;
  - reagendar follow-up para +1 dia.

### `admin/services/auto_followup.php`

- Bloqueia qualquer metodo diferente de POST com `HTTP 405`.
- Valida `csrf_token` com `hash_equals()` antes de consultar/mutar.
- POST sem CSRF valido retorna `HTTP 403`.
- Valida `lead_id` como inteiro positivo antes do update.
- Preserva a regra atual:
  - selecionar leads nao fechados sem contacto recente;
  - gerar link WhatsApp;
  - atualizar `last_contact`.

## Fora do escopo

Nao foram alterados:

- criacao de utilizador/admin;
- CRM inbox/follow-up generico;
- leads genericos;
- carros;
- financeiro;
- regras comerciais de follow-up alem de exigir POST + CSRF.

## Validacoes feitas

### Lint individual

```text
C:\xampp\php\php.exe -l admin\services\cron_followup.php
No syntax errors detected in admin\services\cron_followup.php

C:\xampp\php\php.exe -l admin\services\auto_followup.php
No syntax errors detected in admin\services\auto_followup.php
```

### Lint global

Comando:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1
```

Resultado:

```text
PHP CLI: C:\xampp\php\php.exe
Arquivos OK: 199
Arquivos com erro: 0
Relatorio: C:\Users\rickg\OneDrive\Ambiente de Trabalho\RG_AUTO_SALES\storage\reports\php-lint-20260525-214219.txt
```

## Resultado HTTP local

Ambiente:

- servidor PHP local em `127.0.0.1:8797`;
- MariaDB temporario do workspace em `storage/temp/mysql-marcar-vendido-test`;
- sessao admin sintetica com `PHPSESSID=codexfollow27`;
- token valido da sessao: `valid-token-27`;
- nao foi executado POST com CSRF valido para evitar mutacao real de follow-up.

### GET direto

```powershell
curl.exe -i -s -b "PHPSESSID=codexfollow27" http://127.0.0.1:8797/admin/services/cron_followup.php
curl.exe -i -s -b "PHPSESSID=codexfollow27" http://127.0.0.1:8797/admin/services/auto_followup.php
```

Resultados:

```text
HTTP/1.1 405 Method Not Allowed
Metodo invalido.

HTTP/1.1 405 Method Not Allowed
Metodo invalido.
```

Conclusao: GET nao executa follow-up nem altera CRM.

### POST sem CSRF

```powershell
curl.exe -i -s -b "PHPSESSID=codexfollow27" -X POST http://127.0.0.1:8797/admin/services/cron_followup.php
curl.exe -i -s -b "PHPSESSID=codexfollow27" -X POST http://127.0.0.1:8797/admin/services/auto_followup.php
```

Resultados:

```text
HTTP/1.1 403 Forbidden
CSRF invalido.

HTTP/1.1 403 Forbidden
CSRF invalido.
```

Conclusao: POST sem CSRF valido e bloqueado antes de qualquer mutacao.

## Conclusao

O P0 de servicos de follow-up que alteravam CRM por GET/page load foi corrigido nos arquivos reais identificados:

- `admin/services/cron_followup.php`;
- `admin/services/auto_followup.php`.

As rotas agora exigem admin autenticado, POST e CSRF valido antes de qualquer mutacao.
