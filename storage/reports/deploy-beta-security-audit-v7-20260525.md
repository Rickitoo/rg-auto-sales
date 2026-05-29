# RG Auto Sales - Security/CSRF Audit v7

Data: 2026-05-25  
Fase: Deploy Beta Privado / hardening final  
Escopo: revalidacao apos correcoes 26, 27 e 28, rechecagem de P0 antes do deploy beta privado

## Resumo executivo

As correcoes 26, 27 e 28 foram confirmadas parcialmente no codigo:

- `admin/criar_user.php` agora exige admin autenticado, POST com CSRF e whitelist de role.
- `admin/services/cron_followup.php` e `admin/services/auto_followup.php` agora bloqueiam GET e exigem POST + CSRF.
- rotas principais/legadas de leads e carros corrigidas no fix 28 passaram a validar CSRF.
- o modulo de importacao foi criado dentro do CRM existente, usando `leads` com `origem='importacao'` e `status='novo'`; nao ha CRM paralelo.

Porem, a auditoria v7 encontrou P0 criticos restantes fora dos fixes 26-28, incluindo follow-up no CRM Inbox sem CSRF, rotas admin legadas mutativas sem CSRF e uma rota GET que grava interacao. Portanto:

**Conclusao: NAO liberar Deploy Beta Privado.**

## P0 restantes

### P0-1 - CRM Inbox ainda insere follow-up sem CSRF

Arquivos:

- `admin/crm/inbox.php`
- `app/views/admin/crm/inbox_content.php`

Evidencia:

```text
admin/crm/inbox.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'followup') {
    INSERT INTO lead_followups (...)
    UPDATE leads SET atualizado_em=NOW()
}

app/views/admin/crm/inbox_content.php
<form class="crm-note-form" method="POST" action="...admin/crm/inbox.php">
    <input type="hidden" name="acao" value="followup">
```

A branch `acao=status` tem CSRF, mas a branch `acao=followup` nao valida `csrf_token`, e o formulario de nota nao inclui `csrf_input()`.

Impacto: admin autenticado pode ser induzido via CSRF a inserir notas/follow-ups no CRM.

### P0-2 - Rotas admin legadas mutativas sem CSRF

Arquivos:

- `admin/salvar_interacao.php`
- `admin/vendas/vender.php`
- `views/api/move_stage.php`

Evidencia:

```text
admin/salvar_interacao.php
lead_id/mensagem via $_POST
INSERT INTO lead_interacoes (...)
sem csrf_token/hash_equals

admin/vendas/vender.php
$_POST['preco_venda'], $_POST['preco_custo'], ...
INSERT INTO vendas (...)
sem bloqueio de metodo e sem CSRF

views/api/move_stage.php
$_POST['lead_id'], $_POST['stage']
UPDATE leads SET stage='$new_stage'
sem CSRF
```

Impacto: criacao/alteracao administrativa por CSRF em CRM/vendas/leads.

### P0-3 - Endpoints de carros/fotos sem CSRF em rotas legadas

Arquivos:

- `admin/carros/carro_save.php`
- `app/modules/cars/carro_save.php`
- `admin/carros/carro_fotos_order.php`
- `app/modules/cars/carro_fotos_order.php`

Evidencia:

```text
admin/carros/carro_save.php
$_POST marca/modelo/ano/preco
INSERT INTO carros
INSERT INTO carros_fotos
UPDATE carros SET imagem
sem CSRF

admin/carros/carro_fotos_order.php
php://input JSON
UPDATE carros_fotos SET ordem
UPDATE carros SET imagem
sem CSRF
```

Impacto: admin autenticado pode ser induzido a criar carro/alterar capa/ordem de fotos via POST/JSON malicioso.

### P0-4 - GET mutativo restante em WhatsApp redirect

Arquivo:

- `admin/whatsapp_redirect.php`

Evidencia:

```text
$id = (int)($_GET['id'] ?? 0);
INSERT INTO lead_interacoes (lead_id, tipo, mensagem)
header("Location: https://wa.me/...");
```

Impacto: simples GET autenticado altera historico do lead. Mesmo sendo uma interacao/log, e estado mutativo por GET e deve ser convertido para POST + CSRF ou registro idempotente seguro.

## P1/P2 recomendados

### P1 - Debug explicito em rotas web

Ainda existem `ini_set('display_errors', 1)` e/ou `error_reporting(E_ALL)` em:

- `admin/admin.php`
- `admin/gerir_fotos.php`
- `admin/vendas/vendedor_ver.php`
- `admin/vendas/vendedor_status.php`
- `admin/vendas/vendedor_apagar.php`
- `app/modules/sales/marcar_vendido.php`
- `app/modules/cars/actions/delete.php`
- `app/modules/cars/actions/vender_carro.php`

Recomendacao: remover debug explicito de producao e centralizar logging seguro.

### P1 - Erros SQL expostos em rotas publicas/admin

Foram encontrados usos de `mysqli_error()` em mensagens exibidas por rotas web, incluindo:

- `public/products.php`
- `app/modules/cars/adicionar_carro.php`
- `admin/vendas/nova_venda.php`
- `admin/vendas/venda_detalhe.php`
- `admin/vendas/vendedor_status.php`
- `app/modules/finance/custos.php`

Recomendacao: trocar por mensagem generica ao utilizador e log interno.

### P1 - Webhooks administrativos com desenho inconsistente

Arquivos:

- `admin/webhook_receiver.php`
- `admin/whatsapp/webhook.php`

Ambos exigem `require_admin()`, mas processam `php://input` como webhook. Se forem webhooks reais, nao devem depender de sessao admin; devem validar assinatura/segredo. Se forem testes internos, devem bloquear mutacao web sem CSRF/segredo.

### P2 - Rotas duplicadas continuam aumentando risco

Persistem pares duplicados `admin/...` e `app/modules/...` para carros, leads e financeiro. Isso tem provocado correcoes em espelho e facilita regressao de CSRF.

Recomendacao: apos beta, consolidar rotas canonicas e transformar legados em wrappers sem logica mutativa.

## Arquivos verificados

Correcoes 26-28:

- `admin/criar_user.php`
- `admin/services/cron_followup.php`
- `admin/services/auto_followup.php`
- `admin/services/follow_up.php`
- `admin/leads/adicionar_lead.php`
- `app/modules/leads/adicionar_lead.php`
- `admin/leads/ver_lead.php`
- `app/modules/leads/ver_lead.php`
- `app/modules/cars/adicionar_carro.php`
- `app/views/admin/leads/adicionar_lead_content.php`
- `app/views/admin/leads/ver_lead_content.php`

Revalidacoes anteriores:

- `admin/vendas/aprovar_venda.php`
- `admin/vendas/rejeitar_venda.php`
- `admin/vendas/atualizar_venda.php`
- `admin/vendas/marcar_venda.php`
- `admin/vendas/confirmar_venda.php`
- `admin/vendas/pagar_venda.php`
- `admin/vendas/vendas.php`
- `admin/vendas/venda_detalhe.php`
- `admin/vendas/vendedor_converter.php`
- `admin/vendas/vendedor_status.php`
- `admin/vendas/vendedor_apagar.php`
- `app/modules/finance/pedir_saque.php`
- `app/modules/finance/marcar_pago.php`
- `app/modules/finance/marcar_vendido.php`
- `app/modules/finance/custos.php`
- `admin/carros/apagar_carro.php`
- `app/modules/cars/apagar_carro.php`
- `admin/carros/carro_fotos_delete.php`
- `app/modules/cars/carro_fotos_delete.php`
- `admin/apagar_foto.php`
- `admin/mover_foto.php`
- `admin/mudar_estado.php`
- `admin/leads/leads_status.php`
- `app/modules/leads/leads_status.php`
- `admin/leads/leads_status_ajax.php`
- `app/modules/leads/leads_status_ajax.php`
- `admin/leads/lead_move.php`
- `app/modules/leads/lead_move.php`
- `admin/cron_liberar_saldo.php`
- `app/core/helpers/upload_security.php`

Modulo de importacao:

- `public/importar_carro.php`
- `admin/importacoes/index.php`
- `app/views/admin/importacoes/index_content.php`
- `storage/database/migrations/20260526_importacao_leads_enums.sql`
- `storage/reports/importacao-module-20260526.md`

Achados P0 residuais:

- `admin/crm/inbox.php`
- `app/views/admin/crm/inbox_content.php`
- `admin/salvar_interacao.php`
- `admin/vendas/vender.php`
- `views/api/move_stage.php`
- `admin/carros/carro_save.php`
- `app/modules/cars/carro_save.php`
- `admin/carros/carro_fotos_order.php`
- `app/modules/cars/carro_fotos_order.php`
- `admin/whatsapp_redirect.php`

## Buscas realizadas

```powershell
rg -n "REQUEST_METHOD|\$_POST|\$_GET|INSERT INTO|UPDATE .*SET|DELETE FROM|unlink\(|move_uploaded_file|csrf_token|csrf_verify|hash_equals|csrf_input|require_admin|display_errors|error_reporting|var_dump|print_r|mysqli_error" admin app public actions views includes -g "*.php"

rg -n "move_uploaded_file\(" admin app public actions views includes -g "*.php"

rg -n "display_errors|error_reporting\(E_ALL\)|var_dump\(|print_r\(|console\.log\(" admin app public actions views includes -g "*.php"

rg -n -F "carro_save.php" admin app public views includes
rg -n -F "carro_fotos_order.php" admin app public views includes
rg -n -F "crm-note-form" admin app public views includes
rg -n -F "vendas/vender.php" admin app public views includes
rg -n -F "salvar_interacao.php" admin app public views includes
rg -n -F "whatsapp_redirect.php" admin app public views includes
```

Busca auxiliar para mutacoes sem indicador CSRF:

```powershell
$roots=@('admin','app','actions','views','public','includes')
Get-ChildItem -Path $roots -Recurse -Filter *.php |
  ForEach-Object {
    $c=Get-Content -Raw -Path $_.FullName
    if($c -match 'REQUEST_METHOD|\$_POST|php://input' -and
       $c -match 'INSERT INTO|UPDATE\s+[^;]+SET|DELETE FROM|unlink\(|move_uploaded_file|mysqli_query' -and
       $c -notmatch 'csrf_token|csrf_verify|csrf_input|hash_equals') {
      Resolve-Path -Relative $_.FullName
    }
  }
```

Resultado relevante:

```text
.\admin\salvar_interacao.php
.\admin\webhook_receiver.php
.\admin\carros\carro_fotos_order.php
.\admin\carros\carro_save.php
.\admin\vendas\vender.php
.\admin\whatsapp\webhook.php
.\app\modules\cars\carro_fotos_order.php
.\app\modules\cars\carro_save.php
.\app\modules\cars\actions\vender_carro.php
.\actions\criar_carro.php
.\views\api\move_stage.php
.\public\Formulario_cliente.php
.\public\importar_carro.php
.\public\salvar_testdrive.php
.\public\vender_carro.php
```

Observacao: endpoints publicos de lead/test-drive/venda/importacao nao exigem CSRF por desenho publico; foram avaliados separadamente quanto a prepared statements e mensagens genericas quando aplicavel.

## Evidencias das correcoes 26, 27 e 28

### Fix 26 - criacao de utilizador/admin

Confirmado em `admin/criar_user.php`:

```text
require_admin()
if ($_SERVER['REQUEST_METHOD'] === 'POST')
$csrfToken = $_POST['csrf_token'] ?? ''
hash_equals($_SESSION['csrf_token'], $csrfToken)
in_array($role, ['admin', 'vendedor'], true)
password_hash(...)
INSERT INTO users (...) via mysqli_prepare
formulario com csrf_input()
```

Status: corrigido.

### Fix 27 - servicos de follow-up

Confirmado em:

- `admin/services/cron_followup.php`
- `admin/services/auto_followup.php`

Evidencia:

```text
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metodo invalido.');
}

hash_equals($_SESSION['csrf_token'], $csrfToken)
```

Status: corrigido para esses dois servicos.

### Fix 28 - POST legado CRM/leads/carros

Confirmado em:

- `admin/leads/adicionar_lead.php`
- `app/modules/leads/adicionar_lead.php`
- `admin/leads/ver_lead.php`
- `app/modules/leads/ver_lead.php`
- `app/modules/cars/adicionar_carro.php`
- `app/views/admin/leads/adicionar_lead_content.php`
- `app/views/admin/leads/ver_lead_content.php`

Evidencia:

```text
POST valida csrf_token com hash_equals antes de INSERT/UPDATE
formularios incluem csrf_input()
```

Status: corrigido para os arquivos alvo do fix 28.

## Evidencia do modulo de importacao

Confirmado:

- `public/importar_carro.php` usa `bootstrap.php`.
- Validacao publica de campos obrigatorios.
- `mysqli_prepare()` para inserir em `leads`.
- `origem = 'importacao'`.
- `status = 'novo'`.
- mensagem do lead contem resumo organizado da importacao.
- erros SQL nao sao expostos ao utilizador.
- `admin/importacoes/index.php` usa `bootstrap.php` e `require_admin()`.
- `app/views/admin/importacoes/index_content.php` lista apenas leads com `origem='importacao'` e permite abrir o lead no CRM.
- nao foi criada tabela `import_requests`; o CRM existente foi reaproveitado.
- migration `storage/database/migrations/20260526_importacao_leads_enums.sql` amplia ENUMs de `tipo`, `origem` e `status`.
- MySQL local confirmou ENUMs aplicados:

```text
tipo=enum('testdrive','venda','importacao','consulta','orcamento')
origem=enum('site','ig','fb','wa','outro','importacao')
status=enum('novo','contactado','qualificado','agendado','orcamento','aguardando_opcoes','negociacao','pagamento','embarcado','em_transito','desalfandegamento','entregue','fechado','perdido')
```

Status: modulo de importacao esta integrado ao CRM existente. Pendencia operacional: aplicar a migration nos ambientes que ainda nao a receberam.

## Validacoes adicionais

- `move_uploaded_file()` aparece apenas em `app/core/helpers/upload_security.php`.
- `secure_uploaded_image()` e usado em fluxos de upload verificados.
- `admin/cron_liberar_saldo.php` continua bloqueado para web por `php_sapi_name() !== 'cli'`.
- A maioria das rotas sob `admin/` verificadas usa `require_admin()`.
- Lint pontual nos arquivos inspecionados com maior risco retornou sem erro:
  - `admin/crm/inbox.php`
  - `app/views/admin/crm/inbox_content.php`
  - `admin/carros/carro_save.php`
  - `app/modules/cars/carro_save.php`
  - `admin/whatsapp_redirect.php`

## Conclusao de readiness

**NAO liberar Deploy Beta Privado.**

Motivo: ainda existem P0 criticos apos os fixes 26, 27 e 28:

1. CRM Inbox permite inserir follow-up sem CSRF.
2. Rotas admin legadas ainda fazem INSERT/UPDATE sem CSRF.
3. Endpoints de carros/fotos permitem mutacao sem token.
4. `admin/whatsapp_redirect.php` grava interacao por GET.

O Deploy Beta Privado deve aguardar a correcao desses P0. P1/P2 como debug explicito, erros SQL expostos e duplicacao de rotas podem seguir como observacoes somente depois que os P0 acima forem eliminados.
