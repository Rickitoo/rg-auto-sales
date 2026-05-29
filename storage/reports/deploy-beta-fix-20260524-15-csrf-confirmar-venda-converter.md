# Correção Deploy Beta 15 - CSRF em confirmar_venda.php e vendedor_converter.php

Data: 2026-05-25  
Arquivos principais:

- `admin/vendas/confirmar_venda.php`
- `admin/vendas/vendedor_converter.php`

Bloqueadores tratados:

- confirmação/criação de venda a partir de lead sem CSRF;
- conversão de pedido de vendedor em cliente/venda via GET sem CSRF.

## Correções aplicadas

### `admin/vendas/confirmar_venda.php`

- Agora aceita apenas `POST`.
- GET direto redireciona para `admin/funil.php?msg=metodo_invalido`.
- Valida sessão/admin antes da ação com `require_admin()` e role `admin`.
- Valida `csrf_token` com `hash_equals()` antes de buscar lead/carro ou executar venda.
- `lead_id` agora vem de `$_POST['lead_id']`.
- O fluxo foi preservado em duas fases por POST:
  - POST inicial com `lead_id` abre a tela de confirmação;
  - POST final com `valor_venda`, `valor_proprietario` e `forma_pagamento` cria a venda.
- O formulário final agora inclui `csrf_input()`.
- Regras preservadas:
  - validação de lead, cliente e carro;
  - cálculo de lucro e comissões;
  - criação de cliente quando necessário;
  - insert em `vendas`;
  - update do carro para `vendido`;
  - redirect para `admin/vendas/venda_detalhe.php?id=...`.

### `admin/vendas/vendedor_converter.php`

- Agora aceita apenas `POST`.
- GET direto redireciona para `admin/vendas/vendedores_pedidos.php?msg=metodo_invalido`.
- Valida sessão/admin antes da ação com `require_admin()` e role `admin`.
- Valida `csrf_token` com `hash_equals()` antes de criar cliente/venda.
- `id` agora vem de `$_POST['id']`.
- Regras preservadas:
  - busca do pedido de vendedor;
  - exigência de status `Aprovado`;
  - criação de cliente;
  - criação de venda;
  - atualização do pedido para `Publicado`;
  - redirect para detalhe da venda.

## Chamadores atualizados

- `admin/vendas/vendedores_pedidos.php`
  - Link GET `vendedor_converter.php?id=...` substituído por formulário POST com `csrf_input()`.

- `admin/funil.php`
  - O redirect antigo do fluxo de lead fechado foi substituído por criação de formulário POST dinâmico no navegador.
  - O POST inclui `csrf_token` e `lead_id`.

- `app/modules/leads/lead_move.php`
  - O payload JSON deixou de retornar `confirmar_venda.php?lead_id=...`.
  - Agora retorna `redirect` para `admin/vendas/confirmar_venda.php` e `lead_id` separado.
  - Também foi removido um `echo json_encode` duplicado antes do redirect, para manter resposta JSON válida.

## Validações executadas

### Lint individual

- `C:\xampp\php\php.exe -l admin/vendas/confirmar_venda.php`
- `C:\xampp\php\php.exe -l admin/vendas/vendedor_converter.php`
- `C:\xampp\php\php.exe -l admin/vendas/vendedores_pedidos.php`
- `C:\xampp\php\php.exe -l admin/funil.php`
- `C:\xampp\php\php.exe -l app/modules/leads/lead_move.php`

Resultado: sem erros de sintaxe.

### Lint PHP global

Comando:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1
```

Resultado:

- Arquivos OK: 198
- Arquivos com erro: 0
- Relatório: `storage/reports/php-lint-20260525-005820.txt`

### Busca por GET antigo

Comando:

```powershell
rg -n "confirmar_venda\.php\?lead_id=|confirmar_venda\.php\?|vendedor_converter\.php\?id=|vendedor_converter\.php\?" admin app public -S
```

Resultado: nenhuma chamada GET antiga encontrada.

### HTTP local no XAMPP

Arquivos sincronizados para `C:\xampp\htdocs\RG_AUTO_SALES`.

Testes sem sessão autenticada:

- `confirmar_venda.php` GET direto:
  - URL: `http://localhost/RG_AUTO_SALES/admin/vendas/confirmar_venda.php?lead_id=1`
  - Resultado: `302`
  - Redirect: `auth/login.php?next=%2FRG_AUTO_SALES%2Fadmin%2Fvendas%2Fconfirmar_venda.php%3Flead_id%3D1`
- `confirmar_venda.php` POST sem sessão/CSRF:
  - URL: `http://localhost/RG_AUTO_SALES/admin/vendas/confirmar_venda.php`
  - Body: `lead_id=1`
  - Resultado: `302`
  - Redirect: `auth/login.php?next=%2FRG_AUTO_SALES%2Fadmin%2Fvendas%2Fconfirmar_venda.php`
- `vendedor_converter.php` GET direto:
  - URL: `http://localhost/RG_AUTO_SALES/admin/vendas/vendedor_converter.php?id=1`
  - Resultado: `302`
  - Redirect: `auth/login.php?next=%2FRG_AUTO_SALES%2Fadmin%2Fvendas%2Fvendedor_converter.php%3Fid%3D1`
- `vendedor_converter.php` POST sem sessão/CSRF:
  - URL: `http://localhost/RG_AUTO_SALES/admin/vendas/vendedor_converter.php`
  - Body: `id=1`
  - Resultado: `302`
  - Redirect: `auth/login.php?next=%2FRG_AUTO_SALES%2Fadmin%2Fvendas%2Fvendedor_converter.php`

Observação: sem sessão admin local, os testes HTTP param em `require_admin()`. Em sessão admin autenticada, as rotas agora exigem método `POST` e `csrf_token` válido com `hash_equals()` antes de executar qualquer criação/conversão de venda.

## Estado final

Bloqueadores P0 de `confirmar_venda.php` e `vendedor_converter.php` corrigidos em bloco. As rotas agora dependem de POST autenticado com CSRF válido, preservando regras de venda, lead, carro, comissões e redirects.
