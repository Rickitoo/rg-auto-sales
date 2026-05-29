# Migração Layout Admin - CRM Dashboard

Data: 2026-05-24

## Página migrada

- `admin/crm/dashboard.php`

## Motivo

Foi a próxima página prioritária indicada em `storage/reports/admin-layout-audit-20260524-v2.md`.

## Alterações

- Mantida toda a lógica no controlador `admin/crm/dashboard.php`.
- Movido o HTML visual para `app/views/admin/crm/dashboard_content.php`.
- Removido HTML standalone (`<!doctype html>`, `<head>`, `<body>` e bloco `<style>`).
- Página passou a usar `app/views/layouts/admin_layout.php`.
- Estilos específicos de dashboard CRM foram movidos para `public/assets/css/admin-modern.css`.

## Mantido no controlador

- Autenticação com `require_admin()`.
- Helpers CRM locais.
- Detecção de tabelas/colunas.
- Queries de leads, vendas, follow-ups e funil.
- Cálculos de urgência, pendências e status.
- Preparação de listas recentes.
- Links e dados operacionais sem alteração.

## Layout usado

```php
$pageTitle = 'CRM Dashboard';
$pageSubtitle = 'Visão central da operação comercial';
$contentFile = BASE_PATH . '/app/views/admin/crm/dashboard_content.php';

require BASE_PATH . '/app/views/layouts/admin_layout.php';
```

## Validação

- Lint individual:
  - `admin/crm/dashboard.php`: OK
  - `app/views/admin/crm/dashboard_content.php`: OK
- Lint PHP global:
  - 193 arquivos OK
  - 0 erros
  - Relatório: `storage/reports/php-lint-20260524-201525.txt`
- HTTP local:
  - URL: `http://localhost/RG_AUTO_SALES/admin/crm/dashboard.php`
  - Resultado: `302 Found`
  - Redirect preservou `next=/RG_AUTO_SALES/admin/crm/dashboard.php`

## Observações

- Nenhuma regra CRM, vendas, autenticação ou cálculo operacional foi alterado.
- O único estilo inline mantido na view é a largura dinâmica da barra do funil (`width:%`), por ser dado calculado de visualização.
