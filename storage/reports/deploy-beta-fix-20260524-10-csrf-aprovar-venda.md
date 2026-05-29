# Correção Deploy Beta 10 - CSRF em aprovar_venda.php

Data: 2026-05-25  
Arquivo principal: `admin/vendas/aprovar_venda.php`  
Bloqueador tratado: aprovação de venda via GET sem CSRF.

## Correção aplicada

- `admin/vendas/aprovar_venda.php` agora aceita apenas `POST`.
- A sessão/admin é validada antes da ação com `require_admin()` e checagem de role `admin`.
- GET direto é redirecionado para `admin/aprovacoes.php?msg=metodo_invalido`.
- `csrf_token` é validado com `hash_equals()` antes de qualquer update.
- O ID da venda passou de `$_GET['id']` para `$_POST['id']`.
- A regra de aprovação existente foi preservada:
  - `UPDATE vendas SET precisa_aprovacao = 0, status='APROVADO' WHERE id=?`
  - redirect final para `admin/aprovacoes.php`.
- Nenhum cálculo financeiro foi alterado.

## Chamadas atualizadas para POST

- `admin/aprovacoes.php`
  - Link `Aprovar` substituído por formulário `POST`.
  - Formulário inclui `csrf_input()`.
  - ID da venda enviado por campo hidden.

## Validações executadas

### Lint individual

- `C:\xampp\php\php.exe -l admin/vendas/aprovar_venda.php`
- `C:\xampp\php\php.exe -l admin/aprovacoes.php`

Resultado: sem erros de sintaxe.

### Lint PHP global

Comando:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1
```

Resultado:

- Arquivos OK: 198
- Arquivos com erro: 0
- Relatório: `storage/reports/php-lint-20260525-001625.txt`

### Busca por links GET antigos

Comando:

```powershell
rg -n "aprovar_venda\.php\?id=|aprovar_venda\.php\?" admin app public -S
```

Resultado: nenhum link GET direto restante para `aprovar_venda.php?id=...`.

### HTTP local no XAMPP

Arquivos sincronizados para `C:\xampp\htdocs\RG_AUTO_SALES`.

Testes sem sessão autenticada:

- GET direto:
  - URL: `http://localhost/RG_AUTO_SALES/admin/vendas/aprovar_venda.php?id=1`
  - Resultado: `302`
  - Redirect: `auth/login.php?next=%2FRG_AUTO_SALES%2Fadmin%2Fvendas%2Faprovar_venda.php%3Fid%3D1`
- POST sem CSRF:
  - URL: `http://localhost/RG_AUTO_SALES/admin/vendas/aprovar_venda.php`
  - Body: `id=1`
  - Resultado: `302`
  - Redirect: `auth/login.php?next=%2FRG_AUTO_SALES%2Fadmin%2Fvendas%2Faprovar_venda.php`

Observação: sem sessão admin local, o teste HTTP para antes em `require_admin()`. Em sessão admin autenticada, o código agora valida método `POST` e `csrf_token` com `hash_equals()` antes de executar o update. O fluxo com CSRF válido mantém a regra anterior de aprovação.

## Estado final

Bloqueador P0 de aprovação via GET corrigido. A aprovação agora depende de POST autenticado com CSRF válido, mantendo a regra de negócio existente.
