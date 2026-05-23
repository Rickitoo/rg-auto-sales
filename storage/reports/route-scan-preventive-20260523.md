# Varredura preventiva de rotas antigas - RG_AUTO_SALES

Data: 2026-05-23

## Padrao pesquisado

- `href="*.php`
- `action="*.php`
- `header("Location: *.php`
- `window.location = '*.php`
- `location.href = '*.php`

Escopo: `admin`, `public`, `includes`, `app`, `views`.

## Seguro corrigir agora

Corrigidos nesta etapa:

- `admin/dashboard_carros.php`: `editar_carro.php?id=...` -> `url('admin/carros/editar_carro.php?id=...')`
- `admin/config.php`: `vendas.php` -> `url('admin/vendas/vendas.php')`
- `admin/relatorio_vendedores.php`: `vendas.php` -> `url('admin/vendas/vendas.php')`
- `admin/clientes/clientes.php`: `dashboard.php` -> `url('admin/dashboard.php')`
- `admin/vendas/vendas.php`: `dashboard.php` -> `url('admin/dashboard.php')`
- `admin/vendas/nova_venda.php`: `dashboard.php` -> `url('admin/dashboard.php')`
- `admin/vendas/venda_detalhe.php`: `dashboard.php` -> `url('admin/dashboard.php')`
- `admin/vendas/confirmar_venda.php`: `funil.php` -> `url('admin/funil.php')`
- `admin/leads/lead_detalhe.php`: `funil.php` -> `url('admin/funil.php')`
- `admin/aprovacoes.php`: `aprovar_venda.php?id=...` -> `url('admin/vendas/aprovar_venda.php?id=...')`
- `admin/aprovacoes.php`: `rejeitar_venda.php?id=...` -> `url('admin/vendas/rejeitar_venda.php?id=...')`
- `public/dashboard.php`: `ver_lead.php?id=...` -> `url('admin/leads/ver_lead.php?id=...')`

## Manter relativo por enquanto

Rotas relativas que continuam dentro da mesma pasta canonica e existem hoje:

- `admin/carros/listar_carros.php`: `apagar_carro.php?id=...`
- `admin/leads/listar_leads.php`: `ver_lead.php?id=...`
- `admin/leads/listar_leads.php`: `leads_status.php?id=...&s=...`
- `admin/leads/leads.php`: `ver_lead.php?id=...`
- `admin/vendas/editar_venda.php`: `venda_detalhe.php?id=...`
- `admin/vendas/editar_venda.php`: `editar_venda.php?id=...`
- `admin/vendas/vendas.php`: `venda_detalhe.php?id=...`
- `admin/vendas/vendedores_pedidos.php`: `vendedor_ver.php?id=...`
- `admin/vendas/vendedores_pedidos.php`: `vendedor_converter.php?id=...`

Observacao: estes podem ser padronizados futuramente com `url()`, mas nao sao causa provavel de `NOT FOUND` enquanto a pagina origem permanecer na mesma pasta.

## Precisa analise

Casos encontrados que podem quebrar, mas nao foram alterados por dependerem de destino canonico, endpoint ausente ou fluxo de negocio:

- `admin/dashboard.php`: `marcar_pago.php?id=...`
- `public/dashboard.php`: `marcar_pago.php?id=...`
- `app/modules/finance/financeiro.php`: `marcar_pago.php?id=...`
- `admin/clientes/clientes.php`: `cliente_detalhe.php?id=...` nao tem arquivo canonico confirmado.
- `admin/leads/ver_lead.php`: `editar_lead.php?id=...` nao tem arquivo canonico confirmado.
- `admin/leads/leads.php`: `follow_up.php?id=...` provavelmente deve apontar para `admin/services/follow_up.php`, mas precisa validar parametros.
- `admin/admin_leasing.php`: `action="update_status.php"` precisa confirmar se e leasing ou leads.
- `admin/admin.php`: `delete.php`, `update_status.php`, `marcar_vendido.php` parecem wrappers antigos ou endpoints movidos; tela tambem parece legado administrativo.
- `admin/vendas/vendas.php`: `export_vendas_csv.php` nao existe em `admin/vendas`; existe em `app/modules/finance/export_vendas_csv.php`.
- `admin/vendas/vendas.php`: `custos.php?venda_id=...` nao existe em `admin/vendas`; existe em `app/modules/finance/custos.php`.
- `admin/vendas/venda_detalhe.php`: `custos.php?venda_id=...` e `recibo.php?id=...` nao existem em `admin/vendas`; existem em `app/modules/finance`.
- `admin/vendas/editar_venda.php`: `custos.php?venda_id=...` precisa destino canonico.
- `views/crm/pipeline.php`: `lead.php?id=...` e legado; arquivo atualmente redireciona para `admin/funil.php` antes do HTML.
- Rotas relativas em `app/modules/*` continuam classificadas como legado e devem ser tratadas em etapa propria.

## Ignorados

Links externos, como Facebook, Instagram, WhatsApp, `mailto:` e `tel:` nao foram alterados.

## Proxima recomendacao

Antes da remocao de wrappers:

1. Definir rotas canonicas para custos, recibo, export CSV e marcar pago.
2. Confirmar se `cliente_detalhe.php` e `editar_lead.php` devem existir ou virar redirects.
3. Validar se `admin/admin.php` ainda e tela ativa ou apenas legado.
4. Padronizar tambem os relativos "seguros" com `url()` quando a equipe quiser consistencia total.
