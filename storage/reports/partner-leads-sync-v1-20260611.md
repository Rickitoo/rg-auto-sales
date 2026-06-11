# Partner Leads Sync v1 - 2026-06-11

## Migration criada

- `storage/migrations/20260611_partner_leads_sync.sql`
  - Adiciona `crm_lead_id`, `sincronizado_crm` e `sincronizado_em` em `parceiro_leads`.
  - Adiciona indices para `crm_lead_id` e `sincronizado_crm`.
  - Tenta criar FK opcional `fk_parceiro_leads_crm_lead` para `leads(id)` quando a tabela `leads` existe e a constraint ainda nao existe.

## Arquivos criados

- `admin/parceiros/lead_sync.php`
- `storage/migrations/20260611_partner_leads_sync.sql`
- `storage/reports/partner-leads-sync-v1-20260611.md`

## Arquivos alterados

- `admin/parceiros/leads.php`
  - Adicionada coluna `CRM`.
  - Adicionados badges `Sincronizado` e `Pendente`.
  - Adicionadas acoes `Enviar para CRM` e `Ver no CRM`.
- `admin/parceiros/lead_detalhe.php`
  - Adicionado estado de sincronizacao CRM.
  - Adicionados botoes `Enviar para CRM` e `Ver Lead no CRM`.
  - Adicionados campos `Lead CRM` e `Sincronizado em`.
- `admin/parceiros/performance.php`
  - Adicionados cards `Leads sincronizados no CRM` e `Leads pendentes de sync`.

## Campos detectados na tabela leads

- `id int(11)`
- `tipo enum('testdrive','venda','importacao','consulta','orcamento')`
- `nome varchar(120)`
- `telefone varchar(30)`
- `email varchar(120)`
- `mensagem text`
- `marca varchar(80)`
- `modelo varchar(80)`
- `ano int(11)`
- `carro_id int(11)`
- `origem enum('site','ig','fb','wa','outro','importacao')`
- `status enum('novo','contactado','qualificado','agendado','orcamento','aguardando_opcoes','negociacao','pagamento','embarcado','em_transito','desalfandegamento','entregue','fechado','perdido')`
- `criado_em timestamp`
- `notas text`
- `proximo_contacto datetime`
- `atualizado_em timestamp`
- `last_contact datetime`
- `ultimo_contacto datetime`
- `proximo_followup datetime`
- `tentativas_followup int(11)`
- `created_at datetime`
- `whatsapp_id varchar(50)`
- `last_message text`
- `stage varchar(30)`
- `updated_at datetime`
- `ultima_interacao datetime`

Observacao: o enum `origem` local nao possui `parceiro`; o sync usa fallback seguro para `outro` e grava a referencia ao parceiro em `mensagem`/`notas`.

## Testes feitos

- `php -l`:
  - `admin/parceiros/lead_sync.php`
  - `admin/parceiros/leads.php`
  - `admin/parceiros/lead_detalhe.php`
  - `admin/parceiros/performance.php`
- Lint completo do projeto:
  - Script: `scripts/lint-php.ps1`
  - PHP CLI: `C:\xampp\php\php.exe`
  - Resultado: 218 arquivos OK, 0 erros.
  - Relatorio gerado: `storage/reports/php-lint-20260611-033724.txt`
- Migration aplicada localmente:
  - Resultado: `migration ok`.
- Criado parceiro temporario e lead parceiro pendente:
  - `sincronizado_crm = 0`
  - `crm_lead_id = NULL`
- Sincronizacao manual com CRM:
  - `admin/parceiros/lead_sync.php` via POST com CSRF valido.
  - Resultado: lead criado no CRM com ID `35` durante o teste.
  - `parceiro_leads.crm_lead_id` atualizado.
  - `parceiro_leads.sincronizado_crm` atualizado para `1`.
  - `parceiro_leads.sincronizado_em` preenchido.
- Confirmacao de criacao no CRM:
  - Contagem de leads CRM temporarios: `1`.
- Tentativa de sincronizar o mesmo lead novamente:
  - Resultado: bloqueado com mensagem `Lead ja sincronizado com CRM.`
  - Contagem de leads CRM temporarios permaneceu `1`.
- GET direto em `lead_sync.php`:
  - Resultado: bloqueado com `Metodo invalido`.
  - Contagem no CRM permaneceu inalterada.
- POST sem CSRF em `lead_sync.php`:
  - Resultado: bloqueado com `CSRF invalido`.
  - Contagem no CRM permaneceu inalterada.
- Limpeza:
  - Lead CRM temporario removido.
  - Lead parceiro temporario removido.
  - Parceiro temporario removido.
  - Script temporario de teste removido.

## Resultado final

`Partner Leads Sync v1` foi implementado como sincronizacao manual e idempotente de leads de parceiros para o CRM principal. A versao nao cria vendas, nao altera financeiro, nao calcula comissao real e nao remove o lead original de parceiro. O handler `lead_sync.php` exige `require_admin()`, aceita apenas POST, valida CSRF, usa prepared statements e bloqueia duplicacao quando o lead ja esta sincronizado.
