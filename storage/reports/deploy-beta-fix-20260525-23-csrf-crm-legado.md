# RG Auto Sales - Fix 23 - CSRF CRM legado

Data: 2026-05-25  
Fase: Deploy Beta Privado / hardening final  
Escopo: status/update legado e follow-up legado por GET

## Problema encontrado

A auditoria v5 identificou duas rotas reais de CRM legado que alteravam estado por GET:

- `app/modules/leads/actions/update_status.php`
  - lia `id`, `status` e `token` de `$_GET`;
  - atualizava `vendedores.status`;
  - quando `status=aprovado`, podia criar um carro e gravar `carro_id`;
  - continha debug explicito de producao.

- `admin/services/follow_up.php`
  - lia `id` de `$_GET`;
  - incrementava `tentativas_followup`;
  - alterava `proximo_followup` e `status`;
  - registrava interacao do lead.

Tambem havia chamadas GET legadas:

- `admin/admin.php` chamava `update_status.php?id=...&status=...&token=...`;
- `app/modules/leads/leads.php` chamava `follow_up.php?id=...`.

## Alteracao aplicada

### `app/modules/leads/actions/update_status.php`

- Bloqueia GET com redirect para `admin/admin.php?msg=metodo_invalido`.
- Exige POST.
- Valida `csrf_token` com `hash_equals()`.
- Valida `id` como inteiro positivo.
- Valida `status` por whitelist:
  - `aprovado`;
  - `rejeitado`;
  - `pendente`.
- Preserva a regra comercial existente:
  - busca vendedor;
  - atualiza `vendedores.status`;
  - se aprovado e sem `carro_id`, cria carro uma vez;
  - grava `carro_id`;
  - redireciona para `admin/admin.php?msg=status_ok`.
- Removeu debug explicito (`display_errors` / `error_reporting`) da rota web.

### `admin/services/follow_up.php`

- Bloqueia GET com redirect para `admin/leads/leads.php?msg=metodo_invalido`.
- Exige POST.
- Valida `csrf_token` com `hash_equals()`.
- Valida `id` como inteiro positivo.
- Preserva a logica existente:
  - le `tentativas_followup`;
  - agenda proximo follow-up em 1, 3 ou 7 dias;
  - incrementa tentativas;
  - muda status para `contactado`;
  - registra interacao;
  - redireciona para `admin/leads/leads.php`.

### Chamadas convertidas

- `admin/admin.php`
  - links GET de aprovar/rejeitar foram convertidos para formularios POST com `csrf_input()`.

- `app/modules/leads/leads.php`
  - link GET de follow-up foi convertido para formulario POST com `csrf_input()`.

## Arquivos alterados

- `app/modules/leads/actions/update_status.php`
- `admin/services/follow_up.php`
- `admin/admin.php`
- `app/modules/leads/leads.php`
- `storage/reports/deploy-beta-fix-20260525-23-csrf-crm-legado.md`

## Fora do escopo

Nao foram alterados nesta tarefa:

- financeiro;
- carros/fotos;
- `app/modules/finance/marcar_vendido.php`;
- regras comerciais de venda/financeiro.

## Buscas realizadas

```powershell
rg -n "update_status\.php|follow_up\.php|follow_up\.php\?|follow_up\.php|status=aprovado|status=rejeitado|tentativas_followup|proximo_followup" admin app views public -S
```

Arquivos reais identificados:

```text
app\modules\leads\actions\update_status.php
admin\services\follow_up.php
```

Chamadas relevantes identificadas:

```text
admin\admin.php: links GET para update_status.php
app\modules\leads\leads.php: link GET para follow_up.php
```

Busca final:

```powershell
rg -n 'update_status\.php\?|follow_up\.php\?|href=.*update_status\.php|href=.*follow_up\.php' admin app views public -S
```

Resultado: sem ocorrencias.

## Validacoes feitas

### Lint individual

```text
C:\xampp\php\php.exe -l app/modules/leads/actions/update_status.php
No syntax errors detected in app/modules/leads/actions/update_status.php

C:\xampp\php\php.exe -l admin/services/follow_up.php
No syntax errors detected in admin/services/follow_up.php

C:\xampp\php\php.exe -l admin/admin.php
No syntax errors detected in admin/admin.php

C:\xampp\php\php.exe -l app/modules/leads/leads.php
No syntax errors detected in app/modules/leads/leads.php
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
Relatorio: C:\Users\rickg\OneDrive\Ambiente de Trabalho\RG_AUTO_SALES\storage\reports\php-lint-20260525-133002.txt
```

## Resultado HTTP local

Teste local com servidor PHP em `127.0.0.1:8792`, MySQL local e sessao admin sintetica.

### GET direto

```text
GET /app/modules/leads/actions/update_status.php?id=1&status=aprovado
HTTP/1.1 302 Found
Location: /admin/admin.php?msg=metodo_invalido

GET /admin/services/follow_up.php?id=1
HTTP/1.1 302 Found
Location: /admin/leads/leads.php?msg=metodo_invalido
```

Conclusao: GET direto nao executa update/status nem follow-up.

### POST sem CSRF

```text
POST /app/modules/leads/actions/update_status.php
Body: id=1&status=aprovado
HTTP/1.1 403 Forbidden

POST /admin/services/follow_up.php
Body: id=1
HTTP/1.1 403 Forbidden
```

Conclusao: POST sem token CSRF valido e bloqueado antes da acao.

## Observacoes de seguranca

- Tokens CSRF deixaram de trafegar em query string nessas rotas.
- As rotas continuam protegidas por `require_admin()`.
- Status legado passou a ser validado por whitelist antes do update.
- IDs sao validados como inteiros positivos antes de updates.
- A rota de follow-up nao possui whitelist de status porque o status aplicado e fixo (`contactado`) na regra existente.

## Conclusao

Os P0 residuais de CRM legado cobertos neste bloco foram corrigidos. As rotas de status/update legado e follow-up legado nao aceitam mais alteracao por GET e agora exigem POST autenticado com CSRF valido.
