# Auditoria - Layout Admin Global

Data: 2026-05-24

## Resumo

Foi feita uma auditoria estática para identificar páginas admin que ainda usam layout antigo, HTML completo próprio, CSS inline ou includes duplicados.

Estado atual:

- Layout Global Admin criado e em uso em 8 páginas admin.
- Ainda existem páginas admin importantes usando `includes/layout_top.php` / `includes/layout_bottom.php`.
- Ainda existem várias páginas admin com `<!doctype html>`, `<head>`, `<body>` e CSS inline próprio.
- Existem módulos espelhados em `app/modules/*` que ainda usam layout antigo ou HTML standalone.
- Não foi feita nenhuma alteração funcional nesta auditoria.

## Páginas já migradas para Layout Global Admin

Estas páginas já usam `app/views/layouts/admin_layout.php`:

- `admin/dashboard.php`
- `admin/clientes/clientes.php`
- `admin/clientes/cliente_detalhe.php`
- `admin/carros/listar_carros.php`
- `admin/carros/adicionar_carro.php`
- `admin/carros/editar_carro.php`
- `admin/vendas/vendas.php`
- `admin/financeiro/dashboard_financeiro.php`

Views criadas em `app/views/admin/`:

- `app/views/admin/dashboard/dashboard_content.php`
- `app/views/admin/clientes/clientes_content.php`
- `app/views/admin/clientes/cliente_detalhe_content.php`
- `app/views/admin/carros/listar_carros_content.php`
- `app/views/admin/carros/adicionar_carro_content.php`
- `app/views/admin/carros/editar_carro_content.php`
- `app/views/admin/vendas/vendas_content.php`
- `app/views/admin/financeiro/financeiro_content.php`

## Includes antigos ainda usados

Ainda usam `includes/layout_top.php` e/ou `includes/layout_bottom.php`:

- `admin/dashboard_vendas.php`
- `admin/dashboard_carros.php`
- `admin/admin_saques.php`
- `admin/painel_inteligente.php`
- `admin/funil.php`
- `public/dashboard.php`
- `app/modules/cars/listar_carros.php`
- `app/modules/cars/editar_carro.php`

Observação: `public/dashboard.php` não é admin, mas ainda depende do layout antigo. Os módulos em `app/modules/cars` parecem espelhos/rotas alternativas dos carros e precisam de decisão arquitetural antes de migrar ou descontinuar.

## Páginas admin ainda não migradas

Páginas admin com HTML completo próprio (`<!doctype html>`, `<html>`, `<head>` ou `<body>`) e ainda sem `admin_layout.php`:

- `admin/admin.php`
- `admin/config.php`
- `admin/dashboard_pro.php`
- `admin/crm/inbox.php`
- `admin/crm/dashboard.php`
- `admin/carros/carro_fotos.php`
- `admin/relatorio_vendedores.php`
- `admin/leads/ver_lead.php`
- `admin/leads/listar_leads.php`
- `admin/leads/lead_detalhe.php`
- `admin/leads/leads.php`
- `admin/user_dashboard.php`
- `admin/vendas/confirmar_venda.php`
- `admin/vendas/marcar_venda.php`
- `admin/vendas/editar_venda.php`
- `admin/vendas/nova_venda.php`
- `admin/vendas/vendedores_pedidos.php`
- `admin/vendas/venda_detalhe.php`
- `admin/vendas/vendedor_ver.php`

Páginas admin que usam layout antigo, mas não necessariamente HTML completo próprio:

- `admin/dashboard_vendas.php`
- `admin/dashboard_carros.php`
- `admin/admin_saques.php`
- `admin/painel_inteligente.php`
- `admin/funil.php`

Endpoints/ações admin que provavelmente não precisam de layout visual, mas devem ser mantidos sob revisão:

- `admin/apagar_foto.php`
- `admin/aprovacoes.php`
- `admin/config_admin.php`
- `admin/criar_user.php`
- `admin/cron_liberar_saldo.php`
- `admin/gerir_fotos.php`
- `admin/logout.php`
- `admin/mover_foto.php`
- `admin/mudar_estado.php`
- `admin/salvar_interacao.php`
- `admin/salvar_ordem_fotos.php`
- `admin/send_message.php`
- `admin/status.php`
- `admin/webhook_receiver.php`
- `admin/whatsapp_redirect.php`
- `admin/whatsapp/send.php`
- `admin/whatsapp/webhook.php`
- `admin/vendas/pagar_venda.php`
- `admin/vendas/aprovar_venda.php`
- `admin/vendas/rejeitar_venda.php`
- `admin/vendas/atualizar_venda.php`
- `admin/vendas/vendedor_apagar.php`
- `admin/vendas/vendedor_converter.php`
- `admin/vendas/vendedor_status.php`
- `admin/vendas/vender.php`
- `admin/carros/apagar_carro.php`
- `admin/carros/carro_save.php`
- `admin/carros/carro_fotos_delete.php`
- `admin/carros/carro_fotos_order.php`

## CSS duplicado e inline

Arquivos admin com blocos `<style>` próprios:

- `admin/admin.php`
- `admin/config.php`
- `admin/crm/inbox.php`
- `admin/crm/dashboard.php`
- `admin/funil.php`
- `admin/relatorio_vendedores.php`
- `admin/leads/ver_lead.php`
- `admin/leads/listar_leads.php`
- `admin/carros/carro_fotos.php`
- `admin/user_dashboard.php`
- `admin/vendas/editar_venda.php`
- `admin/vendas/nova_venda.php`
- `admin/vendas/venda_detalhe.php`
- `admin/vendas/vendedores_pedidos.php`
- `admin/vendas/vendedor_ver.php`

Arquivos admin com estilos inline (`style=`) relevantes:

- `admin/admin.php`
- `admin/admin_leasing.php`
- `admin/admin_saques.php`
- `admin/dashboard_carros.php`
- `admin/dashboard_vendas.php`
- `admin/funil.php`
- `admin/painel_inteligente.php`
- `admin/crm/inbox.php`
- `admin/crm/dashboard.php`
- `admin/leads/leads.php`
- `admin/carros/carro_fotos.php`
- `admin/vendas/marcar_venda.php`
- `admin/vendas/vendedores_pedidos.php`
- `admin/vendas/vendedor_ver.php`

CSS/links duplicados em páginas admin:

- Bootstrap CDN carregado diretamente em várias páginas, apesar de o layout global já carregar Bootstrap.
- `admin-modern.css` carregado diretamente em páginas ainda standalone (`admin/crm/inbox.php`, `admin/crm/dashboard.php`, `admin/vendas/nova_venda.php`, `admin/vendas/venda_detalhe.php`).
- `style.css` legado ainda aparece em `admin/admin.php` e em módulos `app/modules/cars/*`.

## Includes antigos

Arquivos centrais antigos ainda presentes:

- `includes/layout_top.php`
- `includes/layout_bottom.php`

Risco: enquanto existirem páginas que usam esses includes, haverá dois sistemas de layout convivendo. Isso aumenta chance de:

- menus divergentes;
- botões/topbar inconsistentes;
- CSS sobrescrevendo estilos modernos;
- links rápidos desatualizados;
- comportamento mobile diferente entre módulos.

## Módulos espelhados fora de admin

Também foram encontrados HTML standalone ou layout antigo em:

- `app/modules/cars/listar_carros.php`
- `app/modules/cars/editar_carro.php`
- `app/modules/cars/carro_fotos.php`
- `app/modules/cars/adicionar_carro.php`
- `app/modules/cars/actions/vender_carro.php`
- `app/modules/leads/*`
- `app/modules/finance/custos.php`
- `app/modules/finance/recibo.php`
- `app/modules/finance/marcar_pago.php`

Recomendação: antes de migrar esses módulos, decidir se são rotas canónicas, wrappers legados ou cópias antigas. Migrar cópias duplicadas sem essa decisão pode duplicar manutenção.

## Riscos antes do deploy

1. Layout duplo em produção
   - O sistema já tem Layout Global Admin, mas páginas importantes ainda usam `includes/layout_top.php`.

2. Rotas duplicadas / espelhadas
   - Existem versões em `admin/*` e `app/modules/*`, especialmente carros, leads e financeiro.
   - Risco de corrigir uma rota e o utilizador cair noutra.

3. CSS conflitante
   - `admin-modern.css`, Bootstrap CDN, `style.css`, blocos `<style>` e `style=` coexistem.
   - Risco de regressões visuais difíceis de rastrear.

4. Encoding legado
   - Algumas páginas antigas ainda têm mojibake em textos (`PreÃ§o`, `AÃ§Ãµes`, etc.).
   - Risco visual e de confiança para utilizadores internos.

5. Páginas críticas ainda standalone
   - CRM, Leads, Nova Venda, Detalhe da Venda, Editar Venda e Galeria de Fotos ainda não estão no Layout Global Admin.

6. Ações misturadas com telas
   - Algumas páginas combinam POST, HTML, CSS e regras de negócio no mesmo ficheiro.
   - Risco alto ao mexer em pagamentos, comissões, aprovações e status.

## Próxima ordem recomendada de migração

1. `admin/vendas/nova_venda.php`
2. `admin/vendas/venda_detalhe.php`
3. `admin/vendas/editar_venda.php`
4. `admin/crm/dashboard.php`
5. `admin/crm/inbox.php`
6. `admin/leads/leads.php`
7. `admin/leads/listar_leads.php`
8. `admin/leads/ver_lead.php`
9. `admin/carros/carro_fotos.php`
10. `admin/painel_inteligente.php`
11. `admin/dashboard_vendas.php`
12. `admin/dashboard_carros.php`

## Conclusão

A base do Layout Global Admin já está funcional e aplicada nas páginas principais de dashboard, clientes, carros, vendas e financeiro. O maior trabalho restante é migrar CRM, Leads, detalhes/edição de vendas e páginas auxiliares que ainda carregam HTML completo ou layout antigo. Antes do deploy, a prioridade deve ser eliminar `includes/layout_top.php` das rotas admin canónicas e reduzir CSS inline nas páginas críticas.
