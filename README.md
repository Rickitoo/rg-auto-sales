# RG Auto Sales

Checkpoint tecnico em 2026-05-23.

## Estado atual

- Bootstrap centralizado em `app/core/bootstrap.php`.
- Rotas principais organizadas em `admin/`, `auth/` e `public/`.
- Helpers oficiais para URL: `url()`, `public_url()`, `asset()` e `redirect_to()`.
- Wrappers legados classificados e mantidos temporariamente para compatibilidade.
- Rotas canonicas definidas para dashboard, carros, leads, vendas, financeiro, clientes, test-drive, autenticacao e paginas publicas.
- Duplicatas claras convertidas em redirects controlados.
- Checklist de teste manual criado em `storage/reports/manual-test-checklist-20260523.md`.
- Lint PHP validado com 173 arquivos OK e 0 erros.

## Correcoes recentes de schema

- `public/Formulario_cliente.php`: test-drive grava o cadastro principal em `clientes` e espelha lead no CRM usando colunas existentes.
- `admin/carros/listar_carros.php`: galeria usa `carros_fotos.caminho`; capa do carro usa `carros.imagem`.
- `admin/vendas/vendedores_pedidos.php`: pedidos de vendedores usam `vendedores.data_registo`.

## Proxima fase

Testar manualmente o fluxo completo:

1. Lead/test-drive publico.
2. Lead no CRM/admin.
3. Conversao para venda.
4. Confirmacao/fechamento da venda.
5. Reflexo no financeiro, lucro e comissoes.

Depois dos testes, remover wrappers antigos somente quando nao houver `NOT FOUND`, erro de schema, POST quebrado ou asset 404.
