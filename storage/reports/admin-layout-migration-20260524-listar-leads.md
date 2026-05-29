# Migração Layout Global Admin - Listar Leads

Data: 2026-05-24

## Página migrada

- `admin/leads/listar_leads.php`

## Estrutura criada

- `app/views/admin/leads/listar_leads_content.php`

## O que foi mantido no controlador

- Autenticação com `require_admin()`.
- Validação de perfil admin existente.
- Query original de leads com `LEFT JOIN carros`.
- Tratamento de erro da query.
- Ordem original por `l.id DESC`.

Observação: a página original não tinha filtros server-side nem contadores PHP explícitos; a busca existente era client-side em JavaScript e foi preservada na view.

## O que foi movido para a view

- Título visual e ações de navegação.
- Campo de pesquisa client-side.
- Tabela de leads.
- Status visual.
- Links WhatsApp.
- Links para detalhe do lead.
- Ações de status existentes.
- Ação de venda existente.
- Script de pesquisa local da tabela.

## CSS

Arquivo atualizado:

- `public/assets/css/admin-modern.css`

O bloco `<style>` antigo foi removido da página e substituído por classes globais:

- `.leads-list-page`
- `.leads-list-search`
- `.leads-list-table-wrap`
- `.legacy-lead-status`
- `.legacy-lead-status-*`
- `.legacy-lead-actions`
- `.legacy-lead-btn`
- `.legacy-lead-btn-*`

## Layout

No final do controlador foi aplicado:

```php
$pageTitle = 'Listar Leads';
$pageSubtitle = 'Gestão e acompanhamento de oportunidades comerciais';
$contentFile = BASE_PATH . '/app/views/admin/leads/listar_leads_content.php';

require BASE_PATH . '/app/views/layouts/admin_layout.php';
```

## Validação

Varredura da rota migrada:

- Sem `<!doctype>`, `<html>`, `<head>`, `<body>`.
- Sem `layout_top.php` ou `layout_bottom.php`.
- Sem bloco `<style>`.
- Sem atributo `style=`.

Lint individual:

- `C:\xampp\php\php.exe -l admin/leads/listar_leads.php`
  - Resultado: sem erros de sintaxe.
- `C:\xampp\php\php.exe -l app/views/admin/leads/listar_leads_content.php`
  - Resultado: sem erros de sintaxe.

Lint PHP global:

- `powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1`
  - Resultado: `Arquivos OK: 197`, `Arquivos com erro: 0`.
  - Relatório gerado: `storage/reports/php-lint-20260524-230422.txt`.

Teste HTTP local:

- URL testada: `http://localhost/RG_AUTO_SALES/admin/leads/listar_leads.php`
- Resultado: `HTTP/1.1 302 Found`
- Redirect confirmado:
  - `/RG_AUTO_SALES/auth/login.php?next=%2FRG_AUTO_SALES%2Fadmin%2Fleads%2Flistar_leads.php`

## Observações

- Nenhuma regra CRM foi reescrita.
- Os links relativos originais da listagem foram preservados para manter compatibilidade:
  - `ver_lead.php?id=...`
  - `leads_status.php?id=...&s=...`
- O link de venda foi preservado com o parâmetro original `id=...`.
- Próxima página crítica do módulo Leads:
  - `admin/leads/lead_detalhe.php`
