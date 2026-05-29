# Migração Layout Global Admin - Lead Detalhe

Data: 2026-05-24

## Página migrada

- `admin/leads/lead_detalhe.php`

## Estrutura criada

- `app/views/admin/leads/lead_detalhe_content.php`

## O que foi mantido no controlador

- Autenticação com `require_admin()`.
- Validação de perfil admin existente.
- Leitura do `id` via `GET`.
- Validação de ID inválido.
- Query preparada `SELECT * FROM leads WHERE id=? LIMIT 1`.
- Tratamento de lead não encontrado.

Observação: esta rota legada não tinha no código atual histórico, WhatsApp, follow-ups ou ações de venda. Nenhuma dessas regras foi removida; simplesmente não existiam nesta página. Foi adicionado apenas um link visual para abrir o detalhe CRM canônico (`ver_lead.php`) sem alterar a lógica da rota.

## O que foi movido para a view

- Cabeçalho visual do detalhe.
- Botão de voltar para `admin/funil.php`.
- Exibição dos campos:
  - Nome
  - Telefone
  - Email
  - Tipo
  - Status
  - Carro
  - Criado em
  - Mensagem

## CSS

Arquivo atualizado:

- `public/assets/css/admin-modern.css`

Classes adicionadas:

- `.lead-legacy-detail-grid`
- `.lead-message-panel`
- `.lead-message-block`

## Layout

No final do controlador foi aplicado:

```php
$pageTitle = 'Detalhe do Lead';
$pageSubtitle = 'Acompanhamento completo da oportunidade';
$contentFile = BASE_PATH . '/app/views/admin/leads/lead_detalhe_content.php';

require BASE_PATH . '/app/views/layouts/admin_layout.php';
```

## Validação

Varredura da rota migrada:

- Sem `<!doctype>`, `<html>`, `<head>`, `<body>`.
- Sem `layout_top.php` ou `layout_bottom.php`.
- Sem bloco `<style>`.
- Sem atributo `style=`.

Lint individual:

- `C:\xampp\php\php.exe -l admin/leads/lead_detalhe.php`
  - Resultado: sem erros de sintaxe.
- `C:\xampp\php\php.exe -l app/views/admin/leads/lead_detalhe_content.php`
  - Resultado: sem erros de sintaxe.

Lint PHP global:

- `powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1`
  - Resultado: `Arquivos OK: 198`, `Arquivos com erro: 0`.
  - Relatório gerado: `storage/reports/php-lint-20260524-230827.txt`.

Teste HTTP local:

- URL testada: `http://localhost/RG_AUTO_SALES/admin/leads/lead_detalhe.php?id=123`
- Resultado: `HTTP/1.1 302 Found`
- Redirect confirmado:
  - `/RG_AUTO_SALES/auth/login.php?next=%2FRG_AUTO_SALES%2Fadmin%2Fleads%2Flead_detalhe.php%3Fid%3D123`

## Observações

- A página agora usa o Layout Global Admin.
- O HTML standalone e Bootstrap CDN direto foram removidos.
- O módulo Leads admin canônico ficou migrado nas rotas principais:
  - `admin/leads/leads.php`
  - `admin/leads/listar_leads.php`
  - `admin/leads/ver_lead.php`
  - `admin/leads/lead_detalhe.php`
