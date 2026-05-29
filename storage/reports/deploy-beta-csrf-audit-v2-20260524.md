# Auditoria CSRF v2 - Pós Correções 09 a 12

Data: 2026-05-25  
Projeto: RG Auto Sales  
Escopo: revalidação dos bloqueadores P0 após as correções em `pagar_venda.php`, `aprovar_venda.php`, `rejeitar_venda.php` e verificação da rota inexistente `cancelar_venda.php`.  
Resultado: auditoria apenas informativa. Nenhum código foi alterado.

## Resumo executivo

As correções 09 a 12 removeram os riscos P0 de pagamento, aprovação e rejeição via GET sem CSRF.

Ainda existem P0 reais antes do GO beta privado em:

- vendas/criação/atualização/conversão;
- leads/status;
- fotos;
- `admin/cron_liberar_saldo.php`.

## P0 corrigidos após auditoria original

| Arquivo | Estado atual | Método | CSRF | Status |
|---|---|---:|---:|---|
| `admin/vendas/pagar_venda.php` | Exige POST antes de executar pagamento | POST | Sim, `hash_equals()` | Corrigido |
| `admin/vendas/aprovar_venda.php` | Exige POST antes de aprovar | POST | Sim, `hash_equals()` | Corrigido |
| `admin/vendas/rejeitar_venda.php` | Exige POST antes de rejeitar | POST | Sim, `hash_equals()` | Corrigido |
| `admin/vendas/cancelar_venda.php` | Rota não existe | N/A | N/A | Sem P0 ativo |

Busca atual não encontrou links GET diretos restantes para:

- `pagar_venda.php?id=...`
- `aprovar_venda.php?id=...`
- `rejeitar_venda.php?id=...`

## P0 restantes - vendas, pagamentos e aprovação

| Arquivo | Ação perigosa | Método atual | CSRF | Prioridade |
|---|---|---:|---:|---|
| `admin/vendas/atualizar_venda.php` | Atualiza valores financeiros, comissões, aprovação e status | POST | Não | P0 |
| `admin/vendas/marcar_venda.php` | Cria venda, marca carro como vendido e fecha lead | POST | Não | P0 |
| `admin/vendas/confirmar_venda.php` | Confirma venda a partir de lead, cria venda e marca carro como vendido | POST | Não | P0 |
| `admin/vendas/vendedor_converter.php` | Converte pedido de vendedor em cliente/venda e altera status do pedido | GET `id` | Não | P0 |

Observações:

- `admin/vendas/atualizar_venda.php` lê `$_POST` e executa `UPDATE vendas` sem token.
- `admin/vendas/marcar_venda.php` tem formulário POST sem `csrf_input()`.
- `admin/vendas/confirmar_venda.php` tem formulário POST sem `csrf_input()`.
- `admin/vendas/vendedor_converter.php` ainda executa criação de cliente/venda via GET.
- Há link GET real em `admin/vendas/vendedores_pedidos.php` para `vendedor_converter.php?id=...`.

## P0 restantes - leads/status

| Arquivo | Ação perigosa | Método atual | CSRF | Prioridade |
|---|---|---:|---:|---|
| `admin/leads/leads_status.php` | Altera status do lead | GET `id`, `s` | Não | P0 |
| `admin/leads/leads_status_ajax.php` | Atualiza status, último contato e próximo follow-up | POST | Não | P0 |
| `admin/leads/lead_move.php` | Move lead entre status e pode redirecionar para venda | POST | Não | P0 |

Chamadas GET reais ainda encontradas:

- `app/views/admin/leads/listar_leads_content.php`
  - `leads_status.php?id=...&s=contactado`
  - `leads_status.php?id=...&s=negociacao`
  - `leads_status.php?id=...&s=fechado`
  - `leads_status.php?id=...&s=perdido`
- `app/modules/leads/listar_leads.php`
  - mesmas chamadas legadas para `leads_status.php?id=...&s=...`

Observação: `lead_move.php` já bloqueia método diferente de POST, mas ainda não valida CSRF.

## P0 restantes - fotos

| Arquivo | Ação perigosa | Método atual | CSRF | Prioridade |
|---|---|---:|---:|---|
| `admin/carros/carro_fotos_delete.php` | Remove foto do carro, atualiza capa e apaga arquivo físico | JSON/POST | Não | P0 |
| `admin/apagar_foto.php` | Remove foto e apaga arquivo físico | GET `id`, `carro_id` | Não | P0 |

Observações:

- `admin/carros/carro_fotos_delete.php` lê JSON de `php://input`, executa `DELETE`, atualiza capa e remove arquivo sem token.
- `admin/apagar_foto.php` usa GET e monta SQL com ID convertido para inteiro, mas sem CSRF.

## P0 restante - estado/status de cliente

| Arquivo | Ação perigosa | Método atual | CSRF | Prioridade |
|---|---|---:|---:|---|
| `admin/mudar_estado.php` | Altera `clientes.estado` | GET `id`, `estado` | Não | P0 |

Observação: além de CSRF, `estado` entra diretamente na query SQL. Deve ser tratado com allowlist/prepared statement quando for corrigido.

## P0 restante - cron financeiro

| Arquivo | Ação perigosa | Método atual | CSRF | Prioridade |
|---|---|---:|---:|---|
| `admin/cron_liberar_saldo.php` | Move saldo pendente para disponível e marca vendas como processadas | Qualquer request autenticado | Não | P0 |

Observações:

- A rota executa alterações financeiras em lote ao ser acessada.
- Deve ser protegida antes do beta privado, preferencialmente tornando a execução CLI-only ou exigindo token operacional fora do fluxo web comum.

## Próxima ordem recomendada

1. `admin/vendas/atualizar_venda.php`
   - P0 financeiro direto por POST sem CSRF.
2. `admin/vendas/marcar_venda.php`
   - Cria venda, marca carro vendido e fecha lead.
3. `admin/vendas/confirmar_venda.php`
   - Cria venda e altera carro/lead.
4. `admin/vendas/vendedor_converter.php`
   - Converte por GET e já possui chamador real.
5. `admin/leads/leads_status.php`
   - GET direto para status de lead com links reais na UI.
6. `admin/carros/carro_fotos_delete.php` e `admin/apagar_foto.php`
   - Remoção de foto e arquivo físico.
7. `admin/cron_liberar_saldo.php`
   - Isolamento de execução financeira em lote.

## Riscos antes do deploy beta

- Ainda é possível alterar status de leads por GET sem token.
- Ainda é possível criar/alterar vendas por POST sem CSRF.
- Ainda existe conversão de vendedor para venda por GET.
- Ainda existem rotas de fotos que removem registros/arquivos sem CSRF.
- O cron financeiro continua exposto como rota web autenticada e executa mutações em lote.

## Conclusão

O sistema avançou: pagamento, aprovação e rejeição foram removidos da lista P0. Ainda assim, o GO beta privado deve aguardar a correção dos P0 restantes acima, principalmente `atualizar_venda.php`, `marcar_venda.php`, `confirmar_venda.php`, `vendedor_converter.php`, rotas de status de leads, rotas de fotos e `cron_liberar_saldo.php`.
