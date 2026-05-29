# Migração Layout Admin - Editar Venda

Data: 2026-05-24

## Página migrada

- `admin/vendas/editar_venda.php`

## Motivo

Foi a próxima página admin prioritária ainda standalone, conforme `storage/reports/admin-layout-audit-20260524.md`, após `nova_venda.php` e `venda_detalhe.php`.

## Alterações

- Mantida toda a lógica no controlador `admin/vendas/editar_venda.php`.
- Movido o HTML visual para `app/views/admin/vendas/editar_venda_content.php`.
- Removido HTML standalone (`<!doctype html>`, `<head>`, `<body>`, Bootstrap local e CSS inline).
- Página passou a usar `app/views/layouts/admin_layout.php`.

## Mantido no controlador

- Autenticação com `require_admin()`.
- CSRF/token existente.
- Busca da venda por ID.
- Detecção dinâmica de colunas.
- Carregamento de pessoas/vendedores/captadores.
- Validações.
- Update dinâmico em `vendas`.
- Recalculo via `recalcular_venda()`.
- Avisos de lucro negativo ou abaixo do mínimo.
- Flash messages.
- Regras financeiras e de status sem alteração.

## Layout usado

```php
$pageTitle = 'Editar Venda';
$pageSubtitle = 'Atualização comercial e financeira da venda';
$contentFile = BASE_PATH . '/app/views/admin/vendas/editar_venda_content.php';

require BASE_PATH . '/app/views/layouts/admin_layout.php';
```

## Validação

- Lint individual:
  - `admin/vendas/editar_venda.php`: OK
  - `app/views/admin/vendas/editar_venda_content.php`: OK
- Lint PHP global:
  - 192 arquivos OK
  - 0 erros
  - Relatório: `storage/reports/php-lint-20260524-200812.txt`
- HTTP local:
  - URL: `http://localhost/RG_AUTO_SALES/admin/vendas/editar_venda.php?id=1`
  - Resultado: `302 Found`
  - Redirect preservou `next=/RG_AUTO_SALES/admin/vendas/editar_venda.php?id=1`

## Observações

- Nenhuma regra financeira, CRM, vendas ou autenticação foi alterada.
- O HTML visual foi normalizado para o novo Layout Global Admin.
