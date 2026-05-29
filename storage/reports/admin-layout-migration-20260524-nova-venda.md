# Migração Layout Admin - Nova Venda

Data: 2026-05-24

## Página migrada

- `admin/vendas/nova_venda.php`

## Motivo

Foi a próxima página prioritária indicada em `storage/reports/admin-layout-audit-20260524.md`.

## Alterações

- Mantida toda a lógica no controlador `admin/vendas/nova_venda.php`.
- Movido o HTML visual para `app/views/admin/vendas/nova_venda_content.php`.
- Removido HTML standalone da página (`<!doctype html>`, `<head>`, `<body>`, Bootstrap local e CSS inline).
- Página passou a usar `app/views/layouts/admin_layout.php`.

## Mantido no controlador

- Autenticação com `require_admin()`.
- CSRF/token existente.
- Validação do modelo novo da tabela `vendas`.
- Busca de clientes.
- Busca de vendedores/captadores em `pessoas`.
- Pré-carregamento de cliente via `cliente_id`.
- Validações de venda.
- Insert dinâmico na tabela `vendas`.
- Recalculo via `recalcular_venda()`.
- Redirect para `admin/vendas/venda_detalhe.php`.
- Flash messages.

## Nova view

- `app/views/admin/vendas/nova_venda_content.php`

## Layout usado

```php
$pageTitle = 'Nova Venda';
$pageSubtitle = 'Criação de venda com lucro real e comissões';
$contentFile = BASE_PATH . '/app/views/admin/vendas/nova_venda_content.php';

require BASE_PATH . '/app/views/layouts/admin_layout.php';
```

## Validação

- Lint individual:
  - `admin/vendas/nova_venda.php`: OK
  - `app/views/admin/vendas/nova_venda_content.php`: OK
- Lint PHP global:
  - 190 arquivos OK
  - 0 erros
  - Relatório: `storage/reports/php-lint-20260524-195818.txt`
- HTTP local:
  - URL: `http://localhost/RG_AUTO_SALES/admin/vendas/nova_venda.php?cliente_id=1`
  - Resultado: `302 Found`
  - Redirect preservou `next=/RG_AUTO_SALES/admin/vendas/nova_venda.php?cliente_id=1`

## Observações

- Nenhuma regra financeira, comissão, lucro real, aprovação, venda ou autenticação foi alterada.
- O texto visual foi normalizado para ASCII na nova view para evitar ampliar problemas de encoding legado.
