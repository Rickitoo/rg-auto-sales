# Migração Layout Admin - CRM Inbox

Data: 2026-05-24

## Página migrada

- `admin/crm/inbox.php`

## Motivo

Foi a próxima página CRM prioritária indicada em `storage/reports/admin-layout-audit-20260524-v2.md`, após `admin/crm/dashboard.php`.

## Alterações

- Mantida toda a lógica no controlador `admin/crm/inbox.php`.
- Movido o HTML visual para `app/views/admin/crm/inbox_content.php`.
- Removido HTML standalone (`<!doctype html>`, `<head>`, `<body>` e bloco `<style>`).
- Página passou a usar `app/views/layouts/admin_layout.php`.
- Estilos da Inbox CRM foram movidos para `public/assets/css/admin-modern.css` com classes prefixadas `crm-*`.

## Mantido no controlador

- Autenticação com `require_admin()`.
- Helpers CRM.
- Criação/verificação da tabela `lead_followups`.
- POST de alteração de status.
- POST de follow-up.
- Filtros `q`, `status` e seleção por `id`.
- Queries de leads e follow-ups.
- Ordenação por atenção/prioridade.
- Mensagens inteligentes de WhatsApp.
- Links WhatsApp e URLs operacionais.
- Regras existentes de CRM sem alteração.

## Layout usado

```php
$pageTitle = 'CRM Inbox';
$pageSubtitle = 'Leads, follow-ups e mensagens comerciais';
$contentFile = BASE_PATH . '/app/views/admin/crm/inbox_content.php';

require BASE_PATH . '/app/views/layouts/admin_layout.php';
```

## Validação

- Lint individual:
  - `admin/crm/inbox.php`: OK
  - `app/views/admin/crm/inbox_content.php`: OK
- Lint PHP global:
  - 194 arquivos OK
  - 0 erros
  - Relatório: `storage/reports/php-lint-20260524-202236.txt`
- HTTP local:
  - URL: `http://localhost/RG_AUTO_SALES/admin/crm/inbox.php?id=1&status=novo&q=teste`
  - Resultado: `302 Found`
  - Redirect preservou `next=/RG_AUTO_SALES/admin/crm/inbox.php?id=1&status=novo&q=teste`

## Observações

- Nenhuma regra CRM, venda, autenticação, follow-up ou WhatsApp foi alterada.
- A Inbox mantém o layout interno de duas colunas dentro do Layout Global Admin.
