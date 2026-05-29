# Migração Layout Global Admin - Leads principal

Data: 2026-05-24

## Página migrada

- `admin/leads/leads.php`

Página priorizada conforme a auditoria v3:

- `storage/reports/admin-layout-audit-20260524-v3.md`

## Estrutura criada

- `app/views/admin/leads/leads_content.php`

## O que foi alterado

- O controlador `admin/leads/leads.php` manteve:
  - autenticação com `require_admin()`;
  - validação de perfil admin existente;
  - helpers e função de mensagem WhatsApp;
  - detecção de colunas de follow-up;
  - filtros `status` e `q`;
  - contadores por status;
  - query principal com `lead_score`;
  - alerta de follow-up pendente;
  - links para WhatsApp, venda, detalhe do lead e CRM.
- O HTML visual foi movido para `app/views/admin/leads/leads_content.php`.
- O controlador agora define:
  - `$pageTitle = 'Leads';`
  - `$pageSubtitle = 'Gestao de oportunidades, follow-ups e conversao comercial';`
  - `$contentFile = BASE_PATH . '/app/views/admin/leads/leads_content.php';`
  - `require BASE_PATH . '/app/views/layouts/admin_layout.php';`
- O HTML standalone antigo foi removido da rota.
- O Bootstrap CDN direto foi removido da página, ficando sob o Layout Global Admin.
- O estilo inline de destaque do lead com score alto foi substituído por classe CSS.

## CSS

Arquivo atualizado:

- `public/assets/css/admin-modern.css`

Classes adicionadas:

- `.leads-page`
- `.leads-filter-grid`
- `.lead-row-hot`

## Validação

Lint individual:

- `C:\xampp\php\php.exe -l admin/leads/leads.php`
  - Resultado: sem erros de sintaxe.
- `C:\xampp\php\php.exe -l app/views/admin/leads/leads_content.php`
  - Resultado: sem erros de sintaxe.

Lint PHP global:

- `powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1`
  - Resultado: `Arquivos OK: 195`, `Arquivos com erro: 0`.
  - Relatório gerado: `storage/reports/php-lint-20260524-203736.txt`.

Teste HTTP local:

- URL testada: `http://localhost/RG_AUTO_SALES/admin/leads/leads.php?q=test&status=novo`
- Resultado: `HTTP/1.1 302 Found`
- Redirect confirmado:
  - `/RG_AUTO_SALES/auth/login.php?next=%2FRG_AUTO_SALES%2Fadmin%2Fleads%2Fleads.php%3Fq%3Dtest%26status%3Dnovo`

## Observações

- Nenhuma regra de negócio de leads, CRM, follow-up ou WhatsApp foi reescrita.
- A rota continua preservando filtros na query string durante redirect para login.
- Próximas páginas recomendadas do módulo Leads:
  - `admin/leads/listar_leads.php`
  - `admin/leads/ver_lead.php`
  - `admin/leads/lead_detalhe.php`
