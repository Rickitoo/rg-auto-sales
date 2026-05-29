# Auditoria Final - Layout Global Admin

Data: 2026-05-24

## Resumo executivo

Auditoria estática final após fechar o módulo Leads.

Estado atual:

- 18 páginas admin já usam `app/views/layouts/admin_layout.php`.
- Módulos principais migrados: Dashboard, Clientes, Carros principais, Financeiro, Vendas principais, CRM principal e Leads.
- O módulo Leads admin canônico ficou fechado no Layout Global Admin.
- Ainda existem páginas admin standalone em rotas auxiliares/legadas.
- Ainda existem includes antigos em dashboards/rotas legadas.
- Ainda existem blocos `<style>` e `style=` fora do núcleo já migrado.

## Páginas admin usando Layout Global Admin

- `admin/dashboard.php`
- `admin/clientes/clientes.php`
- `admin/clientes/cliente_detalhe.php`
- `admin/carros/listar_carros.php`
- `admin/carros/adicionar_carro.php`
- `admin/carros/editar_carro.php`
- `admin/financeiro/dashboard_financeiro.php`
- `admin/vendas/vendas.php`
- `admin/vendas/nova_venda.php`
- `admin/vendas/venda_detalhe.php`
- `admin/vendas/editar_venda.php`
- `admin/crm/dashboard.php`
- `admin/crm/inbox.php`
- `admin/leads/leads.php`
- `admin/leads/listar_leads.php`
- `admin/leads/ver_lead.php`
- `admin/leads/lead_detalhe.php`
- `admin/leads/adicionar_lead.php`

## Módulo Leads

Rotas admin de Leads migradas:

- `admin/leads/leads.php`
- `admin/leads/listar_leads.php`
- `admin/leads/ver_lead.php`
- `admin/leads/lead_detalhe.php`
- `admin/leads/adicionar_lead.php`

Rotas de ação/API de Leads que não precisam de layout visual:

- `admin/leads/leads_status.php`
- `admin/leads/leads_status_ajax.php`
- `admin/leads/lead_move.php`

Resultado:

- Nenhuma página visual do módulo Leads admin foi encontrada com HTML standalone, `<style>`, `style=` ou layout antigo.

Relatórios de migração do módulo:

- `storage/reports/admin-layout-migration-20260524-leads-principal.md`
- `storage/reports/admin-layout-migration-20260524-listar-leads.md`
- `storage/reports/admin-layout-migration-20260524-ver-lead.md`
- `storage/reports/admin-layout-migration-20260524-lead-detalhe.md`
- `storage/reports/admin-layout-migration-20260524-adicionar-lead.md`

## Páginas admin ainda standalone

Ainda têm HTML standalone (`<!doctype html>`, `<html>`, `<head>` ou `<body>`) e não usam o Layout Global Admin:

- `admin/config.php`
- `admin/admin.php`
- `admin/carros/carro_fotos.php`
- `admin/dashboard_pro.php`
- `admin/relatorio_vendedores.php`
- `admin/user_dashboard.php`
- `admin/vendas/confirmar_venda.php`
- `admin/vendas/marcar_venda.php`
- `admin/vendas/vendedores_pedidos.php`
- `admin/vendas/vendedor_ver.php`

## Includes antigos restantes

Ainda usam `includes/layout_top.php` / `includes/layout_bottom.php` ou nomes equivalentes:

- `admin/admin_saques.php`
- `admin/dashboard_carros.php`
- `admin/dashboard_vendas.php`
- `admin/painel_inteligente.php`
- `admin/funil.php`
- `public/dashboard.php`
- `app/modules/cars/listar_carros.php`
- `app/modules/cars/editar_carro.php`

Observações:

- `admin/funil.php` é relevante para CRM/Leads e deve ser priorizado se ainda for rota ativa.
- `public/dashboard.php` está fora do admin, mas ainda depende do layout antigo.
- `app/modules/cars/*` parecem rotas espelhadas/legadas dos carros já migrados em `admin/carros/*`.

## Blocos `<style>` restantes

Admin:

- `admin/admin.php`
- `admin/config.php`
- `admin/carros/carro_fotos.php`
- `admin/funil.php`
- `admin/relatorio_vendedores.php`
- `admin/user_dashboard.php`
- `admin/vendas/vendedores_pedidos.php`
- `admin/vendas/vendedor_ver.php`

## Estilos inline restantes

Admin e views admin com `style=`:

- `admin/admin.php`
- `admin/admin_leasing.php`
- `admin/admin_saques.php`
- `admin/dashboard_carros.php`
- `admin/dashboard_vendas.php`
- `admin/carros/carro_fotos.php`
- `admin/funil.php`
- `admin/painel_inteligente.php`
- `admin/vendas/marcar_venda.php`
- `admin/vendas/vendedores_pedidos.php`
- `admin/vendas/vendedor_ver.php`
- `app/views/admin/crm/dashboard_content.php`

Observação:

- `app/views/admin/crm/dashboard_content.php` mantém `style="width:...%"` para largura dinâmica do funil. É aceitável como valor calculado, mas pode ser convertido para CSS custom property em limpeza futura.

## Próximas páginas críticas antes do deploy beta

1. `admin/carros/carro_fotos.php`
   - Galeria/upload de fotos ainda standalone.
   - Alta visibilidade operacional no estoque.

2. `admin/funil.php`
   - Ainda usa layout antigo e tem `<style>`/inline CSS.
   - Relevante para CRM/Leads.

3. `admin/vendas/marcar_venda.php`
   - Fluxo comercial sensível.
   - Ainda standalone/inline CSS.
   - Deve ser migrado sem alterar regras de venda.

4. `admin/vendas/confirmar_venda.php`
   - Fluxo sensível de confirmação.
   - Exige cuidado com regras financeiras/status.

5. `admin/vendas/vendedores_pedidos.php`
   - Página comercial auxiliar ainda com `<style>` e inline CSS.

6. `admin/vendas/vendedor_ver.php`
   - Página comercial auxiliar ainda com `<style>` e inline CSS.

7. Dashboards/relatórios antigos:
   - `admin/dashboard_vendas.php`
   - `admin/dashboard_carros.php`
   - `admin/dashboard_pro.php`
   - `admin/painel_inteligente.php`
   - `admin/relatorio_vendedores.php`

8. Administração/configuração:
   - `admin/admin.php`
   - `admin/config.php`
   - `admin/user_dashboard.php`
   - `admin/admin_saques.php`

## Riscos para deploy beta

- Layout duplo ainda coexistindo: `app/views/layouts/admin_layout.php` e `includes/layout_top.php`.
- Páginas auxiliares de Vendas ainda fora do padrão global, incluindo fluxos sensíveis.
- Galeria de fotos de carros ainda standalone.
- `admin/funil.php` ainda antigo, apesar de CRM/Leads principais já estarem migrados.
- CSS concorrente entre `admin-modern.css`, blocos `<style>`, inline styles e CSS legado.
- Rotas espelhadas em `app/modules/cars/*` podem expor experiência antiga se acessadas diretamente.
- Alguns arquivos antigos ainda podem conter textos com encoding/mojibake.
- Links relativos antigos em páginas não migradas podem quebrar ao mudar contexto de navegação.

## Recomendação final

Para um beta interno, o núcleo operacional já está em boa forma: dashboard, clientes, carros principais, financeiro, vendas principais, CRM e Leads usam o Layout Global Admin.

Antes de beta externo ou apresentação para uso intensivo, recomenda-se migrar nesta ordem:

1. `admin/carros/carro_fotos.php`
2. `admin/funil.php`
3. `admin/vendas/marcar_venda.php`
4. `admin/vendas/confirmar_venda.php`
5. `admin/vendas/vendedores_pedidos.php`
6. `admin/vendas/vendedor_ver.php`
7. dashboards e relatórios antigos

Conclusão:

- Leads está fechado no Layout Global Admin.
- O risco restante está concentrado em rotas auxiliares/legadas, especialmente galeria de fotos, funil e páginas auxiliares de vendas.
