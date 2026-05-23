# RG Auto Sales

Checkpoint tecnico em 2026-05-24.

## Estado atual

- Bootstrap centralizado em `app/core/bootstrap.php`.
- Rotas principais organizadas em `admin/`, `auth/` e `public/`.
- Helpers oficiais para URL: `url()`, `public_url()`, `asset()` e `redirect_to()`.
- Wrappers legados classificados e mantidos temporariamente para compatibilidade.
- Rotas canonicas definidas para dashboard, carros, leads, vendas, financeiro, clientes, test-drive, autenticacao e paginas publicas.
- Duplicatas claras convertidas em redirects controlados.
- Checklist de teste manual criado em `storage/reports/manual-test-checklist-20260523.md`.
- CRM Inbox criado com timeline, follow-up por lead, prioridade automatica e mensagens WhatsApp inteligentes.
- Dashboard CRM criado em `admin/crm/dashboard.php`.
- Painel Inteligente reforcado com leads urgentes, leads parados, follow-ups pendentes, vendas pendentes, pagamentos pendentes e proximas acoes.
- Detalhe de cliente/test-drive criado em `admin/clientes/cliente_detalhe.php`.
- Rotas e botoes principais entre dashboard admin, dashboard CRM, inbox, financeiro, vendas, clientes, carros e leads foram estabilizados.
- Modernizacao visual inicial aplicada ao Dashboard CRM, CRM Inbox e Painel Inteligente.
- Modernizacao visual aplicada aos modulos Financeiro e Vendas usando `public/assets/css/admin-modern.css`.
- Financeiro e Vendas agora usam componentes visuais reutilizaveis como `rg-page-hero`, `rg-kpi-grid`, `rg-kpi-card`, `rg-panel`, `rg-table-wrap`, badges e botoes padronizados.
- `admin/vendas/pagar_venda.php` permanece como endpoint de acao/redirect, sem HTML visual para preservar o fluxo.
- Lint PHP validado com 176 arquivos OK e 0 erros.

## Correcoes recentes de schema

- `public/Formulario_cliente.php`: test-drive grava o cadastro principal em `clientes` e espelha lead no CRM usando colunas existentes.
- `admin/carros/listar_carros.php`: galeria usa `carros_fotos.caminho`; capa do carro usa `carros.imagem`.
- `admin/vendas/vendedores_pedidos.php`: pedidos de vendedores usam `vendedores.data_registo`.

## Proxima fase

Testar com dados reais e preparar deploy:

1. Lead/test-drive publico.
2. Lead no CRM/admin.
3. Follow-up, WhatsApp e timeline.
4. Conversao para venda.
5. Confirmacao/fechamento da venda.
6. Pagamento, financeiro, lucro e comissoes.
7. Backup do banco antes de uso real.
8. Padronizar pasta oficial ou symlink para evitar divergencia entre workspace e XAMPP.
9. Configurar producao com HTTPS, credenciais fora do repositorio, logs e politica de erros.

Depois dos testes, remover wrappers antigos somente quando nao houver `NOT FOUND`, erro de schema, POST quebrado ou asset 404.
