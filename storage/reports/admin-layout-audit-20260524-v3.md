# Auditoria v3 - Layout Global Admin

Data: 2026-05-24

## Resumo

Auditoria estática atualizada após as migrações de:

- `admin/crm/dashboard.php`
- `admin/crm/inbox.php`

Estado atual:

- 13 páginas admin já usam `app/views/layouts/admin_layout.php`.
- Dashboard principal, Clientes, Carros principais, Financeiro, Vendas principais e CRM principal já estão no Layout Global Admin.
- Ainda existem páginas admin standalone, principalmente Leads, Galeria de Fotos, páginas auxiliares de Vendas, relatórios e páginas legadas.
- Ainda existem usos de `includes/layout_top.php` / `includes/layout_bottom.php`.
- Ainda há blocos `<style>`, estilos inline e CSS duplicado em páginas antigas.
- Não foi feita nenhuma alteração de código nesta auditoria.

## Páginas já migradas

Usam `app/views/layouts/admin_layout.php`:

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

## Páginas admin ainda não migradas

Ainda têm HTML standalone (`<!doctype html>`, `<html>`, `<head>` ou `<body>`) e não usam o Layout Global Admin:

- `admin/admin.php`
- `admin/config.php`
- `admin/dashboard_pro.php`
- `admin/relatorio_vendedores.php`
- `admin/user_dashboard.php`
- `admin/carros/carro_fotos.php`
- `admin/leads/leads.php`
- `admin/leads/listar_leads.php`
- `admin/leads/ver_lead.php`
- `admin/leads/lead_detalhe.php`
- `admin/vendas/confirmar_venda.php`
- `admin/vendas/marcar_venda.php`
- `admin/vendas/vendedores_pedidos.php`
- `admin/vendas/vendedor_ver.php`

Ainda usam layout antigo sem HTML completo detectado nesta passagem:

- `admin/dashboard_carros.php`
- `admin/dashboard_vendas.php`
- `admin/admin_saques.php`
- `admin/funil.php`
- `admin/painel_inteligente.php`

## Usos restantes de `includes/layout_top.php` / `layout_bottom.php`

Ainda dependem do layout antigo:

- `admin/dashboard_carros.php`
- `admin/admin_saques.php`
- `admin/painel_inteligente.php`
- `admin/funil.php`
- `admin/dashboard_vendas.php`
- `public/dashboard.php`
- `app/modules/cars/editar_carro.php`
- `app/modules/cars/listar_carros.php`

Observações:

- `public/dashboard.php` é público/usuário, mas usa o layout antigo compartilhado.
- `app/modules/cars/*` parecem rotas espelhadas/legadas dos carros já migrados em `admin/carros/*`.

## HTML standalone restante

Admin:

- `admin/admin.php`
- `admin/carros/carro_fotos.php`
- `admin/config.php`
- `admin/dashboard_pro.php`
- `admin/leads/lead_detalhe.php`
- `admin/leads/leads.php`
- `admin/leads/listar_leads.php`
- `admin/leads/ver_lead.php`
- `admin/relatorio_vendedores.php`
- `admin/user_dashboard.php`
- `admin/vendas/confirmar_venda.php`
- `admin/vendas/marcar_venda.php`
- `admin/vendas/vendedores_pedidos.php`
- `admin/vendas/vendedor_ver.php`

Fora de admin, mas relevantes para duplicação/legado:

- `app/modules/cars/*`
- `app/modules/leads/*`
- `app/modules/finance/*`
- `views/crm/pipeline.php`
- várias páginas em `public/`

## Blocos `<style>` restantes

Admin:

- `admin/admin.php`
- `admin/config.php`
- `admin/carros/carro_fotos.php`
- `admin/funil.php`
- `admin/leads/listar_leads.php`
- `admin/leads/ver_lead.php`
- `admin/relatorio_vendedores.php`
- `admin/user_dashboard.php`
- `admin/vendas/vendedores_pedidos.php`
- `admin/vendas/vendedor_ver.php`

Fora de admin:

- `app/modules/cars/adicionar_carro.php`
- `app/modules/cars/carro_fotos.php`
- `app/modules/cars/editar_carro.php`
- `app/modules/cars/listar_carros.php`
- `app/modules/finance/custos.php`
- `app/modules/finance/recibo.php`
- `app/modules/leads/listar_leads.php`
- `app/modules/leads/ver_lead.php`
- `views/crm/pipeline.php`
- várias páginas públicas

## Estilos inline restantes

Admin com `style=` relevante:

- `admin/admin.php`
- `admin/admin_leasing.php`
- `admin/admin_saques.php`
- `admin/carros/carro_fotos.php`
- `admin/dashboard_carros.php`
- `admin/dashboard_vendas.php`
- `admin/funil.php`
- `admin/leads/adicionar_lead.php`
- `admin/leads/leads.php`
- `admin/painel_inteligente.php`
- `admin/vendas/marcar_venda.php`
- `admin/vendas/vendedores_pedidos.php`
- `admin/vendas/vendedor_ver.php`

Observação: `app/views/admin/crm/dashboard_content.php` mantém um `style="width:...%"` para a largura dinâmica das barras do funil. É aceitável por ser valor calculado de visualização, mas pode ser trocado por CSS custom property no futuro.

## CSS duplicado

Ainda existem fontes concorrentes de estilo:

- `public/assets/css/admin-modern.css` como base moderna.
- `includes/layout_top.php` com estilos/admin shell antigos.
- Blocos `<style>` em páginas admin standalone.
- Estilos inline em dashboards, funil, galeria, vendedores e páginas legadas.
- Bootstrap CDN carregado diretamente por páginas standalone.
- `style.css` legado em módulos antigos.

Risco: enquanto essas fontes coexistirem, pequenas alterações visuais podem causar regressões diferentes por rota.

## Rotas antigas ou potencialmente quebradas

Achados:

- Não foi encontrado uso ativo de `admin/vendas/detalhe_venda.php`; a rota real usada é `admin/vendas/venda_detalhe.php`.
- `app/modules/finance/financeiro.php` existe como redirect para `admin/financeiro/dashboard_financeiro.php`, então não parece quebrado.
- Há rotas espelhadas antigas em `app/modules/cars/*` que ainda usam layout antigo. Elas podem gerar inconsistência se ainda forem acessadas diretamente.
- `admin/admin.php` contém links/actions relativos antigos como `delete.php`, `marcar_vendido.php` e imagens diretas. Esses links devem ser revisados antes do beta porque podem depender do diretório atual e não das helpers `url()`.
- `public/dashboard.php` ainda aponta para rotas admin migradas, mas ele próprio usa layout antigo.

Não foi feita validação HTTP de todas as rotas nesta auditoria; esta seção é baseada em varredura estática.

## Riscos antes do deploy beta

1. Layout duplo
   - `admin_layout.php` e `includes/layout_top.php` ainda coexistem.

2. Leads ainda fora do padrão
   - `admin/leads/leads.php`, `listar_leads.php`, `ver_lead.php` e `lead_detalhe.php` continuam standalone.

3. Galeria de fotos fora do padrão
   - `admin/carros/carro_fotos.php` ainda tem HTML/CSS próprio.

4. Páginas auxiliares de vendas fora do padrão
   - `confirmar_venda.php`, `marcar_venda.php`, `vendedores_pedidos.php`, `vendedor_ver.php`.

5. Dashboards legados
   - `dashboard_vendas.php`, `dashboard_carros.php`, `dashboard_pro.php`, `funil.php`, `painel_inteligente.php`.

6. Rotas espelhadas
   - `app/modules/cars/*` e `app/modules/leads/*` podem duplicar comportamento já migrado.

7. CSS concorrente
   - Inline styles, `<style>`, Bootstrap direto, `style.css` legado e `admin-modern.css`.

8. Encoding legado
   - Páginas antigas ainda podem mostrar mojibake em textos.

9. Links relativos antigos
   - Especialmente em `admin/admin.php` e rotas legadas.

## Próxima ordem recomendada

1. `admin/leads/leads.php`
2. `admin/leads/listar_leads.php`
3. `admin/leads/ver_lead.php`
4. `admin/leads/lead_detalhe.php`
5. `admin/carros/carro_fotos.php`
6. `admin/painel_inteligente.php`
7. `admin/funil.php`
8. `admin/vendas/vendedores_pedidos.php`
9. `admin/vendas/vendedor_ver.php`
10. `admin/vendas/marcar_venda.php`
11. `admin/vendas/confirmar_venda.php`
12. `admin/dashboard_vendas.php`
13. `admin/dashboard_carros.php`
14. `admin/admin_saques.php`
15. `admin/config.php`

## Conclusão

O núcleo do admin já está substancialmente migrado: dashboard, CRM principal, clientes, carros principais, financeiro e fluxo principal de vendas. Para o beta, o maior risco restante está em Leads, Galeria de Fotos, páginas auxiliares de vendas e rotas espelhadas antigas. A próxima fase deve focar em Leads e em eliminar gradualmente o uso de `includes/layout_top.php` nas rotas admin canónicas.
