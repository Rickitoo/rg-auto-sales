# Migração Layout Admin - Detalhe da Venda

Data: 2026-05-24

## Rota migrada

- `admin/vendas/venda_detalhe.php`

Observação: a rota pedida como `admin/vendas/detalhe_venda.php` não existe no projeto. A rota real encontrada e migrada foi `admin/vendas/venda_detalhe.php`.

## Alterações

- Mantida toda a lógica no controlador `admin/vendas/venda_detalhe.php`.
- Movido o HTML visual para `app/views/admin/vendas/detalhe_venda_content.php`.
- Removido HTML standalone (`<!doctype html>`, `<head>`, `<body>`, Bootstrap local e CSS inline).
- Página passou a usar `app/views/layouts/admin_layout.php`.

## Mantido no controlador

- Autenticação com `require_admin()`.
- CSRF/token existente.
- Busca da venda por ID.
- Validação das colunas do modelo novo.
- Ações POST: `pagar`, `cancelar`, `recalcular`, `aprovar`.
- Regras de aprovação.
- Recalculo via `recalcular_venda()`.
- Atualização de status.
- Marcação de cliente como `CONCLUIDO` quando venda vira `PAGO`.
- Cálculos de lucro real, custos, comissões e percentuais.
- Dados do cliente e carro.
- Flash messages.

## Layout usado

```php
$pageTitle = 'Detalhe da Venda';
$pageSubtitle = 'Informações comerciais, financeiras e acompanhamento da venda';
$contentFile = BASE_PATH . '/app/views/admin/vendas/detalhe_venda_content.php';

require BASE_PATH . '/app/views/layouts/admin_layout.php';
```

## Validação

- Lint individual:
  - `admin/vendas/venda_detalhe.php`: OK
  - `app/views/admin/vendas/detalhe_venda_content.php`: OK
- Lint PHP global:
  - 191 arquivos OK
  - 0 erros
  - Relatório: `storage/reports/php-lint-20260524-200314.txt`
- HTTP local:
  - URL: `http://localhost/RG_AUTO_SALES/admin/vendas/venda_detalhe.php?id=1`
  - Resultado: `302 Found`
  - Redirect preservou `next=/RG_AUTO_SALES/admin/vendas/venda_detalhe.php?id=1`

## Observações

- Nenhuma regra financeira, pagamento, comissão, aprovação ou status foi alterada.
- O texto visual da nova view foi normalizado para ASCII onde possível para evitar ampliar problemas de encoding legado.
