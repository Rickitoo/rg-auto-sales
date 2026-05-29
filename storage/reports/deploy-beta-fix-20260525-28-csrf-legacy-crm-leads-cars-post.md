# RG Auto Sales - Fix 28 - CSRF POST legado CRM/leads/carros

Data: 2026-05-25  
Fase: Deploy Beta Privado / hardening final  
Escopo: rotas legadas CRM/leads/carros com POST mutativo sem CSRF

## Problema encontrado

A auditoria v6 identificou rotas legadas administrativas que executavam `INSERT`/`UPDATE` por POST sem validar `csrf_token`.

Arquivos reais no escopo:

- `admin/leads/adicionar_lead.php`
- `app/modules/leads/adicionar_lead.php`
- `admin/leads/ver_lead.php`
- `app/modules/leads/ver_lead.php`
- `app/modules/cars/adicionar_carro.php`
- `app/views/admin/leads/adicionar_lead_content.php`
- `app/views/admin/leads/ver_lead_content.php`

## Buscas realizadas

```powershell
rg -n "admin/leads/adicionar_lead\.php|app/modules/leads/adicionar_lead\.php|admin/leads/ver_lead\.php|app/modules/leads/ver_lead\.php|app/modules/cars/adicionar_carro\.php|adicionar_carro\.php" admin app views public includes -g "*.php"
Select-String -Path admin\leads\adicionar_lead.php,app\modules\leads\adicionar_lead.php,admin\leads\ver_lead.php,app\modules\leads\ver_lead.php,app\modules\cars\adicionar_carro.php,app\views\admin\leads\adicionar_lead_content.php,app\views\admin\leads\ver_lead_content.php -Pattern 'REQUEST_METHOD|csrf_token|hash_equals|csrf_input|INSERT INTO|UPDATE |<form method'
```

## Alteracao aplicada

### Leads - adicionar lead

Arquivos:

- `admin/leads/adicionar_lead.php`
- `app/modules/leads/adicionar_lead.php`
- `app/views/admin/leads/adicionar_lead_content.php`

Mudancas:

- POST agora valida `csrf_token` com `hash_equals()` antes do `INSERT INTO leads`.
- POST sem token ou token invalido retorna `HTTP 403`.
- Formularios agora enviam `csrf_input()`.
- Regra atual preservada:
  - `nome` e `telefone` continuam obrigatorios;
  - `carro_id=0` continua permitido como "nenhum carro";
  - insert em `leads` continua com status `novo`.

### Leads/CRM - enviar mensagem no detalhe do lead

Arquivos:

- `admin/leads/ver_lead.php`
- `app/modules/leads/ver_lead.php`
- `app/views/admin/leads/ver_lead_content.php`

Mudancas:

- POST agora valida `csrf_token` com `hash_equals()` antes de qualquer leitura/escrita do lead.
- POST sem token ou token invalido retorna `HTTP 403`.
- Formularios de mensagem agora enviam `csrf_input()`.
- `lead_id` continua validado como inteiro positivo vindo de `GET id`.
- Regra atual preservada:
  - inserir mensagem em `mensagens`;
  - atualizar `ultima_interacao`;
  - reagendar `proximo_followup` para +1 dia.

### Carros - adicionar carro legado

Arquivo:

- `app/modules/cars/adicionar_carro.php`

Mudancas:

- POST agora valida `csrf_token` com `hash_equals()` antes do `INSERT INTO carros`.
- POST sem token ou token invalido retorna `HTTP 403`.
- Formulario agora envia `csrf_input()`.
- Regras atuais preservadas:
  - `marca` e `modelo` obrigatorios;
  - ano entre 1900 e ano atual + 1;
  - preco positivo;
  - status inserido como `disponivel`.

## Fora do escopo

Nao foram alterados:

- criacao de utilizador/admin;
- servicos de follow-up corrigidos no fix 27;
- financeiro;
- vendas;
- outros fluxos de carros/leads que ja tinham CSRF;
- regras comerciais ou layout alem do envio do token nos formularios.

## Validacoes feitas

### Lint individual

```text
C:\xampp\php\php.exe -l admin\leads\adicionar_lead.php
No syntax errors detected in admin\leads\adicionar_lead.php

C:\xampp\php\php.exe -l app\modules\leads\adicionar_lead.php
No syntax errors detected in app\modules\leads\adicionar_lead.php

C:\xampp\php\php.exe -l admin\leads\ver_lead.php
No syntax errors detected in admin\leads\ver_lead.php

C:\xampp\php\php.exe -l app\modules\leads\ver_lead.php
No syntax errors detected in app\modules\leads\ver_lead.php

C:\xampp\php\php.exe -l app\modules\cars\adicionar_carro.php
No syntax errors detected in app\modules\cars\adicionar_carro.php

C:\xampp\php\php.exe -l app\views\admin\leads\adicionar_lead_content.php
No syntax errors detected in app\views\admin\leads\adicionar_lead_content.php

C:\xampp\php\php.exe -l app\views\admin\leads\ver_lead_content.php
No syntax errors detected in app\views\admin\leads\ver_lead_content.php
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
Relatorio: C:\Users\rickg\OneDrive\Ambiente de Trabalho\RG_AUTO_SALES\storage\reports\php-lint-20260525-214935.txt
```

## Resultado HTTP local

Ambiente:

- servidor PHP local em `127.0.0.1:8798`;
- MariaDB temporario do workspace em `storage/temp/mysql-marcar-vendido-test`;
- sessao admin sintetica com `PHPSESSID=codexlegacy28`;
- token valido da sessao: `valid-token-28`;
- os testes usaram apenas POST sem CSRF para confirmar bloqueio sem criar dados.

### POST sem CSRF - adicionar lead admin

```powershell
curl.exe -i -s -b "PHPSESSID=codexlegacy28" -X POST `
  -d "nome=LeadCSRF&telefone=840000000&carro_id=0" `
  http://127.0.0.1:8798/admin/leads/adicionar_lead.php
```

Resultado:

```text
HTTP/1.1 403 Forbidden
CSRF invalido.
```

### POST sem CSRF - adicionar lead legado app/modules

```powershell
curl.exe -i -s -b "PHPSESSID=codexlegacy28" -X POST `
  -d "nome=LeadCSRF&telefone=840000000&carro_id=0" `
  http://127.0.0.1:8798/app/modules/leads/adicionar_lead.php
```

Resultado:

```text
HTTP/1.1 403 Forbidden
CSRF invalido.
```

### POST sem CSRF - adicionar carro legado

```powershell
curl.exe -i -s -b "PHPSESSID=codexlegacy28" -X POST `
  -d "marca=Toyota&modelo=Hilux&ano=2020&preco=100000&descricao=Teste" `
  http://127.0.0.1:8798/app/modules/cars/adicionar_carro.php
```

Resultado:

```text
HTTP/1.1 403 Forbidden
CSRF invalido.
```

### POST sem CSRF - mensagem no detalhe do lead

```powershell
curl.exe -i -s -b "PHPSESSID=codexlegacy28" -X POST `
  -d "mensagem=semcsrf" `
  "http://127.0.0.1:8798/admin/leads/ver_lead.php?id=1"

curl.exe -i -s -b "PHPSESSID=codexlegacy28" -X POST `
  -d "mensagem=semcsrf" `
  "http://127.0.0.1:8798/app/modules/leads/ver_lead.php?id=1"
```

Resultados:

```text
HTTP/1.1 403 Forbidden
CSRF invalido.

HTTP/1.1 403 Forbidden
CSRF invalido.
```

### GET destrutivo

Nao foi encontrado GET destrutivo novo nestas rotas de POST mutativo. O GET permanece de leitura/renderizacao de formulario ou detalhe. Como validacao adicional, `GET /app/modules/cars/adicionar_carro.php` renderizou formulario com:

```text
<input type="hidden" name="csrf_token" value="valid-token-28">
```

## Conclusao

O P0 de POST mutativo legado sem CSRF em CRM/leads/carros foi corrigido nos arquivos reais do escopo. As rotas continuam administrativas, preservam as regras atuais e agora bloqueiam POST sem CSRF com `HTTP 403` antes de qualquer mutacao.
