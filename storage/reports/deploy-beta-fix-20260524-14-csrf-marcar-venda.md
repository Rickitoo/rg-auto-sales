# Correção Deploy Beta 14 - CSRF em marcar_venda.php

Data: 2026-05-25  
Arquivo principal: `admin/vendas/marcar_venda.php`  
Bloqueador tratado: criação/marcação de venda por rota acessível via GET e POST sem CSRF.

## Correção aplicada

- `admin/vendas/marcar_venda.php` agora aceita apenas `POST`.
- A sessão/admin continua validada antes da ação com `require_admin()` e checagem de role `admin`.
- GET direto é redirecionado para `admin/leads/leads.php?msg=metodo_invalido`.
- `csrf_token` é validado com `hash_equals()` antes de buscar lead/carro e antes de qualquer insert/update.
- O ID do lead passou a vir de `$_POST['id']` ou `$_POST['lead_id']`.
- O fluxo ficou em duas fases por POST:
  - POST inicial com lead abre a tela de marcar venda;
  - POST final com `preco_venda` e `data_venda` executa a regra existente.
- O formulário final de confirmação agora inclui `csrf_input()` e `lead_id`.
- A regra de negócio existente foi preservada:
  - busca do lead/carro;
  - validações de preço/data;
  - cálculo de lucro e comissões;
  - criação de cliente quando necessário;
  - insert em `vendas`;
  - update do carro para `vendido`;
  - update do lead para `fechado`;
  - redirect final para `admin/dashboard.php?sucesso=venda`.
- Nenhum cálculo financeiro foi alterado.

## Chamadas atualizadas para POST

Foram trocados links GET para formulários POST com `csrf_input()` em:

- `app/views/admin/leads/ver_lead_content.php`
- `app/views/admin/leads/listar_leads_content.php`
- `app/views/admin/leads/leads_content.php`
- `app/views/admin/carros/listar_carros_content.php`
- `app/views/admin/crm/inbox_content.php`
- `admin/painel_inteligente.php`
- `app/modules/leads/ver_lead.php`
- `app/modules/leads/listar_leads.php`
- `app/modules/leads/leads.php`
- `app/modules/cars/listar_carros.php`

## Validações executadas

### Lint individual

Arquivos principais/alterados validados com `C:\xampp\php\php.exe -l`:

- `admin/vendas/marcar_venda.php`
- `admin/painel_inteligente.php`
- `app/views/admin/leads/ver_lead_content.php`
- `app/views/admin/leads/listar_leads_content.php`
- `app/views/admin/leads/leads_content.php`
- `app/views/admin/carros/listar_carros_content.php`
- `app/views/admin/crm/inbox_content.php`
- `app/modules/leads/ver_lead.php`
- `app/modules/leads/listar_leads.php`
- `app/modules/leads/leads.php`
- `app/modules/cars/listar_carros.php`

Resultado: sem erros de sintaxe.

### Lint PHP global

Comando:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1
```

Resultado final:

- Arquivos OK: 198
- Arquivos com erro: 0
- Relatório: `storage/reports/php-lint-20260525-005021.txt`

### Busca por GET antigo

Comando:

```powershell
rg -n "marcar_venda\.php\?id=|marcar_venda\.php\?lead_id=|marcar_venda\.php\?" admin app public -S
```

Resultado: nenhum link GET direto restante para `marcar_venda.php?id=...` ou `marcar_venda.php?lead_id=...`.

### HTTP local no XAMPP

Arquivos sincronizados para `C:\xampp\htdocs\RG_AUTO_SALES`.

Testes sem sessão autenticada:

- GET direto:
  - URL: `http://localhost/RG_AUTO_SALES/admin/vendas/marcar_venda.php?id=1`
  - Resultado: `302`
  - Redirect: `auth/login.php?next=%2FRG_AUTO_SALES%2Fadmin%2Fvendas%2Fmarcar_venda.php%3Fid%3D1`
- POST sem sessão/CSRF:
  - URL: `http://localhost/RG_AUTO_SALES/admin/vendas/marcar_venda.php`
  - Body: `lead_id=1`
  - Resultado: `302`
  - Redirect: `auth/login.php?next=%2FRG_AUTO_SALES%2Fadmin%2Fvendas%2Fmarcar_venda.php`

Observação: sem sessão admin local, o teste HTTP para antes em `require_admin()`. Em sessão admin autenticada, o código agora valida método `POST` e `csrf_token` com `hash_equals()` antes de buscar dados ou executar a venda.

## Estado final

Bloqueador P0 de `admin/vendas/marcar_venda.php` corrigido. A marcação/criação de venda agora depende de POST autenticado com CSRF válido, preservando o fluxo e os cálculos existentes.
