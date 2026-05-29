# Correção Deploy Beta 13 - CSRF em atualizar_venda.php

Data: 2026-05-25  
Arquivo principal: `admin/vendas/atualizar_venda.php`  
Bloqueador tratado: atualização financeira de venda por POST sem CSRF.

## Correção aplicada

- `admin/vendas/atualizar_venda.php` agora aceita apenas `POST`.
- A sessão/admin continua sendo validada antes da ação com `require_admin()` e checagem de role `admin`.
- GET direto é redirecionado para `admin/vendas/vendas.php?msg=metodo_invalido`.
- `csrf_token` é validado com `hash_equals()` antes de ler os dados financeiros e antes de qualquer cálculo/update.
- A regra de negócio existente foi preservada:
  - validação de `venda_id`, `preco_venda` e `preco_custo`;
  - chamada de `calcularComissoes($venda)`;
  - bloqueio de lucro negativo;
  - `UPDATE vendas` com os mesmos campos;
  - mensagens de sucesso/erro existentes por `echo`.
- Nenhum cálculo financeiro foi alterado.

## Chamadas atualizadas

Foi executada busca por chamadas para `atualizar_venda.php` em `admin`, `app` e `public`.

Resultado:

- Nenhum link GET encontrado.
- Nenhum formulário/action chamando `admin/vendas/atualizar_venda.php` encontrado.
- Portanto, não havia botão ou formulário para atualizar com `csrf_input()` neste passo.

## Validações executadas

### Lint individual

- `C:\xampp\php\php.exe -l admin/vendas/atualizar_venda.php`

Resultado: sem erros de sintaxe.

### Lint PHP global

Comando:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1
```

Resultado:

- Arquivos OK: 198
- Arquivos com erro: 0
- Relatório: `storage/reports/php-lint-20260525-003544.txt`

### Busca por GET antigo

Comandos:

```powershell
rg -n "atualizar_venda\.php\?id=" admin app public -S
rg -n "atualizar_venda\.php" admin app public -S
```

Resultado: nenhuma chamada encontrada.

### HTTP local no XAMPP

Arquivo sincronizado para `C:\xampp\htdocs\RG_AUTO_SALES`.

Testes sem sessão autenticada:

- GET direto:
  - URL: `http://localhost/RG_AUTO_SALES/admin/vendas/atualizar_venda.php?id=1`
  - Resultado: `302`
  - Redirect: `auth/login.php?next=%2FRG_AUTO_SALES%2Fadmin%2Fvendas%2Fatualizar_venda.php%3Fid%3D1`
- POST sem sessão/CSRF:
  - URL: `http://localhost/RG_AUTO_SALES/admin/vendas/atualizar_venda.php`
  - Body: `venda_id=1&preco_venda=100&preco_custo=50&status=PENDENTE`
  - Resultado: `302`
  - Redirect: `auth/login.php?next=%2FRG_AUTO_SALES%2Fadmin%2Fvendas%2Fatualizar_venda.php`

Observação: sem sessão admin local, o teste HTTP para antes em `require_admin()`. Em sessão admin autenticada, o código agora valida método `POST` e `csrf_token` com `hash_equals()` antes de executar qualquer cálculo financeiro ou update.

## Estado final

Bloqueador P0 de `admin/vendas/atualizar_venda.php` corrigido. A rota agora exige POST autenticado com CSRF válido, preservando a regra financeira existente.
