# Auditoria CSRF de Rotas Destrutivas Admin - Deploy Beta

Data: 2026-05-24  
Projeto: RG Auto Sales  
Escopo: rotas admin/legadas que alteram dados por GET ou POST sem proteção CSRF adequada.  
Resultado: auditoria apenas informativa. Nenhum código foi alterado.

## Resumo executivo

Foram encontradas rotas críticas que alteram vendas, pagamentos, aprovações, status, leads, fotos e carros sem CSRF ou usando GET para ações destrutivas. Para GO beta privado, as rotas financeiras e comerciais abaixo devem ser corrigidas antes do deploy.

Criticidade usada:

- **P0 / Bloqueia deploy**: altera dados sensíveis sem CSRF, especialmente financeiro, vendas, aprovação, pagamento, delete ou mutação via GET.
- **P1 / Alta**: altera dados sem CSRF via POST ou rota legada que ainda pode ser chamada internamente.
- **P2 / Média**: tem CSRF, mas usa GET para mutação ou comparação de token fraca.
- **P3 / Baixa**: tem CSRF adequado ou risco residual baixo.

## Rotas que bloqueiam deploy

| Arquivo | Ação perigosa | Método usado | CSRF | Prioridade |
|---|---|---:|---:|---|
| `admin/vendas/pagar_venda.php` | Marca venda como paga e recalcula/atualiza colunas financeiras | GET `id` | Não | P0 |
| `admin/vendas/aprovar_venda.php` | Aprova venda e altera status de aprovação | GET `id` | Não | P0 |
| `admin/vendas/rejeitar_venda.php` | Rejeita venda e altera status | GET `id` | Não | P0 |
| `admin/vendas/atualizar_venda.php` | Atualiza valores financeiros, comissões, status e aprovação | POST | Não | P0 |
| `admin/vendas/marcar_venda.php` | Cria venda, marca carro como vendido e fecha lead | POST | Não | P0 |
| `admin/vendas/confirmar_venda.php` | Confirma venda a partir de lead e marca carro como vendido | POST | Não | P0 |
| `admin/vendas/vendedor_converter.php` | Converte pedido de vendedor em cliente/venda e altera status | GET `id` | Não | P0 |
| `admin/leads/leads_status.php` | Altera status do lead | GET `id`, `s` | Não | P0 |
| `admin/carros/carro_fotos_delete.php` | Remove foto do carro, atualiza capa e apaga arquivo físico | JSON/POST | Não | P0 |
| `admin/apagar_foto.php` | Remove foto e apaga arquivo físico | GET `id`, `carro_id` | Não | P0 |
| `admin/mudar_estado.php` | Altera estado/status de cliente | GET `id`, `estado` | Não | P0 |
| `admin/cron_liberar_saldo.php` | Move saldo pendente para disponível e marca vendas como processadas | Qualquer request autenticado | Não | P0 |

## Rotas de alta prioridade

| Arquivo | Ação perigosa | Método usado | CSRF | Prioridade |
|---|---|---:|---:|---|
| `admin/leads/leads_status_ajax.php` | Atualiza status, último contato e próximo follow-up do lead | POST | Não | P1 |
| `admin/leads/lead_move.php` | Move lead entre status e pode redirecionar para fechamento | POST | Não | P1 |
| `admin/salvar_interacao.php` | Insere interação/nota em lead | POST | Não | P1 |
| `admin/mover_foto.php` | Reordena fotos do carro | GET `id`, `dir`, `carro_id` | Não | P1 |
| `app/modules/finance/marcar_pago.php` | Marca venda como paga | POST | Não | P1 |
| `app/modules/finance/marcar_vendido.php` | Cria venda real e marca carro vendido | GET `id` | Não | P1 |
| `app/modules/leads/leads_status.php` | Altera status do lead em módulo legado | GET `id`, `s` | Não | P1 |
| `app/modules/leads/leads_status_ajax.php` | Atualiza status/follow-up do lead em módulo legado | POST | Não | P1 |
| `app/modules/leads/lead_move.php` | Move lead em módulo legado | POST | Não | P1 |

Observação: o diretório `app/` está bloqueado por `.htaccess` no ambiente local sincronizado, mas estas rotas continuam sendo risco se forem usadas por include/roteamento interno, deploy com rewrite diferente ou cópia sem as regras de bloqueio.

## Rotas com proteção parcial ou risco médio

| Arquivo | Ação perigosa | Método usado | CSRF | Prioridade |
|---|---|---:|---:|---|
| `admin/carros/apagar_carro.php` | Remove carro e fotos vinculadas | GET `id`, `csrf_token` | Sim | P2 |
| `admin/vendas/vendedor_apagar.php` | Apaga pedido de vendedor e fotos relacionadas | POST | Sim, mas comparação direta | P2 |
| `admin/vendas/vendedor_status.php` | Atualiza status de pedido de vendedor | POST | Sim, mas comparação direta | P2 |
| `admin/status.php` | Atualiza status de cliente | POST | Sim, mas comparação direta | P2 |
| `app/modules/leads/actions/update_status.php` | Atualiza status de vendedor e pode criar carro | GET `id`, `status`, `token` | Sim | P2 |

## Rotas com proteção aceitável

| Arquivo | Ação perigosa | Método usado | CSRF | Prioridade |
|---|---|---:|---:|---|
| `admin/salvar_ordem_fotos.php` | Atualiza ordem das fotos e imagem principal | POST | Sim, `hash_equals` | P3 |
| `app/modules/cars/actions/delete.php` | Soft delete de cliente em módulo legado | POST | Sim, `hash_equals` | P3 |
| `app/modules/sales/marcar_vendido.php` | Marca carro vendido em módulo legado | POST | Sim, `hash_equals` | P3 |

## Prioridade recomendada de correção

1. Corrigir primeiro as rotas financeiras/comerciais P0:
   `pagar_venda.php`, `aprovar_venda.php`, `rejeitar_venda.php`, `atualizar_venda.php`, `marcar_venda.php`, `confirmar_venda.php`.
2. Corrigir conversões/status de vendas e leads:
   `vendedor_converter.php`, `leads_status.php`, `leads_status_ajax.php`, `lead_move.php`.
3. Corrigir destruição/manipulação de fotos e carros:
   `carro_fotos_delete.php`, `apagar_foto.php`, `mover_foto.php`, depois avaliar migração de `apagar_carro.php` de GET para POST.
4. Isolar ou proteger `admin/cron_liberar_saldo.php`, idealmente tornando a execução CLI-only ou protegida por token operacional fora da sessão web.
5. Trocar comparações diretas de CSRF por `hash_equals()` nas rotas que já possuem token.
6. Revisar rotas legadas em `app/modules/` antes de qualquer mudança de `.htaccess`, roteamento ou deploy fora do XAMPP atual.

## Riscos antes do deploy beta

- Pagamento, aprovação e rejeição de vendas podem ser acionados por link GET sem token.
- Algumas ações críticas ainda dependem apenas de sessão admin, o que permite CSRF se um admin autenticado visitar uma página maliciosa.
- Rotas AJAX/POST de leads aceitam mutações sem token, expondo status e follow-ups a alteração externa.
- Manipulação de fotos por GET/POST sem CSRF pode apagar arquivos ou alterar capas/ordem sem confirmação legítima.
- `admin/cron_liberar_saldo.php` não deve ficar exposto como rota web comum, pois executa alterações financeiras em lote.

## Próximo passo sugerido

Começar por uma correção pequena e isolada em `admin/vendas/pagar_venda.php`: exigir POST, validar `csrf_token` com `hash_equals()` e atualizar os botões/forms que chamam esta ação. Depois repetir o padrão para aprovação/rejeição de venda.
