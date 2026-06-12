# Correcao do fluxo Vender Carro - carro_id

Data: 2026-06-11

## Resumo

Foi auditado o fluxo publico `public/vender_carro.php`, a action relacionada `app/modules/cars/actions/vender_carro.php` e as paginas admin `admin/vendas/vendedores_pedidos.php` e `admin/vendas/vendedor_ver.php`.

O problema encontrado foi que os pedidos de venda guardavam os dados do vendedor, mas nao garantiam a criacao do carro na tabela `carros` nem a associacao do pedido com `vendedores.carro_id`. Como resultado, o admin dependia apenas dos campos duplicados em `vendedores` e nao conseguia consultar o carro real do sistema.

## Alteracoes feitas

- `public/vender_carro.php`
  - Cria o carro em `carros`.
  - Cria o pedido em `vendedores` com `carro_id`.
  - Mantem o registo em `leads` com `tipo = 'venda'` e o mesmo `carro_id`.
  - Usa transacao para evitar carro, pedido ou lead parcialmente criados.
  - Mantem CSRF, honeypot e rate limit existentes.

- `app/modules/cars/actions/vender_carro.php`
  - Cria o carro em `carros` antes do pedido.
  - Guarda o ID criado em `vendedores.carro_id`.
  - Usa transacao ate confirmar que pelo menos uma foto do pedido foi salva.
  - Mantem `require_admin()` e `require_post_csrf()`.

- `admin/vendas/vendedores_pedidos.php`
  - Passou a fazer `LEFT JOIN carros c ON c.id = v.carro_id`.
  - Mostra dados do carro via `COALESCE(c.campo, v.campo)` para preservar pedidos antigos sem `carro_id`.
  - Exibe o ID do carro associado quando existir.

- `admin/vendas/vendedor_ver.php`
  - Passou a fazer `LEFT JOIN carros`.
  - Mostra dados completos do vendedor e do carro associado.
  - Mantem fallback para pedidos antigos sem associacao.

## Validacao

- Lint direto dos ficheiros alterados:
  - `public/vender_carro.php`
  - `app/modules/cars/actions/vender_carro.php`
  - `admin/vendas/vendedores_pedidos.php`
  - `admin/vendas/vendedor_ver.php`

- Lint completo do projeto:
  - Comando: `powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1`
  - Resultado: 219 ficheiros OK, 0 erros.
  - Relatorio gerado: `storage/reports/php-lint-20260611-172308.txt`

## Criterios cobertos

- Pedido de venda fica associado ao carro por `vendedores.carro_id`.
- Carro e criado na tabela `carros`.
- Lead publico, quando criado, tambem fica associado por `leads.carro_id`.
- Admin lista e detalhe fazem JOIN com `carros`.
- Dados do vendedor continuam visiveis.
- Dados do carro passam a vir do registo associado em `carros`.
- GET direto em actions mutativas e POST sem CSRF continuam protegidos pelas funcoes existentes.
- Nao foram alteradas regras de vendas, comissoes, financeiro ou CRM.
