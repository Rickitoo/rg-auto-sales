# Checkpoint tecnico - RG_AUTO_SALES

Data: 2026-05-23

## Resumo

Este checkpoint consolida a fase de limpeza arquitetural e estabilizacao inicial do RG_AUTO_SALES. O objetivo foi reduzir erros de includes, rotas quebradas, URLs hardcoded e divergencias simples entre codigo e schema, sem alterar regras de negocio, SQL financeiro ou calculo de comissoes.

## Concluido

- Padronizacao de bootstrap em `app/core/bootstrap.php`.
- Centralizacao de conexao, autenticacao, sessao e helpers no core.
- Padronizacao parcial/segura de URLs com `url()`, `public_url()`, `asset()` e `redirect_to()`.
- Classificacao de wrappers legados e funcoes antigas.
- Migracao de funcoes reais para:
  - `app/core/helpers/forms.php`
  - `app/modules/cars/helpers.php`
  - `app/modules/sales/commissions.php`
  - `app/modules/finance/helpers.php`
- Definicao de rotas canonicas por modulo.
- Redirects controlados para duplicatas claras.
- Checklist manual pos-refactor criado.
- Lint PHP validado com `173 arquivos OK` e `0 erros`.

## Relatorios gerados

- `storage/reports/legacy-wrappers-20260523.md`
- `storage/reports/canonical-routes-20260523.md`
- `storage/reports/manual-test-checklist-20260523.md`
- Relatorios de lint PHP em `storage/reports/php-lint-*.txt`

## Correcoes de schema ja aplicadas

### Test-drive / cliente

Arquivo: `public/Formulario_cliente.php`

- Confirmado que o formulario publico envia `sexo`.
- Confirmado que a confirmacao publica le da tabela `clientes`.
- Corrigido o fluxo para gravar o agendamento principal em `clientes`.
- Mantido espelho em `leads` usando apenas colunas existentes no schema oficial.

### Listagem de carros

Arquivo: `admin/carros/listar_carros.php`

- Confirmado que `carros` usa `imagem` para capa.
- Confirmado que `carros_fotos` usa `caminho`, nao `foto`.
- Corrigida a consulta da galeria e a montagem da URL da imagem com helper existente.

### Pedidos de vendedores

Arquivo: `admin/vendas/vendedores_pedidos.php`

- Confirmado que a tela consulta `vendedores`, nao `vendas`.
- Confirmado que a coluna de data em `vendedores` e `data_registo`.
- Corrigido `v.criado_em` para `v.data_registo`.

## Pendencias antes de remover wrappers

- Executar o checklist manual no navegador.
- Confirmar que nenhum formulario POST aponta para rota legada em `app/modules/*`.
- Confirmar que endpoints AJAX de leads/fotos funcionam sem 404/500.
- Confirmar assets CSS/JS/imagens no DevTools sem 404.
- Validar links publicos antigos como `Test_drive.html` e fluxo `public/vender_carro.php`.
- Consolidar gerenciadores de fotos entre rotas antigas e canonicas.

## Proxima fase recomendada

Testar o fluxo completo:

1. Criar lead/test-drive pelo site publico.
2. Validar entrada no CRM/admin.
3. Abrir detalhe do lead e registrar follow-up.
4. Converter/fechar venda.
5. Validar venda em `admin/vendas`.
6. Conferir dashboard financeiro.
7. Conferir lucro real e comissoes.

Nao iniciar novo refactor ate este fluxo passar manualmente.
