# Estabilizacao final - RG_AUTO_SALES

Data: 2026-05-23

## Objetivo

Auditar e corrigir os bugs restantes de rotas, botoes, permissoes e integracao entre admin, CRM, vendas, financeiro, clientes, carros e leads, sem fazer refactor pesado nem alterar regras de negocio, vendas, financeiro ou comissoes.

## Correcoes aplicadas

### Navegacao e rotas

- `includes/layout_top.php`
  - Adicionados links para `CRM Dashboard`, `CRM Inbox` e `Painel Inteligente`.
  - Corrigido estado ativo para diferenciar `admin/dashboard.php` de `admin/crm/dashboard.php`.

- `admin/dashboard.php`
  - Corrigido link quebrado `marcar_pago.php` em vendas ja pagas.
  - Venda paga agora abre `admin/vendas/venda_detalhe.php`.

- `public/dashboard.php`
  - Corrigido alias/filtro de follow-up para usar `proximo_contacto`.
  - Corrigido link quebrado `marcar_pago.php` em vendas ja pagas.

- `admin/vendas/vendas.php`
  - Corrigidos links para CSV, custos e detalhe de venda usando rotas reais.

- `admin/vendas/venda_detalhe.php`
  - Corrigidos links de custos e recibo para `app/modules/finance`.

- `admin/vendas/editar_venda.php`
  - Corrigidos links de voltar ao detalhe, custos e editar venda.

- `app/modules/finance/custos.php`
  - Corrigidos links de retorno para dashboard, vendas, editar venda e detalhe.

- `app/modules/finance/recibo.php`
  - Corrigido link de voltar para detalhe da venda.

### Leads e CRM

- `admin/leads/leads.php`
  - Corrigido uso fragil de `proximo_followup`.
  - Adicionado fallback para `proximo_contacto`, coluna real da tabela `leads`.
  - Botao de detalhe agora usa `url('admin/leads/ver_lead.php')`.
  - Botao de follow-up agora abre o CRM Inbox no lead correto.

- `admin/leads/ver_lead.php`
  - Botao quebrado `editar_lead.php` substituido por acesso ao CRM Inbox/follow-up do lead.

- `admin/crm/dashboard.php`
  - Dashboard CRM criado e validado.
  - KPIs, funil, leads urgentes, follow-ups pendentes, atividade recente e links rapidos disponiveis.
  - Corrigido calculo de maximo do funil para compatibilidade com PHP/XAMPP.

### Clientes

- `admin/clientes/cliente_detalhe.php`
  - Nova pagina de detalhe do cliente/test-drive.
  - Mostra dados do cliente, WhatsApp e historico CRM relacionado por telefone/email.

- `admin/clientes/clientes.php`
  - Botao `Ver` agora aponta para a nova pagina canonica de detalhe.

### Painel inteligente

- `admin/painel_inteligente.php`
  - Refeito para usar dados reais de `leads` e `vendas`.
  - Mostra:
    - leads urgentes;
    - leads parados;
    - follow-ups pendentes;
    - vendas pendentes;
    - pagamentos pendentes;
    - sugestoes claras de proxima acao.
  - Acoes diretas para CRM Inbox, WhatsApp, fechar venda, ver venda e pagar venda.

### Permissoes e seguranca de runtime

- Mantida protecao com `require_admin()` nas paginas admin novas/alteradas.
- Endpoints e paginas principais continuam passando pelo bootstrap oficial.
- Declaracoes locais de `function h()` foram protegidas com `function_exists('h')` para evitar fatal error de redeclaracao quando `bootstrap.php` ja carrega o helper global.

## Fluxos cobertos pela estabilizacao

- Dashboard admin -> CRM Dashboard -> CRM Inbox.
- Leads -> detalhe -> follow-up -> WhatsApp -> fechar venda.
- CRM Dashboard -> leads urgentes/follow-ups pendentes -> Inbox.
- Clientes -> detalhe -> historico CRM relacionado.
- Vendas -> detalhe -> custos -> recibo.
- Venda pendente -> pagamento -> financeiro.
- Painel Inteligente -> proxima acao comercial/financeira.

## Validacao tecnica

- Lint PHP completo executado.
- Resultado: `176 arquivos OK`, `0 erros`.
- Relatorio de lint: `storage/reports/php-lint-20260523-233801.txt`.

## Pronto para uso interno

- CRM Inbox funcional com timeline, follow-up inteligente e mensagem WhatsApp.
- Dashboard CRM criado.
- Painel Inteligente reforcado.
- Links principais de admin, CRM, leads, clientes, vendas e financeiro corrigidos.
- Erros conhecidos de rota direta em fluxos principais foram reduzidos.

## Ainda falta antes de deploy

- Teste manual no navegador com sessao admin real.
- Confirmar se a pasta oficial sera `C:\xampp\htdocs\RG_AUTO_SALES` ou o workspace em OneDrive.
- Recomendada criacao de symlink ou padronizacao de uma unica pasta para evitar divergencia entre codigo editado e codigo servido.
- Validar envio real de WhatsApp em mobile/desktop.
- Validar permissao de escrita para uploads/fotos em ambiente final.
- Definir rotina de backup do banco.
- Definir ambiente de producao com HTTPS, credenciais fora do repositorio e configuracao de erro/log adequada.
