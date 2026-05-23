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

## Checkpoint pos-correcao do CRM Inbox

### Diagnostico

- O redirecionamento indevido do CRM Inbox nao estava no HTML/layout da pagina.
- O teste com `die('CHEGOU NA INBOX')` indicou que a requisicao HTTP nao estava executando o arquivo editado no workspace.
- A causa real foi divergencia entre pastas: o navegador/XAMPP estava servindo `C:\xampp\htdocs\RG_AUTO_SALES`, enquanto o workspace ativo estava em `C:\Users\rickg\OneDrive\Ambiente de Trabalho\RG_AUTO_SALES`.
- A copia servida pelo Apache ainda tinha `require_login()` antigo e login sempre redirecionando para dashboard.

### Correcoes aplicadas

- `.htaccess` atualizado para deixar arquivos e pastas fisicos passarem direto antes das rotas amigaveis.
- Rota `/crm` e `/admin/crm` apontando para `admin/crm/inbox.php`.
- `require_login()` passou a preservar a rota pedida com `next`.
- `auth/login.php` mantem o `next` no formulario e, se o usuario ja estiver autenticado, redireciona para a rota solicitada quando segura.
- `auth/processa_login.php` devolve o destino original no JSON de login quando o `next` e valido.
- Arquivos minimos corrigidos foram espelhados para `C:\xampp\htdocs\RG_AUTO_SALES`, que e a copia atualmente servida pelo Apache.

### Validacao

- Acesso sem sessao a `/RG_AUTO_SALES/admin/crm/inbox.php` redireciona para `auth/login.php?next=%2FRG_AUTO_SALES%2Fadmin%2Fcrm%2Finbox.php`.
- Acesso sem sessao a `/RG_AUTO_SALES/crm` redireciona para `auth/login.php?next=%2FRG_AUTO_SALES%2Fcrm`.
- Depois do login, o fluxo preserva a rota pedida em vez de cair sempre no dashboard.
- Lint PHP validado com `174 arquivos OK` e `0 erros`.
- Relatorio final de lint: `storage/reports/php-lint-20260523-214547.txt`.

### Recomendacao operacional

Manter uma unica pasta oficial de trabalho para o projeto ou configurar um symlink entre `C:\xampp\htdocs\RG_AUTO_SALES` e o workspace real. Isso evita que o codigo editado e o codigo executado pelo Apache fiquem divergentes, reduzindo falsos diagnosticos, cache aparente e regressao de rotas.

## Checkpoint CRM Inbox inteligente

### Entregue

- CRM Inbox funcional em `admin/crm/inbox.php`.
- Timeline/follow-up por lead criada com tabela `lead_followups`.
- Migration registrada em `storage/database/migrations/20260523_create_lead_followups.sql`.
- Formulario rapido para registrar notas, status e responsavel pelo acompanhamento.
- Follow-up inteligente calculado em tempo real na pagina, sem cron/job.
- Calculo de ultimo follow-up e dias sem contacto.
- Badges operacionais: `Novo`, `Urgente`, `Sem resposta`, `Parado` e `Em negociacao`.
- Destaque visual para leads esquecidos:
  - 3 dias sem follow-up/contacto: atencao.
  - 7 dias sem follow-up/contacto: urgente.
- Compatibilidade mantida com a timeline atual e com o fluxo existente de vendas.
- Nenhuma regra de venda, comissao ou financeiro foi alterada nesta fase.

### Validacao

- Lint PHP validado com `174 arquivos OK` e `0 erros`.
- Relatorio final de lint: `storage/reports/php-lint-20260523-220816.txt`.
- Arquivo da inbox espelhado para `C:\xampp\htdocs\RG_AUTO_SALES`, que e a copia atualmente servida pelo Apache/XAMPP.

### Proxima fase recomendada

Preparar automacao de mensagens WhatsApp/follow-up:

1. Definir templates de mensagem por status/prioridade.
2. Validar regras de envio manual antes de qualquer automacao.
3. Registrar interacoes enviadas/recebidas na timeline do lead.
4. Avaliar integracao com webhook/API WhatsApp sem bloquear o fluxo atual.
5. Somente depois considerar cron/job para lembretes e reativacao automatica.

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
