# Correção Deploy Beta 09 - CSRF em pagar_venda.php

Data: 2026-05-25  
Arquivo principal: `admin/vendas/pagar_venda.php`  
Bloqueador tratado: pagamento de venda via GET sem CSRF.

## Correção aplicada

- `admin/vendas/pagar_venda.php` agora aceita apenas `POST`.
- A validação CSRF ocorre antes de buscar/recalcular/alterar a venda.
- O token é validado com `hash_equals()`.
- O ID da venda passou de `$_GET['id']` para `$_POST['id']`.
- A regra financeira existente foi preservada:
  - busca da venda por ID;
  - bloqueio para venda já paga;
  - chamada existente de `recalcular_venda()`;
  - atualização dos campos financeiros/status existentes;
  - redirect final para `admin/dashboard.php?msg=pago_ok`.

## Chamadas atualizadas para POST

- `app/views/admin/dashboard/dashboard_content.php`
  - Link `Marcar pago` substituído por formulário `POST` com `csrf_input()`.
- `public/dashboard.php`
  - Link legado `Marcar pago` substituído por formulário `POST` com `csrf_input()`.
- `admin/painel_inteligente.php`
  - Link `Pagar` substituído por formulário `POST` com `csrf_input()`.

## Validações executadas

### Lint individual

- `C:\xampp\php\php.exe -l admin/vendas/pagar_venda.php`
- `C:\xampp\php\php.exe -l app/views/admin/dashboard/dashboard_content.php`
- `C:\xampp\php\php.exe -l public/dashboard.php`
- `C:\xampp\php\php.exe -l admin/painel_inteligente.php`

Resultado: sem erros de sintaxe.

### Lint PHP global

Comando:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1
```

Resultado:

- Arquivos OK: 198
- Arquivos com erro: 0
- Relatório: `storage/reports/php-lint-20260525-000805.txt`

### Busca por links GET antigos

Comando:

```powershell
rg -n "pagar_venda\.php\?id=|pagar_venda\.php\?" admin app public -S
```

Resultado: nenhum link GET direto restante para `pagar_venda.php?id=...`.

### HTTP local no XAMPP

Arquivos sincronizados para `C:\xampp\htdocs\RG_AUTO_SALES`.

Testes sem sessão autenticada:

- GET direto:
  - URL: `http://localhost/RG_AUTO_SALES/admin/vendas/pagar_venda.php?id=1`
  - Resultado: `302`
  - Redirect: `auth/login.php?next=%2FRG_AUTO_SALES%2Fadmin%2Fvendas%2Fpagar_venda.php%3Fid%3D1`
- POST sem CSRF:
  - URL: `http://localhost/RG_AUTO_SALES/admin/vendas/pagar_venda.php`
  - Body: `id=1`
  - Resultado: `302`
  - Redirect: `auth/login.php?next=%2FRG_AUTO_SALES%2Fadmin%2Fvendas%2Fpagar_venda.php`

Observação: sem sessão admin local, o teste HTTP para antes em `require_admin()`. Para uma sessão admin autenticada, o código agora falha antes de qualquer cálculo/update quando o método não é POST ou o CSRF é inválido.

## Estado final

Bloqueador P0 de pagamento via GET corrigido. O fluxo legítimo permanece por POST com CSRF válido, mantendo os cálculos e regras financeiras existentes.
