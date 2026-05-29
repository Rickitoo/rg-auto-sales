# Correção Deploy Beta 11 - CSRF em cancelar_venda.php

Data: 2026-05-25  
Rota solicitada: `admin/vendas/cancelar_venda.php`  
Resultado: a rota não existe no código fonte atual.

## Verificação realizada

Foi verificado que `admin/vendas/cancelar_venda.php` não existe em `admin/vendas/`.

Arquivos existentes relacionados ao cancelamento:

- `admin/vendas/vendas.php`
- `admin/vendas/venda_detalhe.php`
- `app/views/admin/vendas/vendas_content.php`
- `app/views/admin/vendas/detalhe_venda_content.php`

O cancelamento atual é feito como ação interna `acao=cancelar` nos controladores acima, não por uma rota separada `cancelar_venda.php`.

## Estado do cancelamento existente

### `admin/vendas/vendas.php`

- Método: `POST`
- Ação: `acao=cancelar`
- CSRF: validado com `hash_equals($_SESSION['csrf_token'], $token)`
- Regra preservada: `UPDATE vendas SET status = ? WHERE id = ? AND status = 'PENDENTE'`
- Cálculos financeiros: não alterados.

### `admin/vendas/venda_detalhe.php`

- Método: `POST`
- Ação: `acao=cancelar`
- CSRF: validado com `hash_equals($_SESSION['csrf_token'], $token)`
- Regra preservada: atualiza para `CANCELADO` somente quando a venda está `PENDENTE`
- Cálculos financeiros: não alterados.

## Busca por links/botões da rota solicitada

Comando:

```powershell
rg -n "cancelar_venda\.php|href=.*cancelar|action=.*cancelar_venda" admin app public -S
```

Resultado:

- Nenhum link, botão ou formulário chama `admin/vendas/cancelar_venda.php`.
- Os resultados encontrados são botões visuais "Cancelar" de outros fluxos, sem relação com cancelamento de venda por essa rota.

## Validações executadas

### Lint individual

- `C:\xampp\php\php.exe -l admin/vendas/vendas.php`
- `C:\xampp\php\php.exe -l admin/vendas/venda_detalhe.php`

Resultado: sem erros de sintaxe.

Observação: não foi possível rodar lint individual em `admin/vendas/cancelar_venda.php` porque o arquivo não existe.

### Lint PHP global

Comando:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1
```

Resultado:

- Arquivos OK: 198
- Arquivos com erro: 0
- Relatório: `storage/reports/php-lint-20260525-002105.txt`

### HTTP local no XAMPP

Testes da rota solicitada:

- GET direto:
  - URL: `http://localhost/RG_AUTO_SALES/admin/vendas/cancelar_venda.php?id=1`
  - Resultado: `404`
- POST sem CSRF:
  - URL: `http://localhost/RG_AUTO_SALES/admin/vendas/cancelar_venda.php`
  - Body: `id=1`
  - Resultado: `404`

Conclusão: GET direto e POST sem CSRF não executam cancelamento porque a rota não existe.

## Decisão técnica

Nenhuma nova rota foi criada. Criar `admin/vendas/cancelar_venda.php` aumentaria a superfície de ataque sem necessidade, já que o cancelamento real existente está centralizado em `vendas.php` e `venda_detalhe.php`, usando POST e validação com `hash_equals()`.

## Estado final

Não há bloqueador P0 ativo em `admin/vendas/cancelar_venda.php` no estado atual, porque a rota não existe e não há chamadores. O cancelamento existente permanece protegido por POST e CSRF nos controladores atuais.
