# Relatorio de rotas canonicas - RG_AUTO_SALES

Data: 2026-05-23

## Criterio adotado

- Rotas navegaveis e administrativas ficam canonicas em `admin/*`.
- Rotas publicas ficam canonicas em `public/*`.
- Autenticacao fica canonica em `auth/*`.
- `app/modules/*` deve evoluir para camada interna de modulo/helper/servico, nao como entrada direta de navegador.
- `views/*` deve ser tratado como legado temporario enquanto telas reais estiverem em `admin/*` ou `public/*`.
- Acoes POST, AJAX, download ou upload nao foram convertidas automaticamente para redirect para evitar perda de payload ou mudanca de metodo HTTP.

## Dashboard

| Item | Resultado |
| --- | --- |
| Rota atual usada | `admin/dashboard.php`, com atalhos secundarios em `admin/dashboard_carros.php`, `admin/dashboard_vendas.php`, `admin/dashboard_pro.php`, `admin/painel_inteligente.php` |
| Arquivos duplicados existentes | Nao ha equivalente direto em `app/modules/*`; existem dashboards especializados em `admin/*` |
| Rota canonica recomendada | `admin/dashboard.php` |
| Arquivos que devem virar redirect | Nenhum agora; dashboards especializados devem ser avaliados como relatorios/subpaineis antes de redirecionar |
| Remocao futura | `admin/dashboard_carros.php`, `admin/dashboard_vendas.php`, `admin/dashboard_pro.php`, `admin/painel_inteligente.php`, apenas se suas metricas forem incorporadas ao dashboard principal |
| Riscos antes da remocao | Perda de indicadores especificos usados por operacao comercial ou gestao |

## Carros

| Item | Resultado |
| --- | --- |
| Rota atual usada | Menu e links principais usam `admin/carros/listar_carros.php`, `admin/carros/adicionar_carro.php`, `admin/carros/editar_carro.php`, `admin/carros/carro_fotos.php` e acoes de `admin/carros/*` |
| Arquivos duplicados existentes | `app/modules/cars/listar_carros.php`, `app/modules/cars/adicionar_carro.php`, `app/modules/cars/editar_carro.php`, `app/modules/cars/apagar_carro.php`, `app/modules/cars/carro_save.php`, `app/modules/cars/carro_fotos.php`, `app/modules/cars/carro_fotos_delete.php`, `app/modules/cars/carro_fotos_order.php`, `app/modules/cars/actions/delete.php`, `app/modules/cars/actions/vender_carro.php`, `actions/criar_carro.php` |
| Rota canonica recomendada | Telas: `admin/carros/listar_carros.php`, `admin/carros/adicionar_carro.php`, `admin/carros/editar_carro.php`; fotos: `admin/carros/carro_fotos.php` ou `admin/gerir_fotos.php`, a consolidar depois |
| Arquivos que devem virar redirect | `app/modules/cars/listar_carros.php` ja redireciona para `admin/carros/listar_carros.php`; proximos candidatos: `app/modules/cars/editar_carro.php` somente para GET, `app/modules/cars/carro_fotos.php` somente para GET |
| Remocao futura | Duplicatas em `app/modules/cars/*.php` apos confirmar que nenhum formulario/action publica aponta para elas |
| Riscos antes da remocao | `adicionar_carro.php`, `editar_carro.php`, `carro_save.php`, `apagar_carro.php`, fotos delete/order e actions recebem POST/GET destrutivo; redirect simples pode perder dados ou mudar comportamento |

## Leads

| Item | Resultado |
| --- | --- |
| Rota atual usada | Menu principal usa `admin/leads/leads.php`; listas e detalhes tambem existem em `admin/leads/listar_leads.php`, `admin/leads/ver_lead.php`, `admin/leads/lead_detalhe.php`; CRM kanban usa `admin/funil.php` |
| Arquivos duplicados existentes | `app/modules/leads/leads.php`, `app/modules/leads/listar_leads.php`, `app/modules/leads/ver_lead.php`, `app/modules/leads/lead_detalhe.php`, `app/modules/leads/adicionar_lead.php`, `app/modules/leads/leads_status.php`, `app/modules/leads/leads_status_ajax.php`, `app/modules/leads/lead_move.php`, `app/modules/leads/actions/update_status.php`, `views/crm/pipeline.php`, `views/api/move_stage.php` |
| Rota canonica recomendada | Listagem: `admin/leads/leads.php`; lista operacional: `admin/leads/listar_leads.php`; detalhe: `admin/leads/ver_lead.php`; pipeline CRM: `admin/funil.php` |
| Arquivos que devem virar redirect | `app/modules/leads/leads.php`, `app/modules/leads/listar_leads.php`, `app/modules/leads/lead_detalhe.php` e `views/crm/pipeline.php` ja redirecionam para as rotas canonicas |
| Remocao futura | Duplicatas de tela em `app/modules/leads/*` e `views/crm/pipeline.php` depois de periodo de compatibilidade |
| Riscos antes da remocao | `ver_lead.php` possui POST para mensagens; `lead_move.php`, `leads_status_ajax.php`, `actions/update_status.php` e `views/api/move_stage.php` sao endpoints e devem ser migrados com teste funcional |

## Vendas

| Item | Resultado |
| --- | --- |
| Rota atual usada | `admin/vendas/vendas.php`, `admin/vendas/nova_venda.php`, `admin/vendas/venda_detalhe.php`, `admin/vendas/editar_venda.php` e acoes em `admin/vendas/*` |
| Arquivos duplicados existentes | `app/modules/sales/marcar_vendido.php`, `app/modules/sales/vender_sucesso.php`, `app/modules/cars/actions/vender_carro.php`, alem de acoes antigas relacionadas a venda de carro |
| Rota canonica recomendada | Administrativo: `admin/vendas/*`; fluxo publico de vender viatura deve ser consolidado em `public/vender_carro.php` quando existir como entrada publica oficial |
| Arquivos que devem virar redirect | Nenhum nesta rodada, porque os arquivos encontrados participam de fluxo POST/publico ou pagina de sucesso |
| Remocao futura | `app/modules/sales/marcar_vendido.php`, `app/modules/sales/vender_sucesso.php`, `app/modules/cars/actions/vender_carro.php` apos mapear formulario publico de venda |
| Riscos antes da remocao | Quebra de funil de venda, perda de confirmacao/sucesso e mudanca em redirects pos-cadastro |

## Financeiro

| Item | Resultado |
| --- | --- |
| Rota atual usada | Menu principal usa `admin/financeiro/dashboard_financeiro.php`; vendas usam acoes financeiras em `admin/vendas/*` |
| Arquivos duplicados existentes | `app/modules/finance/financeiro.php`, `app/modules/finance/custos.php`, `app/modules/finance/export_vendas_csv.php`, `app/modules/finance/marcar_pago.php`, `app/modules/finance/marcar_vendido.php`, `app/modules/finance/pedir_saque.php`, `app/modules/finance/recibo.php`, `app/modules/finance/relatorio.php` |
| Rota canonica recomendada | Dashboard: `admin/financeiro/dashboard_financeiro.php`; custos/recibos/relatorios devem ganhar rotas `admin/financeiro/*` antes de remover modulos antigos |
| Arquivos que devem virar redirect | `app/modules/finance/financeiro.php` ja redireciona para `admin/financeiro/dashboard_financeiro.php` |
| Remocao futura | Duplicatas em `app/modules/finance/*` depois de criar equivalentes em `admin/financeiro/*` ou mover para servicos internos |
| Riscos antes da remocao | Export CSV, recibo e marcar_pago podem ser usados por operacao financeira; precisam de teste de download, pagamento e detalhe de venda |

## Clientes

| Item | Resultado |
| --- | --- |
| Rota atual usada | `admin/clientes/clientes.php` |
| Arquivos duplicados existentes | Nao foram encontrados duplicados diretos em `app/modules/*`, `views/*` ou `actions/*` |
| Rota canonica recomendada | `admin/clientes/clientes.php` |
| Arquivos que devem virar redirect | Nenhum |
| Remocao futura | Nenhuma indicada nesta rodada |
| Riscos antes da remocao | Baixo, desde que novos cadastros de cliente nao estejam embutidos em vendas/leads |

## Test-drive

| Item | Resultado |
| --- | --- |
| Rota atual usada | `public/test_drive.php`, formulario para `public/Formulario_cliente.php`; existe tambem `public/salvar_testdrive.php` |
| Arquivos duplicados existentes | Nao ha duplicado direto em `admin/*`; links antigos para `Test_drive.html` ainda aparecem em paginas publicas |
| Rota canonica recomendada | `public/test_drive.php` para tela; `public/Formulario_cliente.php` ou `public/salvar_testdrive.php` deve ser escolhido como handler unico em etapa posterior |
| Arquivos que devem virar redirect | Se existir arquivo fisico `Test_drive.html`, deve redirecionar para `public/test_drive.php`; nesta rodada nao foi alterado porque nao entrou no lint PHP |
| Remocao futura | Handler duplicado de formulario que nao for escolhido como oficial |
| Riscos antes da remocao | Perder leads de test-drive se formulario ainda aponta para handler antigo |

## Autenticacao

| Item | Resultado |
| --- | --- |
| Rota atual usada | `auth/login.php`, `auth/processa_login.php`, `auth/processa_registo.php`, `auth/processa_leasing.php`, `auth/logout.php` |
| Arquivos duplicados existentes | `admin/logout.php` ainda existe como saida administrativa antiga |
| Rota canonica recomendada | `auth/*`; logout oficial: `auth/logout.php` |
| Arquivos que devem virar redirect | `admin/logout.php` pode redirecionar para `auth/logout.php` em etapa posterior, confirmando destino pos-logout |
| Remocao futura | `admin/logout.php` apos periodo de compatibilidade |
| Riscos antes da remocao | Diferenca no destino pos-logout (`public/account.php?logout=1` versus fluxo central de auth) |

## Paginas publicas

| Item | Resultado |
| --- | --- |
| Rota atual usada | `public/index.php`, `public/products.php`, `public/product-details.php`, `public/about.php`, `public/contacto.php`, `public/account.php`, `public/cart.php`, `public/leasing.php`, `public/test_drive.php` |
| Arquivos duplicados existentes | `app/modules/cars/actions/vender_carro.php` funciona como pagina/acao publica antiga; `views/success.php` e `views/error.php` sao telas genericas antigas |
| Rota canonica recomendada | `public/*` para toda entrada publica |
| Arquivos que devem virar redirect | `views/success.php` e `views/error.php` podem ser consolidados futuramente em paginas publicas ou flash messages; `app/modules/cars/actions/vender_carro.php` deve migrar para `public/vender_carro.php` se esse fluxo for mantido |
| Remocao futura | `views/success.php`, `views/error.php`, fluxo publico antigo em `app/modules/cars/actions/vender_carro.php` |
| Riscos antes da remocao | Links externos, mensagens de sucesso/erro e formularios publicos podem depender dessas URLs |

## Redirects aplicados nesta etapa

- `app/modules/cars/listar_carros.php` -> `admin/carros/listar_carros.php`
- `app/modules/leads/leads.php` -> `admin/leads/leads.php`
- `app/modules/leads/listar_leads.php` -> `admin/leads/listar_leads.php`
- `app/modules/leads/lead_detalhe.php` -> `admin/leads/lead_detalhe.php`
- `app/modules/finance/financeiro.php` -> `admin/financeiro/dashboard_financeiro.php`
- `views/crm/pipeline.php` -> `admin/funil.php`

Todos preservam query string.

## Pendencias recomendadas

- Padronizar links publicos antigos para `Test_drive.html`.
- Criar ou confirmar `public/vender_carro.php`, pois varias paginas publicas ja apontam para essa rota.
- Consolidar foto de carro entre `admin/carros/carro_fotos.php` e `admin/gerir_fotos.php`.
- Migrar endpoints de `app/modules/*/actions` com testes manuais de POST/AJAX antes de redirecionar.
- Avaliar dashboards administrativos secundarios antes de remover.
