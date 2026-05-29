# RG Auto Sales - Security/CSRF Audit v6

Data: 2026-05-25  
Fase: Deploy Beta Privado / hardening final  
Escopo: revalidacao apos correcoes 22, 23, 24 e 25

## Resumo executivo

A auditoria v6 confirmou que os P0 tratados nas correcoes 22, 23, 24 e 25 foram corrigidos nos arquivos alvo:

- carros/fotos residual: apagar carro e mover foto agora exigem POST + CSRF;
- CRM legado: `update_status.php` e `follow_up.php` agora exigem POST + CSRF;
- `pedir_saque.php`: saque agora exige POST + CSRF;
- `marcar_vendido.php` financeiro: venda/marcacao agora exige POST + CSRF.

Tambem foram revalidadas as correcoes anteriores de vendas, leads/status, uploads/fotos, cron principal, `mudar_estado`, `marcar_pago` e remocao de fotos. Nao foi encontrado `move_uploaded_file()` fora do helper central de upload.

Porem, ainda existem rotas administrativas mutativas sem CSRF e rotas de servico que alteram dados em GET/page load. Portanto, a conclusao e: **nao liberar Deploy Beta Privado ainda**.

## Conclusao de readiness

**Nao liberar Deploy Beta Privado.**

Motivo: restam P0 criticos de CSRF/GET mutativo fora das correcoes 22-25, incluindo criacao de utilizador/admin sem CSRF e servicos de follow-up que alteram CRM por simples acesso GET.

## P0 restantes

### P0-1 - Criacao de utilizador/admin por POST sem CSRF

Arquivo:

- `admin/criar_user.php`

Evidencia:

```text
admin/criar_user.php:7: if ($_SERVER['REQUEST_METHOD'] === 'POST') {
admin/criar_user.php:14: INSERT INTO users (username, email, password, role)
admin/criar_user.php:31: <form method="POST">
```

Nao ha `csrf_token`, `csrf_input()`, `csrf_verify()` ou `hash_equals()` no arquivo.

Impacto: um admin autenticado pode ser induzido via CSRF a criar um novo utilizador com `role=admin` ou `role=vendedor`. Isso e critico para beta privado.

Recomendacao: exigir POST + `csrf_input()` no formulario + `csrf_verify()` no handler; validar whitelist de `role`.

### P0-2 - Servicos de CRM/follow-up executam escrita em GET/page load

Arquivos:

- `admin/services/cron_followup.php`
- `admin/services/auto_followup.php`

Evidencia:

```text
admin/services/cron_followup.php:32: INSERT INTO mensagens (lead_id, mensagem, tipo)
admin/services/cron_followup.php:41: UPDATE leads SET proximo_followup = DATE_ADD(NOW(), INTERVAL 1 DAY)

admin/services/auto_followup.php:33: UPDATE leads SET last_contact=? WHERE id=?
```

Nao ha bloqueio `php_sapi_name() === 'cli'`, segredo de cron, POST-only ou CSRF. As rotas estao protegidas por `require_admin()`, mas um GET autenticado ainda altera estado.

Impacto: alteracao de CRM/follow-up por simples navegacao, prefetch, link ou CSRF GET.

Recomendacao: se forem cron reais, bloquear web e permitir apenas CLI ou token de cron fora da sessao; se forem acoes manuais, converter para POST + CSRF.

### P0-3 - CRM Inbox: adicionar follow-up sem CSRF

Arquivos:

- `admin/crm/inbox.php`
- `app/views/admin/crm/inbox_content.php`

Evidencia:

```text
admin/crm/inbox.php:67: if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'followup') {
admin/crm/inbox.php:78: INSERT INTO lead_followups (lead_id, mensagem, status, admin_id, admin_nome)
admin/crm/inbox.php:86: UPDATE leads SET atualizado_em=NOW() WHERE id=? LIMIT 1

app/views/admin/crm/inbox_content.php:142: <form class="crm-note-form" method="POST" action="...admin/crm/inbox.php">
app/views/admin/crm/inbox_content.php:143: <input type="hidden" name="acao" value="followup">
```

A branch `acao=status` tem CSRF, mas a branch `acao=followup` nao valida token e o formulario nao inclui `csrf_input()`.

Impacto: qualquer pagina externa pode forjar uma nota/follow-up no CRM em nome do admin autenticado.

Recomendacao: adicionar `csrf_input()` ao formulario de nota e validar `csrf_token` antes de inserir follow-up.

### P0-4 - Leads legados aceitam POST mutativo sem CSRF

Arquivos:

- `admin/leads/adicionar_lead.php`
- `app/modules/leads/adicionar_lead.php`
- `admin/leads/ver_lead.php`
- `app/modules/leads/ver_lead.php`

Evidencia:

```text
admin/leads/adicionar_lead.php:12: if ($_SERVER['REQUEST_METHOD'] === 'POST') {
admin/leads/adicionar_lead.php:23: INSERT INTO leads (...)

app/modules/leads/adicionar_lead.php:12: if ($_SERVER['REQUEST_METHOD'] === 'POST') {
app/modules/leads/adicionar_lead.php:23: INSERT INTO leads (...)

admin/leads/ver_lead.php:52: if ($_SERVER['REQUEST_METHOD'] === 'POST') {
admin/leads/ver_lead.php:62: INSERT INTO mensagens (...)
admin/leads/ver_lead.php:82: UPDATE leads SET ultima_interacao = NOW(), proximo_followup = ...
```

Nao ha validacao CSRF nos handlers acima.

Impacto: criacao de leads falsos e insercao de mensagens/interacoes em CRM por CSRF.

Recomendacao: adicionar token aos formularios e validar antes de qualquer `INSERT`/`UPDATE`.

### P0-5 - Modulo legado de carros permite criar carro por POST sem CSRF

Arquivo:

- `app/modules/cars/adicionar_carro.php`

Evidencia:

```text
app/modules/cars/adicionar_carro.php:18: if ($_SERVER['REQUEST_METHOD'] === 'POST') {
app/modules/cars/adicionar_carro.php:47: INSERT INTO carros
app/modules/cars/adicionar_carro.php:165: <form method="POST">
```

Nao ha `csrf_token`, `csrf_input()`, `csrf_verify()` ou `hash_equals()`.

Impacto: criacao de viaturas no inventario por CSRF se a rota legado estiver acessivel a admin autenticado. Ha chamada ativa em `app/modules/cars/listar_carros.php`.

Recomendacao: aplicar o mesmo padrao ja usado em `admin/carros/adicionar_carro.php`.

## P1/P2 recomendados

### P1 - Debug explicito em producao

Ainda existem `ini_set('display_errors', 1)` e/ou `error_reporting(E_ALL)` em rotas web:

- `admin/admin.php`
- `admin/gerir_fotos.php`
- `admin/vendas/vendedor_ver.php`
- `admin/vendas/vendedor_status.php`
- `admin/vendas/vendedor_apagar.php`
- `app/modules/sales/marcar_vendido.php`
- `app/modules/cars/actions/delete.php`
- `app/modules/cars/actions/vender_carro.php`

Recomendacao: remover debug explicito de rotas web de producao e centralizar logging seguro.

### P1 - Queries dinamicas em rotas ja protegidas

Mesmo com POST + CSRF, algumas rotas ainda interpolam IDs convertidos para inteiro:

- `admin/carros/apagar_carro.php`
- `app/modules/cars/apagar_carro.php`
- `admin/mover_foto.php`
- `admin/apagar_foto.php`
- `admin/admin_saques.php`

Recomendacao: migrar para prepared statements para consistencia e manutencao segura.

### P2 - Rotas duplicadas/legadas aumentam risco

Ha duplicacao entre `admin/...` e `app/modules/...` para carros, leads e financeiro. Varias correcoes ja precisaram ser aplicadas em pares.

Recomendacao: documentar rota canonica por fluxo e apos beta consolidar/depreciar wrappers legados.

## Arquivos verificados

Correcoes 22-25:

- `admin/carros/apagar_carro.php`
- `app/modules/cars/apagar_carro.php`
- `admin/mover_foto.php`
- `app/views/admin/carros/listar_carros_content.php`
- `app/modules/cars/listar_carros.php`
- `app/modules/leads/actions/update_status.php`
- `admin/services/follow_up.php`
- `admin/admin.php`
- `app/modules/leads/leads.php`
- `app/modules/finance/pedir_saque.php`
- `app/modules/finance/marcar_vendido.php`

Correcoes anteriores revalidadas:

- `admin/vendas/aprovar_venda.php`
- `admin/vendas/rejeitar_venda.php`
- `admin/vendas/atualizar_venda.php`
- `admin/vendas/marcar_venda.php`
- `admin/vendas/confirmar_venda.php`
- `admin/vendas/pagar_venda.php`
- `app/modules/finance/marcar_pago.php`
- `admin/mudar_estado.php`
- `admin/leads/leads_status.php`
- `app/modules/leads/leads_status.php`
- `admin/leads/leads_status_ajax.php`
- `app/modules/leads/leads_status_ajax.php`
- `admin/leads/lead_move.php`
- `app/modules/leads/lead_move.php`
- `admin/cron_liberar_saldo.php`
- `admin/carros/carro_fotos_delete.php`
- `app/modules/cars/carro_fotos_delete.php`
- `admin/apagar_foto.php`
- `admin/carros/carro_fotos.php`
- `app/modules/cars/carro_fotos.php`
- `admin/gerir_fotos.php`
- `app/core/helpers/upload_security.php`
- `app/core/bootstrap.php`

Achados residuais:

- `admin/criar_user.php`
- `admin/services/cron_followup.php`
- `admin/services/auto_followup.php`
- `admin/crm/inbox.php`
- `app/views/admin/crm/inbox_content.php`
- `admin/leads/adicionar_lead.php`
- `app/modules/leads/adicionar_lead.php`
- `admin/leads/ver_lead.php`
- `app/modules/leads/ver_lead.php`
- `app/modules/cars/adicionar_carro.php`

## Buscas realizadas

```powershell
rg -n -F '$_GET' app admin public actions views includes
rg -n -F 'REQUEST_METHOD' app admin public actions views includes
rg -n -F 'csrf_token' app admin public actions views includes
rg -n -F '.php?' app admin public actions views includes
rg -n -F 'fetch(' admin app public actions views includes
rg -n 'INSERT INTO|UPDATE |DELETE FROM|unlink\(|mysqli_query\(|mysqli_prepare\(' app\modules\finance admin\financeiro admin\admin_saques.php app\views\admin\financeiro includes\financeiro.php
rg move_uploaded_file app admin public actions views includes
rg "display_errors|error_reporting|var_dump|print_r|console\.log" app admin public actions views includes
rg -n "UPDATE |INSERT INTO|DELETE FROM|unlink\(" admin\services views\crm\services admin\whatsapp admin\webhook_receiver.php app\modules\leads app\modules\cars app\modules\finance -g "*.php"
```

Busca auxiliar para POST sem indicadores de CSRF:

```powershell
$files = Get-ChildItem -Recurse -Include *.php -Path admin,app,public,actions,views,includes |
  Where-Object { $_.FullName -notmatch '\\storage\\|\\vendor\\' }
foreach ($f in $files) {
  $c = Get-Content -Raw -Path $f.FullName
  if ($c -match 'REQUEST_METHOD' -and $c -match 'POST' -and $c -notmatch 'csrf_token|csrf_verify|csrf_input|hash_equals') {
    Resolve-Path -Relative $f.FullName
  }
}
```

Resultado relevante:

```text
.\admin\leads\adicionar_lead.php
.\admin\leads\ver_lead.php
.\admin\criar_user.php
.\app\modules\cars\actions\vender_carro.php
.\app\modules\cars\adicionar_carro.php
.\app\modules\leads\adicionar_lead.php
```

## Evidencias das correcoes 22, 23, 24 e 25

### Fix 22 - carros/fotos residual

Confirmado em codigo:

```text
admin/carros/apagar_carro.php:7: if ($_SERVER['REQUEST_METHOD'] !== 'POST')
admin/carros/apagar_carro.php:11-12: csrf_token + hash_equals
admin/carros/apagar_carro.php:17: id vem de $_POST

app/modules/cars/apagar_carro.php:7: if ($_SERVER['REQUEST_METHOD'] !== 'POST')
app/modules/cars/apagar_carro.php:11-12: csrf_token + hash_equals
app/modules/cars/apagar_carro.php:17: id vem de $_POST

admin/mover_foto.php:10: if ($_SERVER['REQUEST_METHOD'] !== 'POST')
admin/mover_foto.php:14-15: csrf_token + hash_equals
admin/mover_foto.php:20-22: id, dir e carro_id vem de $_POST
```

### Fix 23 - CRM legado

Confirmado em codigo:

```text
app/modules/leads/actions/update_status.php:7: if ($_SERVER['REQUEST_METHOD'] !== 'POST')
app/modules/leads/actions/update_status.php:11-12: csrf_token + hash_equals
app/modules/leads/actions/update_status.php:17-18: id/status vem de $_POST

admin/services/follow_up.php:10: if ($_SERVER['REQUEST_METHOD'] !== 'POST')
admin/services/follow_up.php:14-15: csrf_token + hash_equals
admin/services/follow_up.php:20: id vem de $_POST
```

### Fix 24 - pedir_saque.php

Confirmado em codigo:

```text
app/modules/finance/pedir_saque.php:5: if ($_SERVER['REQUEST_METHOD'] !== 'POST')
app/modules/finance/pedir_saque.php:9-10: csrf_token + hash_equals
app/modules/finance/pedir_saque.php:43: INSERT INTO saques
app/modules/finance/pedir_saque.php:51-52: UPDATE wallet
```

### Fix 25 - marcar_vendido.php financeiro

Confirmado em codigo:

```text
app/modules/finance/marcar_vendido.php:10: if ($_SERVER['REQUEST_METHOD'] !== 'POST')
app/modules/finance/marcar_vendido.php:14-15: csrf_token + hash_equals
app/modules/finance/marcar_vendido.php:20: id vem de $_POST
app/modules/finance/marcar_vendido.php:57: INSERT INTO vendas
app/modules/finance/marcar_vendido.php:93: UPDATE carros
```

## Validacoes adicionais

- `move_uploaded_file()` aparece apenas em `app/core/helpers/upload_security.php`.
- `secure_uploaded_image()` e usado nos fluxos de upload de carros/fotos/vendas verificados.
- `admin/cron_liberar_saldo.php` continua bloqueado fora de CLI com `php_sapi_name() !== 'cli'`.
- Rotas sob `admin/` verificadas usam `require_admin()`/bootstrap; nao foi encontrado admin publico sem protecao no escopo varrido.
- Nao foi identificado POST financeiro sem CSRF nos alvos revalidados (`pedir_saque`, `marcar_pago`, `marcar_vendido`, `admin_saques`, `custos`).

## Conclusao

**Nao liberar Deploy Beta Privado.**

As correcoes 22, 23, 24 e 25 estao confirmadas, mas ainda ha P0 criticos fora desses fixes. Antes do beta privado, corrigir no minimo:

1. `admin/criar_user.php` com CSRF e whitelist de role;
2. `admin/services/cron_followup.php` e `admin/services/auto_followup.php` para nao mutarem por GET;
3. `admin/crm/inbox.php` / `app/views/admin/crm/inbox_content.php` na branch `followup`;
4. rotas legadas de leads/carros que aceitam POST mutativo sem CSRF.
