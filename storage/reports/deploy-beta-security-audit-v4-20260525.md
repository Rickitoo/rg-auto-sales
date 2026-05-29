# Auditoria Security v4 - Pos Correcoes 16, 17 e 18

Data: 2026-05-25  
Projeto: RG Auto Sales  
Escopo: revalidacao final CSRF/Security antes do Deploy Beta Privado.  
Resultado: auditoria apenas informativa. Nenhum codigo foi alterado nesta tarefa.

## Resumo executivo

As correcoes 16, 17 e 18 foram confirmadas:

- `admin/cron_liberar_saldo.php` nao executa mais via web comum e retorna bloqueio antes do bootstrap.
- Rotas de `leads/status` deixaram de aceitar mudanca via GET e agora exigem POST + admin + CSRF + whitelist.
- Uploads de fotos foram centralizados em helper seguro, com validacao de extensao, MIME real, `getimagesize()`, nome aleatorio, limite de tamanho e hardening de diretorios.

Ainda assim, a varredura final encontrou P0 criticos restantes antes do Deploy Beta Privado:

1. Remocao de fotos ainda possui rotas sem CSRF adequado.
2. `admin/mudar_estado.php` altera estado de cliente por GET e ainda injeta `estado` diretamente no SQL.
3. Fluxo financeiro legado `app/modules/finance/marcar_pago.php` atualiza venda para `PAGO` via POST sem CSRF.

Conclusao executiva: **nao liberar Deploy Beta Privado ainda**. O projeto melhorou bastante, mas ainda ha P0 destrutivos/financeiros reais.

## P0 restantes

### P0-1 - Remocao de fotos sem CSRF completo

| Arquivo | Problema | Impacto |
|---|---|---|
| `admin/carros/carro_fotos_delete.php` | Remove foto via JSON sem `csrf_token`; nao ha validacao de metodo antes da mutacao | Remove registro, atualiza capa e apaga arquivo fisico |
| `admin/apagar_foto.php` | Remove foto por GET `id`/`carro_id` sem CSRF | Remove registro e arquivo fisico |

Evidencias:

- `admin/carros/carro_fotos_delete.php` le `php://input`, extrai `id`, executa `DELETE FROM carros_fotos`, atualiza `carros.imagem` e chama `@unlink()`.
- Chamadores reais ainda encontrados:
  - `admin/carros/carro_fotos.php` chama `fetch('carro_fotos_delete.php', { method: 'POST', body: JSON.stringify({ id }) })` sem token.
  - `app/modules/cars/carro_fotos.php` tem o mesmo padrao.
- `admin/apagar_foto.php` le `$_GET['id']` e `$_GET['carro_id']`, executa `unlink()` e `DELETE FROM carros_fotos`.

Observacao: a correcao 18 protegeu uploads, mas nao corrigiu endpoints de remocao de fotos.

### P0-2 - `admin/mudar_estado.php` altera cliente por GET

| Arquivo | Problema | Impacto |
|---|---|---|
| `admin/mudar_estado.php` | Usa `$_GET['id']` e `$_GET['estado']` sem POST/CSRF/allowlist | Altera `clientes.estado`; `estado` entra direto no SQL |

Evidencia:

```php
$id = intval($_GET['id']);
$estado = $_GET['estado'];
mysqli_query($conexao, "
UPDATE clientes SET estado='$estado' WHERE id=$id
");
```

Risco: alem de CSRF por GET, `estado` deve ser restringido por allowlist e prepared statement.

### P0-3 - Pagamento financeiro legado sem CSRF

| Arquivo | Problema | Impacto |
|---|---|---|
| `app/modules/finance/marcar_pago.php` | Formulario POST altera venda para `PAGO` sem token CSRF | Muda status financeiro e data de pagamento |

Evidencias:

- `app/modules/finance/financeiro.php` ainda possui link GET para abrir `marcar_pago.php?id=...`.
- `app/modules/finance/marcar_pago.php` busca `id` por `$_GET` ou `$_POST`.
- A mutacao ocorre em POST, mas sem validacao CSRF:

```php
UPDATE vendas SET
    status='PAGO',
    forma_pagamento=?,
    data_pagamento=NOW()
WHERE id=? LIMIT 1
```

Observacao: o GET aparentemente abre tela/formulario, mas a acao POST que marca pagamento nao exige CSRF.

## P1/P2 recomendados

### P1 - Destrutivos por GET com token em URL

- `admin/carros/apagar_carro.php`
- `app/modules/cars/apagar_carro.php`

Ambos validam `csrf_token`, mas continuam executando delete por GET. Recomendado migrar para POST com `csrf_input()` para reduzir vazamento de token em URL, logs, historico e referer.

### P1 - Status de vendedores por GET com token em URL

- `app/modules/leads/actions/update_status.php`
- links em `admin/admin.php` para `update_status.php?id=...&status=...&token=...`

Nao e rota de lead/status corrigida na correcao 17; atua sobre `vendedores`. Ainda assim, altera status por GET e deve migrar para POST.

### P1 - Debug explicito em arquivos executaveis

Ocorrencias encontradas de `ini_set('display_errors', 1)` / `error_reporting(E_ALL)` em:

- `admin/admin.php`
- `admin/gerir_fotos.php`
- `admin/vendas/vendedor_ver.php`
- `admin/vendas/vendedor_status.php`
- `admin/vendas/vendedor_apagar.php`
- `app/modules/cars/actions/vender_carro.php`
- `app/modules/cars/actions/delete.php`
- `app/modules/leads/actions/update_status.php`
- `app/modules/sales/marcar_vendido.php`

Recomendacao: remover debug explicito antes do beta ou condicionar a ambiente local.

### P2 - Mensagens de erro SQL/estrutura em tela

Ha varios `die("Erro ... " . mysqli_error(...))` e mensagens que revelam detalhes internos. Nao e necessariamente P0 em beta privado autenticado, mas deve ser reduzido para logs internos e mensagens genericas.

## Arquivos verificados

### Correcoes 16-18

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
- `admin/carros/carro_fotos.php`
- `app/modules/cars/carro_fotos.php`
- `admin/carros/editar_carro.php`
- `app/modules/cars/editar_carro.php`
- `admin/gerir_fotos.php`
- `app/modules/cars/actions/vender_carro.php`
- `public/uploads/.htaccess`
- `public/assets/uploads/.htaccess`
- `public/assets/uploads/vendas/carros/.htaccess`

### Rotas/areas adicionais revalidadas

- `admin/carros/carro_fotos_delete.php`
- `admin/apagar_foto.php`
- `admin/mudar_estado.php`
- `app/modules/finance/marcar_pago.php`
- `app/modules/finance/financeiro.php`
- `admin/carros/apagar_carro.php`
- `app/modules/cars/apagar_carro.php`
- `app/modules/leads/actions/update_status.php`
- `admin/admin.php`
- `admin/vendas/vendedor_status.php`
- `admin/vendas/vendedor_apagar.php`

## Buscas realizadas

### Vendas corrigidas

Busca:

```powershell
rg -n "pagar_venda\.php\?|aprovar_venda\.php\?|rejeitar_venda\.php\?|atualizar_venda\.php\?|marcar_venda\.php\?|confirmar_venda\.php\?|vendedor_converter\.php\?|cancelar_venda\.php\?" admin app public -S
```

Resultado: sem ocorrencias. Nao foram encontrados GETs antigos para as rotas de vendas ja corrigidas.

### Leads/status corrigidos

Busca:

```powershell
rg -n "leads_status\.php\?id=|lead_status\.php\?id=|atualizar_status\.php\?id=|mudar_status\.php\?id=|lead_move\.php\?" admin app public -S
```

Resultado: sem ocorrencias. Nao foram encontrados GETs antigos para status de leads.

### Uploads/fotos

Busca:

```powershell
rg -n "move_uploaded_file|mime_content_type|\$_FILES|secure_uploaded_image|php_flag engine off|Options -Indexes" admin app public -S
```

Resultado relevante:

- `move_uploaded_file()` aparece apenas em `app/core/helpers/upload_security.php`.
- rotas reais de upload chamam `secure_uploaded_image()`.
- `.htaccess` de upload contem `php_flag engine off` e `Options -Indexes`.

### Cron financeiro

Busca:

```powershell
Select-String -Path admin\cron_liberar_saldo.php -Pattern "php_sapi_name|http_response_code|Acesso negado|UPDATE wallet|UPDATE vendas|require_admin" -Context 1,1
```

Resultado:

- `php_sapi_name() !== 'cli'` nas linhas iniciais.
- `http_response_code(403)` e `exit('Acesso negado.')` antes do bootstrap.
- queries financeiras continuam presentes, mas so acessiveis apos passar pelo gate CLI.

### GET destrutivos e debug

Buscas:

```powershell
rg -n "apagar_foto|carro_fotos_delete|apagar_carro|delete\.php|mudar_estado|vendedor_apagar|vendedor_status|update_status|mover_foto|salvar_ordem_fotos|href=.*\?" admin app public -S
rg -n "display_errors|error_reporting\(E_ALL|var_dump\(|print_r\(|console\.log\(|debug|teste|TODO|die\(" admin app public -S
```

Resultado: encontrados os P0/P1 listados acima.

### Admin sem protecao

Varredura em `admin/**/*.php` procurando arquivos sem `require_admin()`/`require_login()` ou fluxo de login. Resultado da amostra: nao foram encontrados arquivos admin relevantes sem protecao de sessao/admin. A maior parte das descobertas restantes sao rotas protegidas por admin, mas sem POST/CSRF adequado.

## Evidencias das correcoes 16, 17 e 18

### Correcao 16 - cron CLI-only

`admin/cron_liberar_saldo.php` inicia com:

```php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado.');
}
```

Relatorio de origem:

- `storage/reports/deploy-beta-fix-20260525-16-protect-cron-liberar-saldo.md`

### Correcao 17 - leads/status

Evidencias:

- `admin/leads/leads_status.php` e `app/modules/leads/leads_status.php` usam POST e validam `csrf_token` com `hash_equals()`.
- `admin/leads/lead_move.php` e `app/modules/leads/lead_move.php` bloqueiam GET e exigem CSRF.
- `admin/funil.php` envia `csrf_token` no `fetch`.
- `app/views/admin/leads/listar_leads_content.php` e `app/modules/leads/listar_leads.php` usam formularios POST com `csrf_input()`.

Relatorio de origem:

- `storage/reports/deploy-beta-fix-20260525-17-csrf-leads-status.md`

### Correcao 18 - uploads/fotos

Evidencias:

- `app/core/helpers/upload_security.php` concentra o `move_uploaded_file()`.
- Helper valida extensao, MIME real, imagem real, tamanho, nome aleatorio e destino.
- `app/core/bootstrap.php` carrega `upload_security.php`.
- `.htaccess` em `public/uploads`, `public/assets/uploads` e `public/assets/uploads/vendas/carros`.

Relatorio de origem:

- `storage/reports/deploy-beta-fix-20260525-18-secure-uploads-fotos.md`

## Conclusao sobre readiness

Estado atual: **nao pronto para Deploy Beta Privado**.

Motivo: ainda existem P0 acionaveis em ambiente admin autenticado:

- remocao de fotos sem CSRF completo;
- rota GET para alterar estado de cliente;
- pagamento financeiro legado via POST sem CSRF.

Recomendacao para GO beta:

1. Corrigir P0-1: `admin/carros/carro_fotos_delete.php` e `admin/apagar_foto.php`.
2. Corrigir P0-2: `admin/mudar_estado.php`.
3. Corrigir P0-3: `app/modules/finance/marcar_pago.php` e respectivo chamador.
4. Rodar nova auditoria curta v5 focada apenas nesses P0.

Depois desses pontos, os P1/P2 podem ser tratados como hardening pos-GO beta privado, desde que o beta seja restrito e monitorado.
