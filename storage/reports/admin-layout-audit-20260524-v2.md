# Auditoria v2 - Layout Admin Global

Data: 2026-05-24

## Resumo

Auditoria atualizada após as migrações recentes para o Layout Global Admin.

Estado atual:

- 11 páginas admin já usam `app/views/layouts/admin_layout.php`.
- As páginas principais de Dashboard, Clientes, Carros, Vendas e Financeiro já estão no layout novo.
- O fluxo principal de Vendas avançou: listagem, nova venda, detalhe da venda e editar venda já foram migrados.
- Ainda existem páginas admin standalone com HTML completo próprio.
- Ainda existem páginas admin usando `includes/layout_top.php` / `includes/layout_bottom.php`.
- CSS inline e blocos `<style>` continuam concentrados em CRM, Leads, páginas auxiliares de vendas e páginas legadas.
- Não foi feita nenhuma alteração de código nesta auditoria.

## Páginas já migradas para Layout Global Admin

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

Views já criadas em `app/views/admin/`:

- `app/views/admin/dashboard/dashboard_content.php`
- `app/views/admin/clientes/clientes_content.php`
- `app/views/admin/clientes/cliente_detalhe_content.php`
- `app/views/admin/carros/listar_carros_content.php`
- `app/views/admin/carros/adicionar_carro_content.php`
- `app/views/admin/carros/editar_carro_content.php`
- `app/views/admin/financeiro/financeiro_content.php`
- `app/views/admin/vendas/vendas_content.php`
- `app/views/admin/vendas/nova_venda_content.php`
- `app/views/admin/vendas/detalhe_venda_content.php`
- `app/views/admin/vendas/editar_venda_content.php`

## Usos restantes de layout antigo

Ainda usam `includes/layout_top.php` e/ou `includes/layout_bottom.php`:

- `admin/dashboard_carros.php`
- `admin/admin_saques.php`
- `admin/funil.php`
- `admin/dashboard_vendas.php`
- `admin/painel_inteligente.php`
- `public/dashboard.php`
- `app/modules/cars/listar_carros.php`
- `app/modules/cars/editar_carro.php`

Observações:

- `public/dashboard.php` não é uma rota admin canónica, mas ainda depende do layout antigo.
- `app/modules/cars/listar_carros.php` e `app/modules/cars/editar_carro.php` parecem espelhos das páginas admin de carros já migradas. Recomenda-se decidir se serão wrappers, rotas canónicas ou candidatos a remoção.

## Páginas admin ainda standalone

Páginas admin com `<!doctype html>`, `<html>`, `<head>` ou `<body>` e ainda sem Layout Global Admin:

- `admin/admin.php`
- `admin/config.php`
- `admin/dashboard_pro.php`
- `admin/crm/inbox.php`
- `admin/crm/dashboard.php`
- `admin/carros/carro_fotos.php`
- `admin/leads/ver_lead.php`
- `admin/leads/listar_leads.php`
- `admin/leads/lead_detalhe.php`
- `admin/leads/leads.php`
- `admin/relatorio_vendedores.php`
- `admin/user_dashboard.php`
- `admin/vendas/confirmar_venda.php`
- `admin/vendas/marcar_venda.php`
- `admin/vendas/vendedores_pedidos.php`
- `admin/vendas/vendedor_ver.php`

Páginas admin com layout antigo, mas sem HTML standalone completo detectado nesta passagem:

- `admin/dashboard_carros.php`
- `admin/dashboard_vendas.php`
- `admin/admin_saques.php`
- `admin/funil.php`
- `admin/painel_inteligente.php`

## Blocos `<style>` ainda existentes

Arquivos admin com `<style>`:

- `admin/config.php`
- `admin/crm/inbox.php`
- `admin/crm/dashboard.php`
- `admin/admin.php`
- `admin/carros/carro_fotos.php`
- `admin/relatorio_vendedores.php`
- `admin/funil.php`
- `admin/vendas/vendedor_ver.php`
- `admin/leads/ver_lead.php`
- `admin/leads/listar_leads.php`
- `admin/vendas/vendedores_pedidos.php`
- `admin/user_dashboard.php`

Arquivos fora de admin com `<style>` relevantes para futura limpeza:

- `app/modules/finance/custos.php`
- `app/modules/finance/recibo.php`
- `app/modules/leads/ver_lead.php`
- `app/modules/leads/listar_leads.php`
- `app/modules/cars/adicionar_carro.php`
- `app/modules/cars/carro_fotos.php`
- `app/modules/cars/editar_carro.php`
- `app/modules/cars/listar_carros.php`
- `views/crm/pipeline.php`
- várias páginas em `public/`

## Estilos inline ainda existentes

Arquivos admin com `style=` relevantes:

- `admin/dashboard_carros.php`
- `admin/admin_leasing.php`
- `admin/admin_saques.php`
- `admin/admin.php`
- `admin/carros/carro_fotos.php`
- `admin/leads/adicionar_lead.php`
- `admin/leads/leads.php`
- `admin/painel_inteligente.php`
- `admin/dashboard_vendas.php`
- `admin/funil.php`
- `admin/crm/inbox.php`
- `admin/crm/dashboard.php`
- `admin/vendas/marcar_venda.php`
- `admin/vendas/vendedores_pedidos.php`
- `admin/vendas/vendedor_ver.php`

Observação: `admin/crm/dashboard.php` usa `style="width:..."` para barras de gráfico. Esse caso pode continuar como inline dinâmico ou ser migrado para CSS custom property.

## CSS duplicado

Ainda existem padrões duplicados:

- Bootstrap CDN carregado diretamente em páginas standalone.
- `admin-modern.css` carregado diretamente por páginas que ainda não usam `admin_layout.php`.
- `style.css` legado ainda aparece em páginas/módulos antigos.
- Blocos `<style>` repetem card, table, body background, layout grid e botões.
- Estilos inline ainda fazem papel de componentes reutilizáveis em dashboards, funil, CRM, galeria e vendedores.

## Riscos antes do deploy beta

1. Dois layouts admin em paralelo
   - `admin_layout.php` e `includes/layout_top.php` ainda coexistem.
   - Risco de navegação, sidebar, topbar e estilos inconsistentes.

2. CRM e Leads ainda standalone
   - `admin/crm/inbox.php`, `admin/crm/dashboard.php` e páginas de leads ainda não foram migradas.
   - São áreas críticas para operação comercial.

3. Páginas auxiliares de vendas ainda fora do padrão
   - `admin/vendas/confirmar_venda.php`
   - `admin/vendas/marcar_venda.php`
   - `admin/vendas/vendedores_pedidos.php`
   - `admin/vendas/vendedor_ver.php`

4. Galeria de fotos ainda standalone
   - `admin/carros/carro_fotos.php` ainda tem HTML completo e CSS próprio.
   - Risco visual no fluxo de gestão de estoque.

5. Rotas espelhadas em `app/modules`
   - Especialmente carros e leads.
   - Risco de corrigir uma rota e o utilizador acessar outra versão antiga.

6. CSS concorrente
   - Bootstrap direto, `admin-modern.css`, `style.css`, `<style>` e `style=` convivem.
   - Risco de regressões visuais difíceis de rastrear.

7. Encoding legado
   - Ainda há textos antigos com mojibake em várias páginas não migradas.
   - Risco de aparência pouco profissional no beta.

8. Ações sensíveis misturadas com UI
   - Algumas páginas ainda misturam POST, regras de negócio e HTML/CSS.
   - Deve-se migrar com cuidado para não alterar status, pagamentos, aprovações, comissões ou CRM.

## Próxima ordem recomendada

1. `admin/crm/dashboard.php`
2. `admin/crm/inbox.php`
3. `admin/leads/leads.php`
4. `admin/leads/listar_leads.php`
5. `admin/leads/ver_lead.php`
6. `admin/carros/carro_fotos.php`
7. `admin/painel_inteligente.php`
8. `admin/funil.php`
9. `admin/vendas/vendedores_pedidos.php`
10. `admin/vendas/vendedor_ver.php`
11. `admin/vendas/marcar_venda.php`
12. `admin/vendas/confirmar_venda.php`
13. `admin/dashboard_vendas.php`
14. `admin/dashboard_carros.php`
15. `admin/admin_saques.php`

## Conclusão

O núcleo operacional está mais consistente do que na auditoria inicial: Dashboard, Clientes, Carros, Financeiro e o fluxo principal de Vendas já usam o Layout Global Admin. Para um beta mais seguro, a maior prioridade agora é CRM/Leads, porque ainda concentram páginas standalone e são centrais para acompanhamento comercial. Em seguida, migrar galeria de fotos e páginas auxiliares de vendas reduzirá os pontos visuais legados mais visíveis.
