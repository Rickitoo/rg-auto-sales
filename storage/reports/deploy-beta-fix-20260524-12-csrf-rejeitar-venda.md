# Correção Deploy Beta 12 - CSRF em rejeitar_venda.php

Data: 2026-05-25  
Arquivo principal: `admin/vendas/rejeitar_venda.php`  
Bloqueador tratado: rejeição de venda via GET sem CSRF.

## Rota escolhida

Com base em `storage/reports/deploy-beta-csrf-audit-20260524.md`, a próxima rota P0 real existente após `pagar_venda.php` e `aprovar_venda.php` é:

- `admin/vendas/rejeitar_venda.php`

Foi confirmado que o arquivo existe no projeto e tinha chamada GET em `admin/aprovacoes.php`.

## Correção aplicada

- `admin/vendas/rejeitar_venda.php` agora aceita apenas `POST`.
- A sessão/admin é validada antes da ação com `require_admin()` e checagem de role `admin`.
- GET direto é redirecionado para `admin/aprovacoes.php?msg=metodo_invalido`.
- `csrf_token` é validado com `hash_equals()` antes de qualquer update.
- O ID da venda passou de `$_GET['id']` para `$_POST['id']`.
- A regra de rejeição existente foi preservada:
  - `UPDATE vendas SET status='REJEITADO' WHERE id=?`
  - redirect final para `admin/aprovacoes.php`.
- Nenhum cálculo financeiro foi alterado.

## Chamadas atualizadas para POST

- `admin/aprovacoes.php`
  - Link `Rejeitar` substituído por formulário `POST`.
  - Formulário inclui `csrf_input()`.
  - ID da venda enviado por campo hidden.

## Validações executadas

### Lint individual

- `C:\xampp\php\php.exe -l admin/vendas/rejeitar_venda.php`
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
- Relatório: `storage/reports/php-lint-20260525-002606.txt`

### Busca por links GET antigos

Comando:

```powershell
rg -n "rejeitar_venda\.php\?id=|rejeitar_venda\.php\?" admin app public -S
```

Resultado: nenhum link GET direto restante para `rejeitar_venda.php?id=...`.

### HTTP local no XAMPP

Arquivos sincronizados para `C:\xampp\htdocs\RG_AUTO_SALES`.

Testes sem sessão autenticada:

- GET direto:
  - URL: `http://localhost/RG_AUTO_SALES/admin/vendas/rejeitar_venda.php?id=1`
  - Resultado: `302`
  - Redirect: `auth/login.php?next=%2FRG_AUTO_SALES%2Fadmin%2Fvendas%2Frejeitar_venda.php%3Fid%3D1`
- POST sem CSRF:
  - URL: `http://localhost/RG_AUTO_SALES/admin/vendas/rejeitar_venda.php`
  - Body: `id=1`
  - Resultado: `302`
  - Redirect: `auth/login.php?next=%2FRG_AUTO_SALES%2Fadmin%2Fvendas%2Frejeitar_venda.php`

Observação: sem sessão admin local, o teste HTTP para antes em `require_admin()`. Em sessão admin autenticada, o código agora valida método `POST` e `csrf_token` com `hash_equals()` antes de executar o update. O fluxo com CSRF válido mantém a regra anterior de rejeição.

## Estado final

Bloqueador P0 de rejeição via GET corrigido. A rejeição agora depende de POST autenticado com CSRF válido, mantendo a regra de negócio existente.
