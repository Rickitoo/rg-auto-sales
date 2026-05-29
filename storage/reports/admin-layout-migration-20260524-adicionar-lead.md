# Migração Layout Global Admin - Adicionar Lead

Data: 2026-05-24

## Página migrada

- `admin/leads/adicionar_lead.php`

## Estrutura criada

- `app/views/admin/leads/adicionar_lead_content.php`

## O que foi mantido no controlador

- Autenticação com `require_admin()`.
- Validação de perfil admin existente.
- Leitura dos campos `nome`, `telefone` e `carro_id`.
- Validação de nome e telefone obrigatórios.
- Insert original na tabela `leads`.
- Status inicial `novo`.
- Redirect original para `admin/leads/listar_leads.php`.
- Query de carros para seleção.

## O que foi limpo/migrado

- A página passou a usar o Layout Global Admin.
- O HTML visual do formulário foi movido para `app/views/admin/leads/adicionar_lead_content.php`.
- O inline style antigo da mensagem de erro (`style="color:red"`) foi removido.
- A mensagem de erro agora usa `.rg-alert.rg-alert-danger`.

## CSS

Arquivo atualizado:

- `public/assets/css/admin-modern.css`

Classe adicionada:

- `.lead-form-page`

## Validação

Varredura da rota migrada:

- Sem `<!doctype>`, `<html>`, `<head>`, `<body>`.
- Sem `layout_top.php` ou `layout_bottom.php`.
- Sem bloco `<style>`.
- Sem atributo `style=`.

Lint individual:

- `C:\xampp\php\php.exe -l admin/leads/adicionar_lead.php`
  - Resultado: sem erros de sintaxe.
- `C:\xampp\php\php.exe -l app/views/admin/leads/adicionar_lead_content.php`
  - Resultado: sem erros de sintaxe.

Lint PHP global:

- `powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1`
  - Resultado: `Arquivos OK: 199`, `Arquivos com erro: 0`.
  - Relatório gerado: `storage/reports/php-lint-20260524-231317.txt`.

Teste HTTP local:

- URL testada: `http://localhost/RG_AUTO_SALES/admin/leads/adicionar_lead.php`
- Resultado: `HTTP/1.1 302 Found`
- Redirect confirmado:
  - `/RG_AUTO_SALES/auth/login.php?next=%2FRG_AUTO_SALES%2Fadmin%2Fleads%2Fadicionar_lead.php`

## Observações

- Nenhuma regra de insert, validação ou redirect foi reescrita.
- A rota agora está alinhada às demais páginas do módulo Leads migradas para o Layout Global Admin.
