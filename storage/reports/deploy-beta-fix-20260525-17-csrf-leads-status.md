# Correcao Deploy Beta 17 - CSRF em rotas de status de leads

Data: 2026-05-25  
Escopo: rotas reais que alteram status de leads.  
Resultado: rotas de status de leads protegidas com POST + sessao/admin + CSRF + whitelist de status.

## Problema encontrado

Ainda existiam rotas que alteravam `leads.status` sem CSRF:

- `admin/leads/leads_status.php` e `app/modules/leads/leads_status.php`
  - alteravam status via GET `id`/`s`;
  - havia links GET reais em listagens de leads.
- `admin/leads/leads_status_ajax.php` e `app/modules/leads/leads_status_ajax.php`
  - alteravam status, ultimo contato e proximo follow-up via POST sem token.
- `admin/leads/lead_move.php` e `app/modules/leads/lead_move.php`
  - ja exigiam POST, mas nao validavam CSRF.
- `admin/crm/inbox.php`
  - a acao POST `acao=status` alterava status no CRM Inbox sem CSRF.

Tambem foi verificada a busca por chamadas antigas similares, incluindo `status.php?id=`, `lead_status.php?id=`, `atualizar_status.php?id=` e `mudar_status.php?id=`.

## Alteracao aplicada

- Rotas `leads_status.php` agora aceitam apenas POST.
- Rotas `leads_status.php` agora leem `lead_id` e `status` via `$_POST`.
- Rotas AJAX e `lead_move.php` validam metodo POST antes da mutacao.
- Todas as rotas de status alteradas validam `csrf_token` com `hash_equals()`.
- `lead_id` e validado como inteiro positivo.
- Status continua validado contra a whitelist existente:
  - `novo`
  - `contactado`
  - `qualificado`
  - `agendado`
  - `negociacao`
  - `fechado`
  - `perdido`
- Links GET antigos nas listagens foram convertidos para formularios POST com `csrf_input()`.
- O `fetch` do funil agora envia `lead_id`, `status` e `csrf_token`.
- O formulario de status do CRM Inbox agora inclui `csrf_input()`.

As regras comerciais existentes foram preservadas:

- os mesmos status continuam permitidos;
- os updates de status continuam atualizando os mesmos campos;
- a rotina AJAX continua atualizando `ultimo_contacto` e `proximo_followup`;
- o fluxo `lead_move.php` continua retornando redirect quando status e `fechado`;
- a estrutura do CRM e os textos gerais nao foram alterados.

## Arquivos alterados

- `admin/leads/leads_status.php`
- `app/modules/leads/leads_status.php`
- `admin/leads/leads_status_ajax.php`
- `app/modules/leads/leads_status_ajax.php`
- `admin/leads/lead_move.php`
- `app/modules/leads/lead_move.php`
- `admin/funil.php`
- `admin/crm/inbox.php`
- `app/views/admin/crm/inbox_content.php`
- `app/views/admin/leads/listar_leads_content.php`
- `app/modules/leads/listar_leads.php`

Nenhum arquivo de fotos/uploads foi alterado nesta tarefa.

## Validacoes feitas

### Busca por rotas e chamadas antigas

Busca executada em `admin`, `app` e `public` para rotas de status de leads e chamadas GET antigas.

Resultado:

- nao restaram chamadas `leads_status.php?id=...`;
- nao foram encontradas rotas reais de lead status em `lead_status.php?id=...`, `atualizar_status.php?id=...`, `mudar_status.php?id=...`;
- as ocorrencias restantes de `update_status.php?id=...` pertencem a status de vendedores em `admin/admin.php`, fora do escopo de leads.

### Lint PHP individual

Arquivos validados com `C:\xampp\php\php.exe -l`:

- `admin/leads/leads_status.php`
- `app/modules/leads/leads_status.php`
- `admin/leads/leads_status_ajax.php`
- `app/modules/leads/leads_status_ajax.php`
- `admin/leads/lead_move.php`
- `app/modules/leads/lead_move.php`
- `admin/funil.php`
- `admin/crm/inbox.php`
- `app/views/admin/crm/inbox_content.php`
- `app/views/admin/leads/listar_leads_content.php`
- `app/modules/leads/listar_leads.php`

Resultado: sem erros de sintaxe.

### Lint PHP global

Comando:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1
```

Resultado final:

- Arquivos OK: 198
- Arquivos com erro: 0
- Relatorio: `storage/reports/php-lint-20260525-115513.txt`

## Resultado HTTP

Teste local executado com servidor PHP temporario e MySQL local do XAMPP.

### Sem sessao

- `GET /admin/leads/leads_status.php?id=1&s=contactado`
  - Resultado: `302`
  - Redirect: `/auth/login.php?...`
- `POST /admin/leads/leads_status.php`
  - Body: `lead_id=1&status=contactado`
  - Resultado: `302`
  - Redirect: `/auth/login.php?...`
- `GET /admin/leads/lead_move.php?lead_id=1&status=contactado`
  - Resultado: `302`
  - Redirect: `/auth/login.php?...`
- `POST /admin/leads/lead_move.php`
  - Body: `lead_id=1&status=contactado`
  - Resultado: `302`
  - Redirect: `/auth/login.php?...`

### Com sessao admin sintetica, sem CSRF

- `GET /admin/leads/leads_status.php?id=1&s=contactado`
  - Resultado: `302`
  - Redirect: `/admin/leads/listar_leads.php?msg=metodo_invalido`
- `POST /admin/leads/leads_status.php`
  - Body: `lead_id=1&status=contactado`
  - Resultado: `403`
  - Corpo: `CSRF inválido.`
- `GET /admin/leads/lead_move.php?lead_id=1&status=contactado`
  - Resultado: `405`
  - Corpo JSON: `{"ok":false,"error":"Metodo invalido"}`
- `POST /admin/leads/lead_move.php`
  - Body: `lead_id=1&status=contactado`
  - Resultado: `403`
  - Corpo JSON: `{"ok":false,"error":"CSRF invalido"}`
- `POST /admin/crm/inbox.php`
  - Body: `acao=status&lead_id=1&status=contactado`
  - Resultado: `403`
  - Corpo: `CSRF inválido.`

Esses testes confirmam que GET direto nao executa alteracao de status e que POST sem sessao/CSRF nao altera status.

## Observacoes de seguranca

- As rotas que alteram status de leads agora dependem de POST autenticado e token CSRF valido.
- A whitelist de status foi mantida e continua bloqueando valores fora do conjunto permitido.
- Os links GET de status em listagens foram removidos do fluxo de UI.
- O CRM Inbox recebeu protecao apenas na acao que altera `leads.status`.
- Nenhuma feature nova foi adicionada.
- Nenhum fluxo de fotos/uploads foi tocado.
