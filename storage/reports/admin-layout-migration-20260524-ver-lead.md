# Migração Layout Global Admin - Detalhe do Lead

Data: 2026-05-24

## Página migrada

- `admin/leads/ver_lead.php`

Página escolhida como próxima prioridade do módulo Leads por concentrar:

- dados do lead;
- status;
- envio de mensagem;
- histórico de mensagens;
- atualização de `ultima_interacao`;
- agendamento de `proximo_followup`;
- links WhatsApp, venda e CRM.

## Estrutura criada

- `app/views/admin/leads/ver_lead_content.php`

## O que foi mantido no controlador

- Autenticação com `require_admin()`.
- Validação de perfil admin existente.
- Busca do lead por `id`.
- Mapeamento de status visual existente.
- Processamento de `POST` para mensagens.
- Insert na tabela `mensagens`.
- Update do lead com `ultima_interacao` e `proximo_followup`.
- Redirect de retorno para `admin/leads/ver_lead.php?id=...`.
- Query do histórico de mensagens.
- Preparação do telefone para WhatsApp.

Observação: a página original não tinha token CSRF explícito. Nenhum fluxo de CSRF foi removido ou alterado.

## O que foi movido para a view

- Cabeçalho visual do detalhe do lead.
- Cards com dados principais.
- Botões de WhatsApp, fechar venda e CRM/follow-up.
- Chat/histórico de mensagens.
- Formulário visual de envio de mensagem.
- Botões de mensagens rápidas.
- Script local para preencher mensagem rápida e rolar o chat.

## CSS

Arquivo atualizado:

- `public/assets/css/admin-modern.css`

Classes adicionadas:

- `.lead-detail-page`
- `.lead-detail-grid`
- `.lead-detail-layout`
- `.lead-status-badge`
- `.lead-status-*`
- `.lead-chat`
- `.lead-message`
- `.lead-message-enviada`
- `.lead-message-recebida`
- `.lead-message-form`
- `.lead-quick-buttons`

## Validação

Lint individual:

- `C:\xampp\php\php.exe -l admin/leads/ver_lead.php`
  - Resultado: sem erros de sintaxe.
- `C:\xampp\php\php.exe -l app/views/admin/leads/ver_lead_content.php`
  - Resultado: sem erros de sintaxe.

Lint PHP global:

- `powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1`
  - Resultado: `Arquivos OK: 196`, `Arquivos com erro: 0`.
  - Relatório gerado: `storage/reports/php-lint-20260524-204200.txt`.

Teste HTTP local:

- URL testada: `http://localhost/RG_AUTO_SALES/admin/leads/ver_lead.php?id=123`
- Resultado: `HTTP/1.1 302 Found`
- Redirect confirmado:
  - `/RG_AUTO_SALES/auth/login.php?next=%2FRG_AUTO_SALES%2Fadmin%2Fleads%2Fver_lead.php%3Fid%3D123`

## Observações

- Nenhuma regra CRM, follow-up, mensagem ou venda foi reescrita.
- A página não usa mais HTML standalone, `<style>`, `style=`, `layout_top.php` ou `layout_bottom.php`.
- Próximas páginas do módulo Leads ainda pendentes:
  - `admin/leads/listar_leads.php`
  - `admin/leads/lead_detalhe.php`
