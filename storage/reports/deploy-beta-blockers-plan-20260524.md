# Plano Curto - Bloqueadores Deploy Beta Privado

Data: 2026-05-24

Base:

- `storage/reports/deploy-beta-checklist-20260524.md`
- `storage/reports/admin-layout-audit-final-20260524.md`

## Estado atual

- 18 páginas admin migradas para o Layout Global Admin.
- Núcleo operacional migrado: Dashboard, Clientes, Carros principais, Financeiro, Vendas principais, CRM e Leads.
- Ambiente ainda local XAMPP.
- Riscos restantes concentrados em hardening, configuração, backups, uploads e rotas auxiliares/legadas.

## 1. Bloqueia deploy

Estes itens devem ser resolvidos antes de qualquer beta privado.

### Segurança e exposição

- [ ] Remover, bloquear ou proteger `teste_login.php`.
- [ ] Garantir que `.git/`, `storage/`, `app/`, `vendor/`, `scripts/`, `includes/`, dumps SQL e backups não são acessíveis pelo navegador.
- [ ] Confirmar que arquivos de configuração/credenciais não ficam em webroot público.
- [ ] Criar `.env` ou configuração beta equivalente fora do webroot.
- [ ] Definir debug desligado no beta.
- [ ] Remover exposição de erros técnicos ao usuário final, especialmente `error_reporting(E_ALL)` e `die("Erro...")` em rotas públicas/ativas.
- [ ] Usar HTTPS no ambiente beta.

### Autenticação e sessão

- [ ] Confirmar que todas as rotas admin sensíveis exigem `require_admin()` ou proteção equivalente.
- [ ] Testar acesso anônimo às rotas admin principais e confirmar `302` para login.
- [ ] Confirmar que `auth/login.php?next=...` não aceita redirect externo.
- [ ] Testar login/logout real com usuário admin beta.

### Banco e backup

- [ ] Fazer dump completo do banco antes da publicação.
- [ ] Fazer backup completo de `public/uploads`.
- [ ] Guardar cópia fora do servidor/máquina de deploy.
- [ ] Validar restore ou pelo menos importar o dump em ambiente limpo.
- [ ] Usar usuário MySQL próprio para beta, não `root`.

### Uploads/imagens

- [ ] Validar permissões de escrita em `public/uploads`.
- [ ] Confirmar que uploads não executam `.php` ou scripts.
- [ ] Testar upload de capa/fotos em ambiente igual ao beta.
- [ ] Testar exibição das imagens no admin e no catálogo público.

### Fluxos críticos

- [ ] Testar fluxo carro completo: adicionar, editar, imagem, catálogo público.
- [ ] Testar fluxo lead completo: adicionar, listar, CRM inbox, follow-up, WhatsApp.
- [ ] Testar fluxo venda completo: nova venda, detalhe, edição/status.
- [ ] Testar financeiro: totais, lucro real, comissões e aprovações.
- [ ] Validar CSRF nos fluxos destrutivos que serão usados no beta: apagar, editar, confirmar venda, mudar status, uploads.

## 2. Pode ir para beta com cuidado

Estes itens não precisam bloquear um beta privado se o acesso for interno, mas devem ser conhecidos e monitorados.

- [ ] Rotas auxiliares ainda standalone:
  - `admin/carros/carro_fotos.php`
  - `admin/vendas/marcar_venda.php`
  - `admin/vendas/confirmar_venda.php`
  - `admin/vendas/vendedores_pedidos.php`
  - `admin/vendas/vendedor_ver.php`
- [ ] `admin/funil.php` ainda usa layout antigo, apesar de CRM/Leads principais estarem migrados.
- [ ] Dashboards/relatórios antigos ainda não migrados:
  - `admin/dashboard_vendas.php`
  - `admin/dashboard_carros.php`
  - `admin/dashboard_pro.php`
  - `admin/painel_inteligente.php`
  - `admin/relatorio_vendedores.php`
- [ ] CSS antigo e inline ainda existem em páginas legadas.
- [ ] Alguns textos antigos podem conter problemas de encoding/mojibake.
- [ ] Rotas espelhadas em `app/modules/*` podem existir, desde que não sejam divulgadas e estejam protegidas.

Condição para aceitar estes riscos:

- beta restrito a usuários internos;
- lista de rotas legadas conhecida;
- plano de rollback pronto;
- logs monitorados;
- rotas financeiras/vendas testadas antes de uso real.

## 3. Pode ficar para depois

Estes itens podem ser tratados após o beta privado, desde que os bloqueadores acima estejam resolvidos.

- [ ] Migrar visualmente todos os dashboards legados.
- [ ] Remover definitivamente includes/layout antigo após confirmar que nenhuma rota ativa depende deles.
- [ ] Consolidar CSS duplicado e remover blocos `<style>` restantes.
- [ ] Refatorar rotas espelhadas em `app/modules/*`.
- [ ] Migrar ou reavaliar `public/dashboard.php`.
- [ ] Converter inline dinâmico do funil CRM para CSS custom property.
- [ ] Padronizar textos antigos com encoding correto.
- [ ] Melhorar UX das páginas auxiliares de administração/configuração.
- [ ] Criar automação de backup recorrente.
- [ ] Criar testes automatizados de smoke/regressão.

## Plano de ação executável

### Passo 1 - Hardening mínimo

1. Bloquear acesso público a pastas internas e backups.
2. Remover/proteger `teste_login.php`.
3. Desativar debug e erros técnicos em tela.
4. Configurar `.env`/config beta fora do webroot.
5. Ativar HTTPS.

### Passo 2 - Dados e arquivos

1. Exportar banco.
2. Copiar `public/uploads`.
3. Testar restore/import.
4. Validar permissões de `public/uploads` e `storage/*`.

### Passo 3 - Validação funcional

1. Login/logout admin.
2. Carros + imagens + catálogo.
3. Leads + CRM + WhatsApp.
4. Vendas + detalhe + edição/status.
5. Financeiro + comissões/lucro.

### Passo 4 - Decisão Go/No-Go

Go para beta privado apenas se:

- nenhum arquivo sensível estiver público;
- backup/restore estiver validado;
- upload estiver protegido e funcional;
- login/admin estiver protegido;
- fluxos de venda e financeiro passarem no teste manual;
- riscos aceitos estiverem documentados.

No-Go se:

- credenciais ou backups estiverem acessíveis via navegador;
- upload permitir execução de script;
- login/admin falhar em proteger rotas;
- venda/financeiro apresentar cálculo ou status incorreto;
- não existir backup confiável.

## Conclusão

O Layout Global Admin já cobre o núcleo necessário para beta privado. O deploy não deve ser bloqueado por páginas legadas apenas visuais, mas deve ser bloqueado por qualquer falha de segurança, backup, upload, autenticação ou fluxo financeiro/comercial crítico.
