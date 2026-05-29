# Auditoria v4 - Layout Global Admin

Data: 2026-05-24

## Resumo

Auditoria estática rápida após as migrações do módulo Leads:

- `admin/leads/leads.php`
- `admin/leads/ver_lead.php`

Estado atual:

- 15 páginas admin já usam `app/views/layouts/admin_layout.php`.
- O núcleo principal de Leads avançou: lista principal e detalhe operacional (`ver_lead.php`) já estão no Layout Global Admin.
- Ainda existem páginas admin standalone, principalmente listagem legada de leads, detalhe legado de lead, galeria de fotos, vendas auxiliares, relatórios e dashboards antigos.
- Ainda existem usos de `includes/layout_top.php` / `includes/layout_bottom.php` em rotas legadas.
- Ainda há blocos `<style>` e estilos inline em páginas antigas.
- Não foi feita alteração de código da aplicação nesta auditoria.

## Páginas admin já migradas

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
- `admin/leads/leads.php`
- `admin/leads/ver_lead.php`

## Páginas admin ainda standalone

Ainda têm HTML standalone (`<!doctype html>`, `<html>`, `<head>` ou `<body>`) e não usam o Layout Global Admin:

- `admin/admin.php`
- `admin/config.php`
- `admin/dashboard_pro.php`
- `admin/relatorio_vendedores.php`
- `admin/user_dashboard.php`
- `admin/carros/carro_fotos.php`
- `admin/leads/listar_leads.php`
- `admin/leads/lead_detalhe.php`
- `admin/vendas/confirmar_venda.php`
- `admin/vendas/marcar_venda.php`
- `admin/vendas/vendedores_pedidos.php`
- `admin/vendas/vendedor_ver.php`

## Módulo Leads

Migradas:

- `admin/leads/leads.php`
- `admin/leads/ver_lead.php`

Ainda pendentes:

- `admin/leads/listar_leads.php`
  - Página standalone.
  - Contém bloco `<style>`.
  - Usa layout próprio completo.
  - Tem links de WhatsApp, detalhe e venda.
- `admin/leads/lead_detalhe.php`
  - Página standalone.
  - Detalhe simples/legado de lead.
- `admin/leads/adicionar_lead.php`
  - Não apareceu como HTML standalone completo nesta varredura, mas ainda tem `style=` inline em mensagem de erro.

## Usos restantes de `includes/layout_top.php` / `layout_bottom.php`

Ainda dependem do layout antigo:

- `admin/dashboard_vendas.php`
- `admin/dashboard_carros.php`
- `admin/painel_inteligente.php`
- `admin/admin_saques.php`
- `admin/funil.php`
- `public/dashboard.php`
- `app/modules/cars/listar_carros.php`
- `app/modules/cars/editar_carro.php`

Observações:

- `public/dashboard.php` está fora do admin canônico, mas ainda usa o layout antigo.
- `app/modules/cars/*` parecem rotas espelhadas/legadas dos carros já migrados em `admin/carros/*`.
- `admin/funil.php` é relevante para CRM/Leads e ainda usa layout antigo.

## Blocos `<style>` restantes

Admin:

- `admin/admin.php`
- `admin/carros/carro_fotos.php`
- `admin/config.php`
- `admin/relatorio_vendedores.php`
- `admin/leads/listar_leads.php`
- `admin/user_dashboard.php`
- `admin/vendas/vendedores_pedidos.php`
- `admin/vendas/vendedor_ver.php`
- `admin/funil.php`

## Estilos inline restantes

Admin e views admin com `style=`:

- `admin/painel_inteligente.php`
- `admin/leads/adicionar_lead.php`
- `admin/admin_saques.php`
- `admin/admin_leasing.php`
- `admin/funil.php`
- `admin/admin.php`
- `admin/carros/carro_fotos.php`
- `admin/vendas/vendedor_ver.php`
- `admin/vendas/marcar_venda.php`
- `admin/dashboard_carros.php`
- `admin/vendas/vendedores_pedidos.php`
- `admin/dashboard_vendas.php`
- `app/views/admin/crm/dashboard_content.php`

Observação:

- `app/views/admin/crm/dashboard_content.php` mantém `style="width:...%"` para largura dinâmica do funil. É aceitável como valor calculado de visualização, mas pode ser convertido para CSS custom property em uma limpeza futura.

## Próximas páginas críticas antes do deploy beta

1. `admin/leads/listar_leads.php`
   - Próxima prioridade natural do módulo Leads.
   - Ainda standalone e com `<style>`.
   - Pode duplicar experiência já modernizada em `admin/leads/leads.php`.

2. `admin/leads/lead_detalhe.php`
   - Detalhe legado simples.
   - Deve ser migrado ou redirecionado de forma controlada para `ver_lead.php`, se for comprovado que é rota duplicada.

3. `admin/carros/carro_fotos.php`
   - Galeria/upload de fotos ainda standalone.
   - Alto impacto visual e operacional no estoque.

4. `admin/funil.php`
   - CRM/funil ainda usa layout antigo.
   - Importante por ser rota comercial e ligada a Leads.

5. `admin/vendas/marcar_venda.php`
   - Fluxo auxiliar sensível de vendas.
   - Deve ser migrado com cuidado para não alterar regras comerciais.

6. `admin/vendas/confirmar_venda.php`
   - Fluxo sensível de confirmação.
   - Exige cuidado com regras financeiras/status.

7. `admin/vendas/vendedores_pedidos.php` e `admin/vendas/vendedor_ver.php`
   - Ainda têm `<style>` e/ou inline style.
   - Risco de inconsistência visual em rotas comerciais.

8. Dashboards/relatórios antigos:
   - `admin/dashboard_vendas.php`
   - `admin/dashboard_carros.php`
   - `admin/dashboard_pro.php`
   - `admin/relatorio_vendedores.php`
   - `admin/painel_inteligente.php`

## Riscos antes do beta

- Layout duplo ainda coexistindo: `admin_layout.php` e `includes/layout_top.php`.
- Rotas de Leads ainda duplicadas: `leads.php`, `listar_leads.php`, `ver_lead.php`, `lead_detalhe.php`.
- CSS concorrente entre `admin-modern.css`, blocos `<style>`, estilos inline e CSS legado.
- Páginas comerciais auxiliares de vendas ainda fora do padrão global.
- Galeria de fotos ainda fora do padrão global.
- Rotas espelhadas em `app/modules/*` podem expor visual antigo se acessadas diretamente.
- Alguns textos antigos ainda podem conter problemas de encoding/mojibake.

## Conclusão

A migração de Leads avançou de forma importante: a lista principal e o detalhe operacional com mensagens/follow-up já usam o Layout Global Admin. Para reduzir risco antes do deploy beta, a próxima ação recomendada é migrar `admin/leads/listar_leads.php`, depois decidir se `admin/leads/lead_detalhe.php` deve ser migrado ou substituído por uma rota canônica para `admin/leads/ver_lead.php`.
