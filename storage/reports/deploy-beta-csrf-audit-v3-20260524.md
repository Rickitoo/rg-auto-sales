# Auditoria CSRF v3 - Pos Correcoes 13 a 15

Data: 2026-05-25  
Projeto: RG Auto Sales  
Escopo: revalidacao dos P0 restantes em vendas, leads/status, fotos e `admin/cron_liberar_saldo.php` apos as correcoes 13, 14 e 15.  
Resultado: auditoria apenas informativa. Nenhum codigo foi alterado.

## Resumo executivo

As correcoes 13, 14 e 15 removeram os P0 de CSRF que ainda estavam abertos no bloco de vendas da auditoria v2:

- `admin/vendas/atualizar_venda.php`
- `admin/vendas/marcar_venda.php`
- `admin/vendas/confirmar_venda.php`
- `admin/vendas/vendedor_converter.php`

Nao foram encontrados links GET antigos restantes para essas rotas corrigidas.

Ainda existem P0 reais antes do GO beta privado em:

- leads/status;
- fotos;
- `admin/cron_liberar_saldo.php`.

## Vendas - P0 revalidados

| Arquivo | Acao perigosa | Estado atual | CSRF | Status |
|---|---|---|---:|---|
| `admin/vendas/atualizar_venda.php` | Atualiza valores financeiros, comissoes, aprovacao e status | Exige POST antes do update | Sim, `hash_equals()` | Corrigido |
| `admin/vendas/marcar_venda.php` | Cria venda, marca carro como vendido e fecha lead | Exige POST antes de buscar lead/carro e antes de insert/update | Sim, `hash_equals()` e `csrf_input()` no formulario final | Corrigido |
| `admin/vendas/confirmar_venda.php` | Confirma venda a partir de lead e marca carro como vendido | Exige POST antes de buscar lead/carro e antes de insert/update | Sim, `hash_equals()` e `csrf_input()` no formulario final | Corrigido |
| `admin/vendas/vendedor_converter.php` | Converte pedido de vendedor em cliente/venda | Exige POST antes de criar cliente/venda | Sim, `hash_equals()` | Corrigido |

Evidencias:

- `admin/vendas/atualizar_venda.php:10-18` valida metodo POST e `csrf_token` antes de ler `$_POST` e executar `UPDATE vendas`.
- `admin/vendas/marcar_venda.php:12-20` valida metodo POST e `csrf_token`; o formulario final inclui `csrf_input()` em `admin/vendas/marcar_venda.php:225`.
- `admin/vendas/confirmar_venda.php:12-20` valida metodo POST e `csrf_token`; o formulario final inclui `csrf_input()` em `admin/vendas/confirmar_venda.php:194`.
- `admin/vendas/vendedor_converter.php:10-18` valida metodo POST e `csrf_token` antes de converter o pedido.
- Busca por `atualizar_venda.php?`, `marcar_venda.php?`, `confirmar_venda.php?`, `vendedor_converter.php?`, `pagar_venda.php?`, `aprovar_venda.php?` e `rejeitar_venda.php?` em `admin`, `app` e `public` nao retornou ocorrencias.

Conclusao do bloco: nenhum P0 restante encontrado no escopo de vendas revalidado.

## Leads/status - P0 restantes

| Arquivo | Acao perigosa | Metodo atual | CSRF | Prioridade |
|---|---|---:|---:|---|
| `admin/leads/leads_status.php` | Altera status do lead | GET `id`, `s` | Nao | P0 |
| `app/modules/leads/leads_status.php` | Altera status do lead | GET `id`, `s` | Nao | P0 |
| `admin/leads/leads_status_ajax.php` | Atualiza status, ultimo contato e proximo follow-up | POST | Nao | P0 |
| `app/modules/leads/leads_status_ajax.php` | Atualiza status, ultimo contato e proximo follow-up | POST | Nao | P0 |
| `admin/leads/lead_move.php` | Move lead entre status e pode redirecionar para venda | POST | Nao | P0 |
| `app/modules/leads/lead_move.php` | Move lead entre status e pode redirecionar para venda | POST | Nao | P0 |

Evidencias:

- `admin/leads/leads_status.php:15-16` le `$_GET['id']` e `$_GET['s']`; `admin/leads/leads_status.php:32` executa `UPDATE leads`.
- `app/modules/leads/leads_status.php:15-16` repete o mesmo padrao GET; `app/modules/leads/leads_status.php:32` executa `UPDATE leads`.
- `admin/leads/leads_status_ajax.php:17-18` le POST sem token; `admin/leads/leads_status_ajax.php:28`, `:36` e `:44` atualizam `leads`.
- `app/modules/leads/leads_status_ajax.php:17-18` le POST sem token; `app/modules/leads/leads_status_ajax.php:28`, `:36` e `:44` atualizam `leads`.
- `admin/leads/lead_move.php:7` bloqueia metodo diferente de POST, mas `admin/leads/lead_move.php:13-14` le `id/status` sem CSRF e `admin/leads/lead_move.php:23` executa `UPDATE leads`.
- `app/modules/leads/lead_move.php:15` bloqueia metodo diferente de POST, mas `app/modules/leads/lead_move.php:21-22` le `id/status` sem CSRF e `app/modules/leads/lead_move.php:32` executa `UPDATE leads`.

Chamadas reais ainda encontradas:

- `app/views/admin/leads/listar_leads_content.php:87`, `:94`, `:101`, `:108` contem links GET para `leads_status.php?id=...&s=...`.
- `app/modules/leads/listar_leads.php:308`, `:316`, `:324`, `:332` contem links GET para `leads_status.php?id=...&s=...`.
- `admin/funil.php:130` chama `admin/leads/lead_move.php` via POST sem enviar `csrf_token` nesse request. O token so e usado depois, no POST dinamico para `confirmar_venda.php`.
- `admin/leads/leads_status_ajax.php:65` e `app/modules/leads/leads_status_ajax.php:65` chamam `leads_status_ajax.php` via POST sem token.

## Fotos - P0 restantes

| Arquivo | Acao perigosa | Metodo atual | CSRF | Prioridade |
|---|---|---:|---:|---|
| `admin/carros/carro_fotos_delete.php` | Remove foto do carro, atualiza capa e apaga arquivo fisico | JSON/POST | Nao | P0 |
| `admin/apagar_foto.php` | Remove foto e apaga arquivo fisico | GET `id`, `carro_id` | Nao | P0 |

Evidencias:

- `admin/carros/carro_fotos_delete.php:30-31` le JSON de `php://input` sem token.
- `admin/carros/carro_fotos_delete.php:61` executa `DELETE FROM carros_fotos`.
- `admin/carros/carro_fotos_delete.php:86` e `:93` atualizam a capa do carro.
- `admin/carros/carro_fotos_delete.php:105` apaga o arquivo fisico com `@unlink`.
- `admin/carros/carro_fotos.php:393` e `app/modules/cars/carro_fotos.php:393` chamam `carro_fotos_delete.php` via POST JSON enviando apenas `{ id }`.
- `admin/apagar_foto.php:10-11` le `id` e `carro_id` via GET.
- `admin/apagar_foto.php:25` apaga o arquivo fisico e `admin/apagar_foto.php:28` remove o registro em `carros_fotos`.

Observacao: `admin/apagar_foto.php` nao apareceu como chamador ativo na busca atual por `apagar_foto.php?`, mas a rota existe, e uma requisicao GET autenticada ainda executa remocao sem CSRF.

## Cron financeiro - P0 restante

| Arquivo | Acao perigosa | Metodo atual | CSRF | Prioridade |
|---|---|---:|---:|---|
| `admin/cron_liberar_saldo.php` | Move saldo pendente para disponivel e marca vendas como processadas | Qualquer request autenticado | Nao | P0 |

Evidencias:

- `admin/cron_liberar_saldo.php:3` exige apenas `require_admin()`.
- Nao ha validacao de metodo, `csrf_token`, token operacional ou execucao CLI-only.
- `admin/cron_liberar_saldo.php:19` atualiza `wallet`, movendo saldo pendente para saldo disponivel.
- `admin/cron_liberar_saldo.php:27` marca vendas como processadas.

Observacao adicional: o arquivo continua executando outras consultas/alertas depois do processamento financeiro e contem uso de `$valor` fora do loop. Isso nao e CSRF diretamente, mas reforca que a rota nao deveria ficar acessivel como endpoint web comum.

## Proxima ordem recomendada

1. `admin/leads/lead_move.php` e `app/modules/leads/lead_move.php`
   - Sao POST e ja usados por funil/fluxo interativo; adicionar CSRF no request atual.
2. `admin/leads/leads_status.php` e `app/modules/leads/leads_status.php`
   - Remover mudanca por GET e trocar links por POST com `csrf_input()`.
3. `admin/leads/leads_status_ajax.php` e `app/modules/leads/leads_status_ajax.php`
   - Exigir POST com token e enviar `csrf_token` no `fetch`.
4. `admin/carros/carro_fotos_delete.php`
   - Exigir token no JSON/header e enviar token em `admin/carros/carro_fotos.php` e `app/modules/cars/carro_fotos.php`.
5. `admin/apagar_foto.php`
   - Transformar em POST com CSRF ou desativar a rota se for legado sem chamador.
6. `admin/cron_liberar_saldo.php`
   - Tornar CLI-only ou exigir token operacional fora da sessao web comum.

## Riscos antes do deploy beta

- Ainda e possivel alterar status de leads por GET autenticado sem token.
- Ainda e possivel mover leads no funil por POST sem CSRF.
- Ainda e possivel alterar status/follow-up de leads via AJAX sem CSRF.
- Ainda e possivel remover fotos/registros/arquivos fisicos por requests autenticados sem CSRF.
- O cron financeiro continua exposto como rota web autenticada e executa mutacoes em lote sem token operacional.

## Conclusao

As correcoes 13, 14 e 15 fecham o bloco P0 de vendas que estava pendente na auditoria v2. O GO beta privado ainda deve aguardar a correcao dos P0 restantes em leads/status, fotos e `admin/cron_liberar_saldo.php`.
