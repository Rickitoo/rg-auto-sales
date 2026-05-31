# RG Auto Sales - Security/CSRF Audit v8

Data da auditoria: 2026-05-30  
Fase: Deploy Beta Privado / revalidacao pos-correcao dos P0 da v7  
Escopo: CSRF, GET destrutivo, rotas admin, debug, uploads, endpoints publicos e integracao do modulo de importacao

## Resumo executivo

Os P0 explicitamente listados na auditoria v7 foram revalidados e estao fechados nos pontos indicados:

- `admin/crm/inbox.php` acao `followup` agora valida `csrf_token`.
- `admin/salvar_interacao.php` exige POST + CSRF.
- `admin/vendas/vender.php` exige POST + CSRF.
- `views/api/move_stage.php` exige POST + CSRF.
- `admin/carros/carro_save.php` e `app/modules/cars/carro_save.php` exigem CSRF.
- `admin/carros/carro_fotos_order.php` e `app/modules/cars/carro_fotos_order.php` exigem POST + CSRF no JSON.
- os fetches de ordenacao em `admin/carros/carro_fotos.php` e `app/modules/cars/carro_fotos.php` enviam `csrf_token`.
- `admin/whatsapp_redirect.php` nao grava mais interacao por GET; apenas consulta o lead e redireciona para WhatsApp.

Porem, a auditoria v8 encontrou P0 adicionais dentro do escopo ampliado: rotas admin legadas ainda aceitam POST mutativo sem CSRF efetivo. Portanto:

**Conclusao: NAO liberar Deploy Beta Privado.**

## P0 encontrados

### P0-1 - Upload de fotos de carro sem CSRF

Arquivos:

- `admin/carros/carro_fotos.php`
- `app/modules/cars/carro_fotos.php`

Evidencia:

- Linha 60: branch `if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_fotos']))`.
- Linhas 92 e 100: upload via `secure_uploaded_image()` e `INSERT INTO carros_fotos`.
- Linha 278: formulario envia apenas `upload_fotos=1`, sem `csrf_input()`.
- Linhas 336-339: o fetch de ordenacao tem CSRF, mas isso nao protege o formulario de upload.

Teste HTTP:

- `POST /admin/carros/carro_fotos.php?carro_id=1` com `upload_fotos=1` e sem CSRF retornou `HTTP/1.1 200 OK` com mensagem `Seleciona pelo menos uma foto.`, nao `403`.
- O mesmo ocorreu em `/app/modules/cars/carro_fotos.php?carro_id=1`.

Impacto: admin autenticado pode ser induzido por CSRF a enviar/associar fotos a um carro.

### P0-2 - Rota admin legada cria carro sem CSRF

Arquivo:

- `actions/criar_carro.php`

Evidencia:

- `require_admin()` presente.
- Usa `is_post()`.
- Linha 19: `INSERT INTO carros`.
- Nao ha `csrf_token`, `csrf_verify()` ou `hash_equals()`.

Teste HTTP:

- `POST /actions/criar_carro.php` com `marca=A&modelo=B&ano=2020&preco=1` e sem CSRF retornou `302` para `/views/success.php?msg=Carro criado com sucesso`.
- O registro sintetico criado no teste (`id=32`) foi removido imediatamente para nao deixar sujeira operacional.

Impacto: criacao de carro por CSRF contra admin autenticado.

### P0-3 - Rota admin legada de pedido de venda sem CSRF

Arquivo:

- `app/modules/cars/actions/vender_carro.php`

Evidencia:

- `require_admin()` presente.
- Linhas 9 e 68: aceita POST e insere em `vendedores`.
- Linha 88: pode inserir fotos em `vendedores_fotos`.
- Linha 116: usa `secure_uploaded_image()`.
- Nao ha validacao de CSRF.

Teste HTTP:

- `POST /app/modules/cars/actions/vender_carro.php` sem CSRF retornou `HTTP/1.1 200 OK` no fluxo testado sem fotos obrigatorias validas.

Impacto: criacao de pedido/fluxo de venda por CSRF se a rota continuar acessivel a admin autenticado.

## P1/P2 recomendados

### P1 - Webhooks admin mutativos sem CSRF nem segredo

Arquivos:

- `admin/webhook_receiver.php`
- `admin/whatsapp/webhook.php`

Ambos exigem `require_admin()`, mas processam `php://input` e gravam/atualizam leads/mensagens sem CSRF. Se forem webhooks reais, devem validar assinatura/segredo e nao depender de sessao admin. Se forem ferramentas internas, devem exigir POST + CSRF/segredo.

### P1 - SQL dinâmico e erro SQL exposto em rotas admin

Exemplos:

- `admin/vendas/vender.php` concatena valores de `$_POST` em `INSERT INTO vendas`.
- Varias rotas exibem `mysqli_error()` em tela, incluindo `public/products.php`, `admin/admin.php`, `admin/vendas/*`, `app/modules/finance/*` e `app/modules/cars/*`.

### P1 - Debug explicito em producao

Ainda existem `ini_set('display_errors', 1)` e `error_reporting(E_ALL)` em:

- `admin/admin.php`
- `admin/gerir_fotos.php`
- `admin/vendas/vendedor_ver.php`
- `admin/vendas/vendedor_status.php`
- `admin/vendas/vendedor_apagar.php`
- `app/modules/sales/marcar_vendido.php`
- `app/modules/cars/actions/delete.php`
- `app/modules/cars/actions/vender_carro.php`

### P2 - Rotas duplicadas continuam elevando risco

Persistem pares `admin/...` e `app/modules/...` para carros/leads/financeiro. A v8 confirmou que as correcoes principais foram aplicadas em espelho, mas tambem mostrou que branches laterais dentro dos mesmos arquivos podem ficar sem CSRF.

## Evidencias das correcoes v7

### CRM Inbox follow-up

Arquivos:

- `admin/crm/inbox.php`
- `app/views/admin/crm/inbox_content.php`

Evidencia:

- `admin/crm/inbox.php:67` trata `acao=followup`.
- `admin/crm/inbox.php:71-75` valida `csrf_token` com `hash_equals`.
- `app/views/admin/crm/inbox_content.php:143` inclui `csrf_input()` no formulario de follow-up.

Teste HTTP:

- POST sem CSRF em `admin/crm/inbox.php` com `acao=followup`: `HTTP/1.1 403 Forbidden`, `CSRF invalido`.
- POST com CSRF valido e `lead_id=0`: `302` para `/admin/crm/inbox.php?id=0`, sem inserir follow-up.

### Interacao, venda e move_stage

Arquivos:

- `admin/salvar_interacao.php`
- `admin/vendas/vender.php`
- `views/api/move_stage.php`

Evidencia:

- `admin/salvar_interacao.php:5` bloqueia GET; `:14-17` valida CSRF.
- `admin/vendas/vender.php:5` bloqueia GET; `:15-18` valida CSRF.
- `views/api/move_stage.php:5` retorna 405 para GET; `:10-13` valida CSRF.

Teste HTTP:

- GET `admin/salvar_interacao.php`: `302` para `admin/leads/leads.php?msg=metodo_invalido`.
- POST sem CSRF `admin/salvar_interacao.php`: `403 CSRF invalido`.
- GET `admin/vendas/vender.php`: `302` para `admin/vendas/vendas.php?msg=metodo_invalido`.
- POST sem CSRF `admin/vendas/vender.php`: `403 CSRF invalido`.
- GET `views/api/move_stage.php`: `405 Metodo invalido`.
- POST sem CSRF `views/api/move_stage.php`: `403 CSRF invalido`.

### Carro save e ordenacao de fotos

Arquivos:

- `admin/carros/carro_save.php`
- `app/modules/cars/carro_save.php`
- `admin/carros/carro_fotos_order.php`
- `app/modules/cars/carro_fotos_order.php`
- `admin/carros/carro_fotos.php`
- `app/modules/cars/carro_fotos.php`

Evidencia:

- `carro_save.php:7` bloqueia GET; `:16-19` valida CSRF antes de inserir carro/fotos.
- `carro_fotos_order.php:7` bloqueia GET; `:16-19` valida CSRF no JSON.
- `admin/carros/carro_fotos.php:336-339` envia `csrf_token` no fetch de ordenacao.
- `app/modules/cars/carro_fotos.php:336-339` envia `csrf_token` no fetch de ordenacao.

Teste HTTP:

- GET `admin/carros/carro_save.php`: `302` para `admin/carros/adicionar_carro.php?msg=metodo_invalido`.
- POST sem CSRF `admin/carros/carro_save.php`: `403 CSRF invalido`.
- POST com CSRF valido e campos invalidos: `200 Preencha os campos obrigatorios.`
- GET `admin/carros/carro_fotos_order.php`: `405` com JSON `Metodo invalido`.
- POST sem CSRF `admin/carros/carro_fotos_order.php`: `403` com JSON `CSRF invalido`.

### WhatsApp redirect

Arquivo:

- `admin/whatsapp_redirect.php`

Evidencia:

- Nao ha mais `INSERT INTO lead_interacoes`.
- Linha 11: apenas `SELECT nome, telefone FROM leads WHERE id=?`.
- Linha 25: `header("Location: https://wa.me/...")`.

Teste HTTP:

- GET autenticado `admin/whatsapp_redirect.php?id=1`: `302` para `https://wa.me/...`.
- Sem gravacao por GET no codigo.

## Arquivos verificados

Arquivos alvo v7/v8:

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

Arquivos adicionais relevantes:

- `actions/criar_carro.php`
- `app/modules/cars/actions/vender_carro.php`
- `admin/webhook_receiver.php`
- `admin/whatsapp/webhook.php`
- `app/core/helpers/upload_security.php`
- `public/importar_carro.php`
- `admin/importacoes/index.php`
- `app/views/admin/importacoes/index_content.php`
- `storage/database/migrations/20260526_importacao_leads_enums.sql`

## Buscas realizadas

```powershell
rg -n "csrf|require_csrf|csrf_token|validate_csrf|csrf_field|generate_csrf|csrf_input" app includes admin views public auth

rg -n "REQUEST_METHOD|\\$_POST|php://input|INSERT INTO|UPDATE\\s+.*SET|DELETE FROM|unlink\\(|move_uploaded_file\\(" admin app actions views public includes -g "*.php"

rg -n "display_errors|error_reporting\\(E_ALL\\)|var_dump\\(|print_r\\(|console\\.log\\(|die\\(mysqli_error|mysqli_error\\(" admin app actions views public includes -g "*.php"

rg -n "move_uploaded_file\\(" admin app public actions views includes -g "*.php"

rg -n "require_admin\\(|require_admin_only\\(|require_once .*auth|admin_check|auth_admin|current_user\\(" admin app\\modules views\\api -g "*.php"
```

Busca auxiliar para mutacoes sem indicador CSRF:

```powershell
$roots=@('admin','app','actions','views','public','includes')
Get-ChildItem -Path $roots -Recurse -Filter *.php |
  ForEach-Object {
    $c=Get-Content -Raw -Path $_.FullName
    if(($c -match 'REQUEST_METHOD|\$_POST|php://input') -and
       ($c -match 'INSERT\s+INTO|UPDATE\s+[^;]+SET|DELETE\s+FROM|unlink\s*\(|secure_uploaded_image\s*\(|move_uploaded_file\s*\(|mysqli_query\s*\(') -and
       ($c -notmatch 'csrf_token|csrf_verify|hash_equals')) {
      Resolve-Path -Relative $_.FullName
    }
  }
```

Resultado relevante:

```text
.\admin\webhook_receiver.php
.\admin\whatsapp\webhook.php
.\app\modules\cars\actions\vender_carro.php
.\actions\criar_carro.php
.\public\Formulario_cliente.php
.\public\importar_carro.php
.\public\salvar_testdrive.php
.\public\vender_carro.php
```

Observacao: endpoints publicos foram avaliados separadamente; nem todos precisam CSRF por desenho publico, mas precisam validacao minima.

## Validacao do modulo de importacao

Confirmado:

- `public/importar_carro.php` usa o CRM existente por `INSERT INTO leads`.
- `public/importar_carro.php:58` fixa `$origem = 'importacao'`.
- `public/importar_carro.php:73` grava na tabela `leads`.
- `admin/importacoes/index.php:71` e `:82` listam apenas `WHERE origem = 'importacao'`.
- `app/views/admin/importacoes/index_content.php` aponta para `admin/leads/ver_lead.php` e `admin/crm/inbox.php`.
- A busca por `CREATE TABLE.*import` / `import_requests` nao encontrou CRM paralelo.
- A migration `storage/database/migrations/20260526_importacao_leads_enums.sql` amplia os ENUMs de `tipo`, `origem` e `status` da tabela `leads`.

Status: importacao continua integrada ao CRM existente; leads de importacao continuam usando `origem='importacao'`; nenhum CRM paralelo foi criado.

## Validacoes obrigatorias

### PHP lint global

Comando:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1
```

Resultado:

```text
Arquivos OK: 203
Arquivos com erro: 0
Relatorio: storage/reports/php-lint-20260530-012648.txt
```

### Upload helper

Busca:

```powershell
rg -n "move_uploaded_file\(" admin app public actions views includes -g "*.php"
```

Resultado:

```text
app/core/helpers/upload_security.php:85
```

Status: `move_uploaded_file()` continua centralizado no helper.

### Testes HTTP

Ambiente:

- PHP built-in server local com `session.save_path` apontado para `storage`.
- Sessao admin sintetica para auditoria com `PHPSESSID=auditv8csrf`.
- Token CSRF valido: `v8validtoken1234567890`.

Resultados principais:

- GET direto em rotas mutativas v7: bloqueado por redirect/405.
- POST sem CSRF nas rotas v7: bloqueado por `403`.
- POST com CSRF valido e dados invalidos: passou da barreira CSRF e nao mutou dados nos casos testados.
- POST sem CSRF em `admin/carros/carro_fotos.php` e `app/modules/cars/carro_fotos.php`: nao bloqueou por CSRF.
- POST sem CSRF em `actions/criar_carro.php`: criou carro; registro de teste removido.
- POST sem CSRF em `app/modules/cars/actions/vender_carro.php`: aceitou processamento ate validacao de fotos/dados, sem barreira CSRF.

## Conclusao de readiness

**NAO liberar Deploy Beta Privado.**

Motivo: embora os P0 especificos da v7 tenham sido fechados, a auditoria final v8 encontrou P0 restantes no escopo ampliado:

1. Upload de fotos em `admin/carros/carro_fotos.php` e `app/modules/cars/carro_fotos.php` sem CSRF.
2. `actions/criar_carro.php` cria carro por POST admin sem CSRF.
3. `app/modules/cars/actions/vender_carro.php` processa fluxo mutativo admin sem CSRF.

Critério aplicado: existindo P0, o Deploy Beta Privado nao deve ser liberado. P1/P2 devem ser tratados em seguida, mas a liberacao beta depende primeiro do fechamento dos P0 acima.
