# RG Auto Sales - Fix P0 Security/CSRF v7

Data: 2026-05-26  
Escopo: correcao apenas dos P0 criticos apontados na auditoria Security/CSRF v7

## Arquivos alterados

- `admin/crm/inbox.php`
- `app/views/admin/crm/inbox_content.php`
- `admin/salvar_interacao.php`
- `admin/vendas/vender.php`
- `views/api/move_stage.php`
- `admin/carros/carro_save.php`
- `app/modules/cars/carro_save.php`
- `admin/carros/carro_fotos_order.php`
- `app/modules/cars/carro_fotos_order.php`
- `admin/carros/carro_fotos.php`
- `app/modules/cars/carro_fotos.php`
- `admin/whatsapp_redirect.php`

## P0 corrigidos

### CRM Inbox follow-up sem CSRF

- `admin/crm/inbox.php`: branch `acao=followup` agora valida `csrf_token` com `hash_equals()` antes de inserir em `lead_followups` ou atualizar `leads`.
- `app/views/admin/crm/inbox_content.php`: formulario "Adicionar follow-up" agora envia `csrf_input()`.

### Rotas admin legadas mutativas sem CSRF

- `admin/salvar_interacao.php`: agora exige POST e CSRF antes de inserir em `lead_interacoes`.
- `admin/vendas/vender.php`: agora exige POST e CSRF antes de inserir em `vendas`.
- `views/api/move_stage.php`: agora exige POST e CSRF antes de atualizar `leads.stage`.

### Carros/fotos sem CSRF

- `admin/carros/carro_save.php`: agora exige POST e CSRF antes de criar carro/fotos.
- `app/modules/cars/carro_save.php`: agora exige POST e CSRF antes de criar carro/fotos.
- `admin/carros/carro_fotos_order.php`: agora exige POST e token JSON `csrf_token` antes de atualizar ordem/capa.
- `app/modules/cars/carro_fotos_order.php`: agora exige POST e token JSON `csrf_token` antes de atualizar ordem/capa.
- `admin/carros/carro_fotos.php` e `app/modules/cars/carro_fotos.php`: `fetch('carro_fotos_order.php')` agora envia `csrf_token`.

### GET mutativo em WhatsApp redirect

- `admin/whatsapp_redirect.php`: GET deixou de gravar em `lead_interacoes`.
- A rota agora apenas busca o lead e redireciona para WhatsApp.
- A consulta foi mantida de leitura e feita com prepared statement.

## Buscas realizadas

```powershell
rg -n -F "salvar_interacao.php" admin app public views includes
rg -n -F "vendas/vender.php" admin app public views includes
rg -n -F "move_stage.php" admin app public views includes
rg -n -F "carro_save.php" admin app public views includes
rg -n -F "carro_fotos_order.php" admin app public views includes
rg -n -F "whatsapp_redirect.php" admin app public views includes
rg -n "csrf_token|csrf_input|hash_equals|REQUEST_METHOD|INSERT INTO lead_followups|INSERT INTO lead_interacoes|INSERT INTO vendas|UPDATE leads|INSERT INTO carros|UPDATE carros_fotos" [arquivos-alvo]
```

Resultados relevantes:

- Nao foram encontradas chamadas antigas para `salvar_interacao.php`, `vendas/vender.php`, `move_stage.php` ou `whatsapp_redirect.php` no escopo varrido.
- `carro_fotos_order.php` e chamado por `admin/carros/carro_fotos.php` e `app/modules/cars/carro_fotos.php`; ambos agora enviam `csrf_token`.
- `carro_save.php` aparece apenas nos proprios arquivos alvo; nao havia formulario ativo encontrado apontando diretamente para essa rota.

## Testes executados

Ambiente:

- PHP: `C:\xampp\php\php.exe`
- servidor local temporario em `127.0.0.1`
- sessao admin sintetica com `PHPSESSID=codexp0v7direct`
- token valido: `valid-token-v7`

### Lint individual

Comando:

```powershell
C:\xampp\php\php.exe -l [arquivo]
```

Resultado: 0 erros em todos os arquivos alterados.

Arquivos testados:

- `admin/crm/inbox.php`
- `app/views/admin/crm/inbox_content.php`
- `admin/salvar_interacao.php`
- `admin/vendas/vender.php`
- `views/api/move_stage.php`
- `admin/carros/carro_save.php`
- `app/modules/cars/carro_save.php`
- `admin/carros/carro_fotos_order.php`
- `app/modules/cars/carro_fotos_order.php`
- `admin/carros/carro_fotos.php`
- `app/modules/cars/carro_fotos.php`
- `admin/whatsapp_redirect.php`

### Lint global

Comando:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1
```

Resultado:

```text
PHP CLI: C:\xampp\php\php.exe
Arquivos OK: 203
Arquivos com erro: 0
Relatorio: storage/reports/php-lint-20260526-065542.txt
```

### GET direto nas rotas mutativas

Resultados:

```text
GET /admin/salvar_interacao.php => HTTP/1.1 302 Found
GET /admin/vendas/vender.php => HTTP/1.1 302 Found
GET /views/api/move_stage.php => HTTP/1.1 405 Method Not Allowed
GET /admin/carros/carro_save.php => HTTP/1.1 302 Found
GET /app/modules/cars/carro_save.php => HTTP/1.1 302 Found
GET /admin/carros/carro_fotos_order.php => HTTP/1.1 405 Method Not Allowed
GET /app/modules/cars/carro_fotos_order.php => bloqueia/redireciona sem executar mutacao
```

`admin/whatsapp_redirect.php?id=1`:

```text
HTTP/1.1 302 Found
lead_interacoes antes: 0
lead_interacoes depois: 0
```

Conclusao: GET nao executou gravacao de interacao.

### POST sem CSRF

Resultados:

```text
POST /admin/crm/inbox.php acao=followup => HTTP/1.1 403 Forbidden
POST /admin/salvar_interacao.php => HTTP/1.1 403 Forbidden
POST /admin/vendas/vender.php => HTTP/1.1 403 Forbidden
POST /views/api/move_stage.php => HTTP/1.1 403 Forbidden
POST /admin/carros/carro_save.php => HTTP/1.1 403 Forbidden
POST /app/modules/cars/carro_save.php => HTTP/1.1 403 Forbidden
POST /admin/carros/carro_fotos_order.php => HTTP/1.1 403 Forbidden
POST /app/modules/cars/carro_fotos_order.php => HTTP/1.1 403 Forbidden
```

Conclusao: POST sem token valido foi bloqueado antes de mutacao.

### Fluxo com CSRF valido

Resultados:

```text
GET /admin/crm/inbox.php?id=1 contem name="csrf_token" value="valid-token-v7"
POST /admin/crm/inbox.php acao=followup com CSRF => HTTP/1.1 302 Found
lead_followups teste criado: 1
lead_followups teste removido apos validacao: 0
POST /admin/salvar_interacao.php com CSRF e lead_id=0 => HTTP/1.1 200 OK sem inserir
POST /views/api/move_stage.php com CSRF e lead_id=0 => HTTP/1.1 200 OK sem afetar lead real
POST /admin/carros/carro_fotos_order.php com CSRF e dados invalidos => HTTP 200 JSON "Dados invalidos"
POST /app/modules/cars/carro_fotos_order.php com CSRF e dados invalidos => HTTP 200 JSON "Dados invalidos"
```

Conclusao: os handlers passam da validacao CSRF quando o token e valido e continuam a aplicar validacoes de negocio/dados.

## Riscos restantes

- `admin/vendas/vender.php` continua com SQL interpolado e mensagens diretas; nao foi refatorado por estar fora do pedido e para preservar regras existentes.
- `views/api/move_stage.php` continua com SQL interpolado em `stage`; nesta execucao foi corrigido o P0 de CSRF, sem refatorar regras/contrato legado.
- Rotas duplicadas `admin/...` e `app/modules/...` continuam aumentando superficie de manutencao.
- P1/P2 da auditoria v7, como debug explicito e exposicao de erros SQL em rotas legadas, continuam recomendados para hardening posterior.

## Conclusao

Os P0 especificos da auditoria v7 foram corrigidos no escopo solicitado.  

**Pode rodar nova auditoria Security/CSRF v8.**
