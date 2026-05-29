# Checklist Técnico - Deploy Beta Privado RG Auto Sales

Data: 2026-05-24

## Contexto Atual

- Projeto ainda em ambiente local XAMPP.
- 18 páginas admin já migradas para o Layout Global Admin.
- Módulos principais no layout moderno:
  - Dashboard
  - Clientes
  - Carros principais
  - Financeiro
  - Vendas principais
  - CRM principal
  - Leads
- Riscos restantes concentrados em rotas auxiliares/legadas:
  - Galeria de fotos
  - Funil legado
  - Páginas auxiliares de vendas
  - Dashboards/relatórios antigos
  - Rotas espelhadas em `app/modules/*`

## Decisão Recomendada

Deploy beta privado pode avançar apenas se:

- acesso for restrito a utilizadores internos;
- houver backup completo antes do deploy;
- banco de dados estiver validado;
- uploads/imagens forem testados;
- credenciais e debug local forem removidos/desativados;
- rotas críticas de venda, financeiro e login forem testadas de ponta a ponta.

## Bloqueadores Antes do Deploy

- [ ] Remover ou desativar qualquer página de teste pública, especialmente `teste_login.php`, se não for necessária.
- [ ] Garantir que `error_reporting(E_ALL)` e mensagens `die("Erro...")` não exponham detalhes técnicos em ambiente beta.
- [ ] Confirmar que credenciais de banco não estão hardcoded em arquivos expostos.
- [ ] Criar `.env` ou configuração equivalente fora do webroot para credenciais.
- [ ] Garantir que `storage/`, `app/`, `vendor/`, `scripts/`, `includes/` e backups não sejam servidos publicamente pelo Apache.
- [ ] Confirmar que login/admin exige sessão em todas as rotas sensíveis.
- [ ] Fazer backup do banco e dos uploads antes de qualquer publicação.
- [ ] Validar permissões de escrita em `public/uploads` e `storage/*`.
- [ ] Testar upload de imagens em ambiente igual ao beta.
- [ ] Confirmar que `auth/login.php?next=...` preserva redirects e não permite open redirect externo.
- [ ] Verificar CSRF nos fluxos destrutivos: apagar, editar, confirmar venda, mudar status, uploads.
- [ ] Migrar ou bloquear acesso direto a rotas antigas que executam ações sensíveis sem layout/CSRF suficiente.

## Segurança

- [ ] Usar HTTPS no beta, mesmo privado.
- [ ] Desativar listagem de diretórios no Apache.
- [ ] Bloquear acesso web a:
  - `.git/`
  - `storage/`
  - `scripts/`
  - `vendor/`
  - `app/`
  - arquivos `.env`
  - dumps `.sql`
  - relatórios internos sensíveis
- [ ] Confirmar que uploads não executam PHP.
- [ ] Validar extensão e MIME de imagens no upload.
- [ ] Renomear uploads para nomes seguros, sem preservar nome original bruto.
- [ ] Definir tamanho máximo de upload.
- [ ] Usar `password_hash()` e `password_verify()` para senhas.
- [ ] Forçar troca de senha para contas de teste/admin padrão.
- [ ] Remover contas demo sem necessidade.
- [ ] Rever permissões de perfis (`admin`, vendedores, usuário público).
- [ ] Confirmar logout real e invalidação de sessão.
- [ ] Definir cookies de sessão com flags seguras quando em HTTPS:
  - `httponly`
  - `secure`
  - `samesite`
- [ ] Não exibir erros SQL ao usuário final.
- [ ] Registrar erros em log privado.

## Autenticação e Sessão

- [ ] Testar login admin.
- [ ] Testar login usuário/vendedor se existir.
- [ ] Testar logout.
- [ ] Acessar rota admin sem login e confirmar `302` para login.
- [ ] Confirmar preservação de `next` em rotas importantes.
- [ ] Confirmar que `require_admin()` protege:
  - Dashboard
  - Clientes
  - Carros
  - Vendas
  - Financeiro
  - CRM
  - Leads
  - Rotas de ação/API admin
- [ ] Testar acesso com usuário não admin em páginas admin.
- [ ] Validar timeout de sessão ou política mínima para beta.

## `.env` e Configuração

- [ ] Criar `.env` ou arquivo de configuração local não versionado.
- [ ] Separar configuração local e beta:
  - host do banco
  - nome do banco
  - usuário
  - senha
  - base URL
  - modo debug
  - caminho de uploads
- [ ] Garantir que `.env` está no `.gitignore`.
- [ ] Definir `APP_ENV=beta` ou equivalente.
- [ ] Definir `APP_DEBUG=false`.
- [ ] Validar `BASE_URL`/helpers `url()` e `asset()` no domínio beta.
- [ ] Confirmar timezone usada pelo sistema.
- [ ] Rever limites PHP:
  - `upload_max_filesize`
  - `post_max_size`
  - `memory_limit`
  - `max_execution_time`

## Banco de Dados

- [ ] Exportar dump completo antes do deploy.
- [ ] Guardar dump em `storage/backups` e também fora do servidor.
- [ ] Validar import em ambiente limpo.
- [ ] Confirmar charset/collation, preferencialmente `utf8mb4`.
- [ ] Validar tabelas principais:
  - `users`
  - `carros`
  - `carro_fotos`
  - `clientes`
  - `leads`
  - `mensagens`
  - `followups`/equivalente se usado
  - `vendas`
  - `financeiro`/comissões/config
- [ ] Confirmar colunas alternativas já tratadas pelo código:
  - `proximo_followup`
  - `proximo_contacto`
  - `criado_em`
  - `created_at`
- [ ] Confirmar índices mínimos:
  - IDs primários
  - `leads.status`
  - `leads.telefone`
  - `vendas.cliente_id`
  - `vendas.carro_id`
  - `carro_fotos.carro_id`
- [ ] Confirmar usuário do banco com permissões mínimas necessárias.
- [ ] Evitar usuário root do MySQL no beta.
- [ ] Testar transações ou rollback em fluxos críticos quando aplicável.

## Uploads e Imagens

- [ ] Confirmar pasta `public/uploads`.
- [ ] Confirmar subpastas usadas por carros/galeria.
- [ ] Testar upload de capa de carro.
- [ ] Testar upload de múltiplas fotos.
- [ ] Testar visualização de imagens no catálogo público.
- [ ] Testar visualização no admin.
- [ ] Testar remoção de foto.
- [ ] Testar alteração/reordenação de fotos, se rota estiver ativa.
- [ ] Validar permissões de escrita pelo Apache.
- [ ] Impedir execução de `.php` em uploads.
- [ ] Definir imagem placeholder quando não houver foto.
- [ ] Fazer backup separado de `public/uploads`.

## Backups

- [ ] Criar backup completo do banco antes do deploy.
- [ ] Criar backup completo de `public/uploads`.
- [ ] Criar backup da pasta do projeto antes de publicar.
- [ ] Guardar uma cópia fora da máquina/servidor.
- [ ] Documentar comando ou processo de restore.
- [ ] Testar restore pelo menos uma vez em ambiente separado.
- [ ] Definir rotina de backup para beta:
  - diário para banco
  - diário ou semanal para uploads, conforme volume
- [ ] Confirmar espaço em disco.

## Permissões de Pastas

- [ ] `public/uploads`: escrita pelo Apache, sem execução de scripts.
- [ ] `storage/logs`: escrita pelo Apache se logs forem gravados.
- [ ] `storage/backups`: sem acesso público.
- [ ] `storage/temp`: escrita se usada.
- [ ] `app/`: leitura pela aplicação, sem acesso público direto.
- [ ] `vendor/`: leitura pela aplicação, sem acesso público direto.
- [ ] `scripts/`: sem acesso público.
- [ ] `.env`: leitura pela aplicação, sem acesso público.

## Testes de Fluxo Completo

### Autenticação

- [ ] Login admin.
- [ ] Logout.
- [ ] Bloqueio de páginas admin sem sessão.
- [ ] Redirect `next` após login.

### Dashboard

- [ ] Abrir dashboard admin.
- [ ] Validar KPIs.
- [ ] Validar links rápidos.

### Carros

- [ ] Listar carros.
- [ ] Filtrar carros.
- [ ] Adicionar carro.
- [ ] Editar carro.
- [ ] Alterar status disponível/vendido.
- [ ] Upload de imagens.
- [ ] Visualizar carro no público.
- [ ] Testar galeria `admin/carros/carro_fotos.php`, mesmo ainda standalone.

### Clientes

- [ ] Listar clientes.
- [ ] Abrir detalhe do cliente.
- [ ] Ver histórico de vendas/leads.
- [ ] Testar links WhatsApp.

### Leads e CRM

- [ ] Adicionar lead.
- [ ] Listar leads.
- [ ] Abrir detalhe do lead.
- [ ] Abrir CRM inbox.
- [ ] Alterar status.
- [ ] Registrar follow-up/mensagem.
- [ ] Testar WhatsApp.
- [ ] Testar fechamento de venda a partir do lead.

### Vendas

- [ ] Listar vendas.
- [ ] Criar nova venda.
- [ ] Editar venda.
- [ ] Abrir detalhe da venda.
- [ ] Confirmar venda se rota estiver ativa.
- [ ] Marcar venda se rota estiver ativa.
- [ ] Validar status da venda.
- [ ] Validar status de pagamento.

### Financeiro

- [ ] Abrir financeiro.
- [ ] Validar totais.
- [ ] Validar lucro real.
- [ ] Validar comissões.
- [ ] Validar regras de aprovação.
- [ ] Comparar alguns cálculos manualmente.

### Público

- [ ] Página inicial.
- [ ] Catálogo de carros.
- [ ] Detalhe do carro.
- [ ] Formulário/test drive.
- [ ] Criação de lead a partir do público.
- [ ] WhatsApp/links externos.

## Limpeza de Arquivos Antigos

- [ ] Remover ou proteger `teste_login.php`.
- [ ] Revisar `auth_check.php` e `init.php` legados.
- [ ] Revisar rotas espelhadas em `app/modules/cars/*`.
- [ ] Revisar rotas espelhadas em `app/modules/leads/*`.
- [ ] Decidir se `public/dashboard.php` continua necessário.
- [ ] Decidir destino de dashboards antigos:
  - `admin/dashboard_carros.php`
  - `admin/dashboard_vendas.php`
  - `admin/dashboard_pro.php`
  - `admin/painel_inteligente.php`
- [ ] Remover includes antigos somente depois de confirmar que não são usados.
- [ ] Não apagar arquivos antes de ter backup.

## Riscos Que Bloqueiam Deploy

- Credenciais expostas ou hardcoded em arquivo público.
- `.env`, `storage`, dumps SQL ou backups acessíveis pelo navegador.
- Upload permitindo execução de PHP/script.
- Login/admin sem proteção em rota sensível.
- Fluxos de venda/financeiro com erro de cálculo ou status.
- Banco sem backup testado.
- Erros técnicos expostos ao usuário em produção/beta.
- Permissões impedindo upload ou escrita de logs.
- Rotas destrutivas sem CSRF em uso real.

## Riscos Aceitáveis Para Beta Privado

Estes podem ficar para depois do beta privado se o acesso for restrito e os usuários forem internos:

- Algumas rotas auxiliares ainda com layout antigo.
- Dashboards/relatórios legados ainda não migrados.
- CSS duplicado em páginas não críticas.
- `app/modules/*` legados, desde que não sejam divulgados e sejam protegidos.
- Pequenos problemas visuais em páginas pouco usadas.
- Migração completa de `public/dashboard.php`.
- Conversão do inline dinâmico do funil CRM para CSS custom property.

## Riscos Que Devem Ser Monitorados Durante o Beta

- Erros em `storage/logs`.
- Falhas de upload.
- Lentidão em listagens com muitos carros/leads/vendas.
- Inconsistência entre status de carro vendido e venda registrada.
- Diferenças entre lucro/comissão esperados e exibidos.
- Leads duplicados por telefone.
- Mensagens/WhatsApp com telefone mal formatado.
- Imagens quebradas no catálogo.

## Checklist Final de Go/No-Go

- [ ] Backup de banco realizado.
- [ ] Backup de uploads realizado.
- [ ] Restore testado ou pelo menos validado.
- [ ] `.env`/config beta definido.
- [ ] Debug desativado.
- [ ] Páginas admin protegidas.
- [ ] Upload testado e protegido.
- [ ] Fluxo lead -> CRM -> venda testado.
- [ ] Fluxo carro -> imagens -> catálogo testado.
- [ ] Fluxo venda -> financeiro testado.
- [ ] Usuários beta definidos.
- [ ] Plano de rollback definido.
- [ ] Riscos aceitos documentados.

## Conclusão

O projeto está apto a preparar um beta privado controlado, desde que os bloqueadores de segurança/configuração sejam resolvidos antes da publicação. O Layout Global Admin já cobre o núcleo operacional, incluindo Leads. O maior cuidado agora deve ser com hardening, backups, uploads, proteção de rotas legadas e testes completos dos fluxos comerciais e financeiros.
