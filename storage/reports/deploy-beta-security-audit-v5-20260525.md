# RG Auto Sales - Security/CSRF Audit v5

Data: 2026-05-25  
Fase: Deploy Beta Privado / hardening final  
Escopo: revalidacao apos correcoes 19, 20 e 21

## Resumo executivo

A auditoria v5 confirmou que as correcoes 19, 20 e 21 foram aplicadas nos pontos previstos:

- remocao de fotos: protegida por POST + sessao/admin + CSRF;
- `admin/mudar_estado.php`: protegido por POST + CSRF + whitelist + prepared statement;
- `app/modules/finance/marcar_pago.php`: protegido por POST + CSRF, sem alteracao da regra financeira de pagamento.

Tambem foram revalidadas correcoes anteriores de cron, leads/status, uploads/fotos e vendas. As evidencias principais estao positivas: `cron_liberar_saldo.php` esta bloqueado fora de CLI, uploads usam helper central com validacao de MIME/imagem real, e `move_uploaded_file()` aparece apenas no helper.

No entanto, ainda existem P0 criticos residuais fora das correcoes 19/20/21. A recomendacao e **nao liberar o Deploy Beta Privado ainda** ate corrigir essas rotas.

## Conclusao de readiness

**Nao liberar Deploy Beta Privado.**

Motivo: ainda ha rotas autenticadas destrutivas por GET e uma rota financeira POST sem CSRF. Mesmo com sessao/admin, essas rotas seguem expostas a CSRF ou acionamento indevido por navegacao/link.

## P0 restantes

### P0-1 - Apagar carro por GET com CSRF no query string

Arquivos:

- `admin/carros/apagar_carro.php`
- `app/modules/cars/apagar_carro.php`
- chamadas em:
  - `app/views/admin/carros/listar_carros_content.php`
  - `app/modules/cars/listar_carros.php`

Evidencia:

```text
admin/carros/apagar_carro.php:
$id = intval($_GET['id'] ?? 0);
$csrf = $_GET['csrf_token'] ?? '';
...
mysqli_query($conexao, "DELETE FROM carros_fotos WHERE carro_id = $id");
mysqli_query($conexao, "DELETE FROM carros WHERE id = $id");

app/modules/cars/apagar_carro.php:
$id = intval($_GET['id'] ?? 0);
$csrf = $_GET['csrf_token'] ?? '';
...
mysqli_query($conexao, "DELETE FROM carros_fotos WHERE carro_id = $id");
mysqli_query($conexao, "DELETE FROM carros WHERE id = $id");
```

Impacto: delecao destrutiva de carro e fotos por GET. CSRF em query string reduz protecao e pode vazar em historico, logs e referer.

Recomendacao: converter para POST only + `csrf_input()` + `hash_equals()` + prepared statements.

### P0-2 - Ordenacao/movimentacao de foto por GET

Arquivo:

- `admin/mover_foto.php`

Evidencia:

```text
$id = intval($_GET['id'] ?? 0);
$dir = $_GET['dir'] ?? '';
$carro_id = intval($_GET['carro_id'] ?? 0);
...
mysqli_query($conexao, "UPDATE carros_fotos SET ordem = $ordemVizinha WHERE id = $id");
mysqli_query($conexao, "UPDATE carros_fotos SET ordem = $ordemAtual WHERE id = $idVizinha");
```

Impacto: alteracao de estado por GET sem CSRF. Afeta ordem de fotos/galeria.

Recomendacao: exigir POST + CSRF + prepared statements ou consolidar com a rota ja protegida de ordenacao (`carro_fotos_order.php` / `salvar_ordem_fotos.php`).

### P0-3 - Rota legado de vendedor/status por GET

Arquivo:

- `app/modules/leads/actions/update_status.php`

Evidencia:

```text
$id     = intval($_GET['id'] ?? 0);
$status = $_GET['status'] ?? '';
$token  = $_GET['token'] ?? '';
...
$stmt2 = mysqli_prepare($conexao, "UPDATE vendedores SET status = ? WHERE id = ?");
...
if ($status === "aprovado" && empty($carroIdAtual)) {
    INSERT INTO carros (...)
}
```

Impacto: altera status de vendedor por GET e, no estado `aprovado`, pode criar carro. Tambem contem debug explicito (`ini_set('display_errors', 1); error_reporting(E_ALL);`).

Recomendacao: converter para POST only + CSRF via POST + whitelist + remover debug de producao.

### P0-4 - Follow-up legado por GET com update

Arquivo:

- `admin/services/follow_up.php`

Evidencia:

```text
$id = (int)($_GET['id'] ?? 0);
...
UPDATE leads
SET tentativas_followup = tentativas_followup + 1,
    proximo_followup = ?,
    status = 'contactado'
WHERE id = ?
```

Impacto: alteracao de lead/follow-up por GET sem CSRF.

Recomendacao: converter para POST only + CSRF + validar id positivo.

### P0-5 - Rota financeira `pedir_saque.php` com POST sem CSRF

Arquivo:

- `app/modules/finance/pedir_saque.php`

Evidencia:

```text
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valor = (float)$_POST['valor'];
    ...
    INSERT INTO saques (user_id, valor)
    ...
    UPDATE wallet
    SET saldo_disponivel = saldo_disponivel - $valor
}
```

Impacto: cria saque e altera saldo via POST sem token CSRF. Tambem usa interpolacao SQL de valores derivados de sessao/POST.

Recomendacao: exigir CSRF com `hash_equals()`, validar `valor`, usar prepared statements e revisar a dependencia de `$_SESSION['user_id']` legado.

### P0-6 - `app/modules/finance/marcar_vendido.php` cria venda por GET

Arquivo:

- `app/modules/finance/marcar_vendido.php`

Evidencia:

```text
$id = (int)($_GET['id'] ?? 0);
...
INSERT INTO vendas (...)
...
UPDATE carros SET status='vendido' WHERE id=?
```

Impacto: cria venda real e altera status de carro por GET, sem CSRF.

Recomendacao: exigir POST + CSRF + preservar regra financeira existente.

## P1/P2 recomendados

### P1 - Debug explicito em producao

Arquivos com `ini_set('display_errors', 1)` ou `error_reporting(E_ALL)` em rotas administrativas/modulos:

- `admin/admin.php`
- `admin/gerir_fotos.php`
- `admin/vendas/vendedor_ver.php`
- `admin/vendas/vendedor_status.php`
- `admin/vendas/vendedor_apagar.php`
- `app/modules/sales/marcar_vendido.php`
- `app/modules/leads/actions/update_status.php`
- `app/modules/cars/actions/delete.php`
- `app/modules/cars/actions/vender_carro.php`

Recomendacao: remover debug explicito das rotas web de producao e centralizar logging seguro.

### P1 - SQL dinamico em rotas de exclusao/ordenacao legadas

Arquivos:

- `admin/carros/apagar_carro.php`
- `app/modules/cars/apagar_carro.php`
- `admin/mover_foto.php`
- `admin/apagar_foto.php`

Observacao: parte dos ids e convertida para inteiro, reduzindo SQL injection direto, mas ainda ha queries destrutivas interpoladas. Preferir prepared statements para padrao uniforme.

### P2 - Rotas legadas duplicadas

Ha pares `admin/...` e `app/modules/...` com logica semelhante para carros/fotos/leads. Isso aumenta risco de corrigir um lado e deixar o outro vulneravel.

Recomendacao: apos P0, consolidar rotas duplicadas ou documentar rota canonica.

## Arquivos verificados

Correcoes 19, 20, 21:

- `admin/carros/carro_fotos_delete.php`
- `app/modules/cars/carro_fotos_delete.php`
- `admin/apagar_foto.php`
- `admin/carros/carro_fotos.php`
- `app/modules/cars/carro_fotos.php`
- `admin/mudar_estado.php`
- `app/modules/finance/marcar_pago.php`
- `app/modules/finance/financeiro.php`

Correcoes anteriores:

- `admin/cron_liberar_saldo.php`
- `admin/leads/leads_status.php`
- `app/modules/leads/leads_status.php`
- `admin/leads/leads_status_ajax.php`
- `app/modules/leads/leads_status_ajax.php`
- `admin/leads/lead_move.php`
- `app/modules/leads/lead_move.php`
- `admin/funil.php`
- `admin/crm/inbox.php`
- `app/views/admin/crm/inbox_content.php`
- `app/views/admin/leads/listar_leads_content.php`
- `app/modules/leads/listar_leads.php`
- `app/core/helpers/upload_security.php`
- `app/core/bootstrap.php`
- `admin/carros/carro_save.php`
- `app/modules/cars/carro_save.php`
- `admin/carros/editar_carro.php`
- `app/modules/cars/editar_carro.php`
- `admin/gerir_fotos.php`
- `app/modules/cars/actions/vender_carro.php`
- `admin/vendas/*.php`
- `app/views/admin/vendas/*.php`

Rotas com achados residuais:

- `admin/carros/apagar_carro.php`
- `app/modules/cars/apagar_carro.php`
- `admin/mover_foto.php`
- `app/modules/leads/actions/update_status.php`
- `admin/services/follow_up.php`
- `app/modules/finance/pedir_saque.php`
- `app/modules/finance/marcar_vendido.php`

## Buscas realizadas

```powershell
rg -n 'href=["''][^"'']*(delete|apagar|remover|mudar_estado|marcar_pago|status|pagar|aprovar|rejeitar|confirmar|cancelar)[^"'']*\?' admin app views public -S
```

Resultado: sem matches nessa forma restrita apos ajuste de quoting.

```powershell
rg -n 'href=.*\?' admin app/views app/modules -S
```

Resultado relevante:

- `app/views/admin/carros/listar_carros_content.php` ainda chama `admin/carros/apagar_carro.php?id=...&csrf_token=...`;
- `app/modules/cars/listar_carros.php` ainda chama `apagar_carro.php?id=...&csrf_token=...`;
- demais links com query revisados eram navegacao/leitura ou WhatsApp, exceto legados listados em P0.

```powershell
rg -n '\$_GET' admin app/modules app/views -S
```

Resultado relevante:

- GET destrutivo residual em `admin/mover_foto.php`;
- GET destrutivo residual em `admin/carros/apagar_carro.php`;
- GET destrutivo residual em `app/modules/cars/apagar_carro.php`;
- GET destrutivo residual em `app/modules/leads/actions/update_status.php`;
- GET destrutivo residual em `admin/services/follow_up.php`;
- GET destrutivo residual em `app/modules/finance/marcar_vendido.php`.

```powershell
rg -n 'UPDATE|DELETE FROM|unlink\(' admin app/modules app/views -S
```

Resultado: confirmou os pontos destrutivos acima e confirmou as rotas corrigidas 19/20/21.

```powershell
rg -n 'REQUEST_METHOD|csrf_token|hash_equals|csrf_input|UPDATE vendas|status=''PAGO''|forma_pagamento|confirmar_pagamento' app/modules/finance admin/financeiro admin/vendas -S
```

Resultado: `marcar_pago.php`, vendas e dashboard financeiro revalidados; achado residual em `pedir_saque.php`.

```powershell
rg -n 'move_uploaded_file\s*\(' admin app views public -S
```

Resultado:

```text
app\core\helpers\upload_security.php:85:        if (!move_uploaded_file($tmp, $destination)) {
```

```powershell
rg -n 'secure_image_upload\(|ensure_upload_htaccess\(|upload_security\.php|move_uploaded_file\(' admin app public -S
```

Resultado:

```text
app\core\helpers\upload_security.php:85: move_uploaded_file(...)
app\core\bootstrap.php:8: require_once __DIR__ . '/helpers/upload_security.php';
```

```powershell
rg -n '(var_dump|print_r\s*\(|console\.log|debug|die\(|mysqli_error\(|display_errors|error_reporting\()' admin app views public -S
```

Resultado: encontrou debug explicito de producao em rotas listadas na secao P1.

```powershell
$files = Get-ChildItem -Path admin -Recurse -Filter *.php | Where-Object { $_.FullName -notmatch '\\(includes|assets)\\' }; foreach ($f in $files) { $text = Get-Content -LiteralPath $f.FullName -Raw; if ($text -match 'require_once .*bootstrap' -and $text -notmatch 'require_admin\s*\(') { $f.FullName.Substring((Get-Location).Path.Length + 1) } }
```

Resultado: sem arquivos admin com bootstrap e sem `require_admin()` nessa amostra.

## Evidencias das correcoes 19, 20 e 21

### Correcao 19 - Remocao de fotos

`admin/carros/carro_fotos_delete.php` e `app/modules/cars/carro_fotos_delete.php`:

```text
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); ... }
$csrfToken = (string)($data['csrf_token'] ?? '');
!hash_equals($_SESSION['csrf_token'], $csrfToken)
$stmtD = mysqli_prepare($conexao, "DELETE FROM carros_fotos WHERE id = ? LIMIT 1");
```

Chamadas em `carro_fotos.php` usam `fetch('carro_fotos_delete.php', { ... body: JSON.stringify({ id, csrf_token: ... }) })`.

`admin/apagar_foto.php`:

```text
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect_to(...metodo_invalido); }
$csrfToken = $_POST['csrf_token'] ?? '';
!hash_equals($_SESSION['csrf_token'], $csrfToken)
$id = intval($_POST['id'] ?? 0);
```

### Correcao 20 - `admin/mudar_estado.php`

```text
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect_to(...metodo_invalido); }
$csrfToken = $_POST['csrf_token'] ?? '';
!hash_equals($_SESSION['csrf_token'], $csrfToken)
$id = intval($_POST['id'] ?? 0);
$allowedEstados = ['novo', 'negociacao'];
$stmt = mysqli_prepare($conexao, "UPDATE clientes SET estado = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, "si", $estado, $id);
```

### Correcao 21 - `app/modules/finance/marcar_pago.php`

```text
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('app/modules/finance/financeiro.php?msg=metodo_invalido');
}
$csrfToken = $_POST['csrf_token'] ?? '';
!hash_equals($_SESSION['csrf_token'], $csrfToken)
$id = intval($_POST['id'] ?? $_POST['venda_id'] ?? 0);
if (isset($_POST['confirmar_pagamento'])) { ... UPDATE vendas SET status='PAGO' ... }
```

Chamada em `app/modules/finance/financeiro.php`:

```text
<form method="POST" action="marcar_pago.php" style="display:inline;">
    <?= csrf_input() ?>
    <input type="hidden" name="id" value="<?= h($v['id']) ?>">
    <button type="submit" class="btn btn-sm btn-success">PAGO</button>
</form>
```

## Evidencias das correcoes anteriores

### Cron

`admin/cron_liberar_saldo.php`:

```text
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado.');
}
```

### Leads/status

Rotas principais de status de leads usam POST + CSRF:

- `admin/leads/leads_status.php`
- `app/modules/leads/leads_status.php`
- `admin/leads/leads_status_ajax.php`
- `app/modules/leads/leads_status_ajax.php`
- `admin/leads/lead_move.php`
- `app/modules/leads/lead_move.php`

Evidencia comum:

```text
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { ... }
!hash_equals($_SESSION['csrf_token'], $csrfToken)
```

### Uploads/fotos

`app/core/helpers/upload_security.php`:

```text
$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
$dangerousExtensions = ['php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'htaccess'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$imageInfo = @getimagesize($tmp);
$filename = $safePrefix . '-' . bin2hex(random_bytes(16)) . '.' . $extension;
```

`move_uploaded_file()` aparece somente no helper central.

### Vendas

Rotas administrativas de venda revisadas continuam com POST + CSRF nas acoes sensiveis:

- `admin/vendas/confirmar_venda.php`
- `admin/vendas/atualizar_venda.php`
- `admin/vendas/aprovar_venda.php`
- `admin/vendas/rejeitar_venda.php`
- `admin/vendas/pagar_venda.php`
- `admin/vendas/marcar_venda.php`

Evidencia comum:

```text
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { ... }
!hash_equals($_SESSION['csrf_token'], $csrfToken)
```

## Resultado final

As correcoes 19, 20 e 21 estao confirmadas, mas a auditoria v5 encontrou P0 residuais suficientes para bloquear o Deploy Beta Privado.

Status: **NAO LIBERAR**.

Proxima acao recomendada: corrigir P0 restantes na seguinte ordem:

1. `admin/carros/apagar_carro.php` e `app/modules/cars/apagar_carro.php`;
2. `app/modules/finance/pedir_saque.php`;
3. `app/modules/finance/marcar_vendido.php`;
4. `admin/mover_foto.php`;
5. `app/modules/leads/actions/update_status.php`;
6. `admin/services/follow_up.php`.
