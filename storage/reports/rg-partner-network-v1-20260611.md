# RG Partner Network v1 - 2026-06-11

## Arquivos criados

- `admin/parceiros/index.php`
- `admin/parceiros/adicionar.php`
- `admin/parceiros/editar.php`
- `admin/parceiros/detalhe.php`
- `admin/parceiros/salvar.php`
- `admin/parceiros/apagar.php`
- `storage/migrations/20260611_create_parceiros.sql`
- `storage/reports/rg-partner-network-v1-20260611.md`

## Arquivos alterados

- `app/views/layouts/admin_sidebar.php`
  - Adicionado link `Parceiros` apontando para `admin/parceiros/index.php`.

## Migration criada

- `storage/migrations/20260611_create_parceiros.sql`
  - Cria a tabela `parceiros`.
  - Inclui campos de identificacao, contactos, tipo, origem, estado, nivel, comissao, notas e timestamps.
  - Inclui indices `idx_parceiros_tipo`, `idx_parceiros_estado`, `idx_parceiros_nivel` e `idx_parceiros_cidade`.

## Testes feitos

- Lint individual com `C:\xampp\php\php.exe -l`:
  - `admin/parceiros/index.php`
  - `admin/parceiros/adicionar.php`
  - `admin/parceiros/editar.php`
  - `admin/parceiros/detalhe.php`
  - `admin/parceiros/salvar.php`
  - `admin/parceiros/apagar.php`
  - `app/views/layouts/admin_sidebar.php`
- Lint completo do projeto:
  - Script: `scripts/lint-php.ps1`
  - PHP CLI: `C:\xampp\php\php.exe`
  - Resultado: 211 arquivos OK, 0 erros.
  - Relatorio gerado: `storage/reports/php-lint-20260611-025333.txt`
- Migration aplicada localmente no banco `rg_auto_sales`:
  - Resultado: `migration ok`.
- Criacao de parceiro via `admin/parceiros/salvar.php` com sessao admin simulada e CSRF valido:
  - Resultado: parceiro temporario criado em estado `ativo`.
- Edicao de parceiro via `admin/parceiros/salvar.php` com sessao admin simulada e CSRF valido:
  - Resultado: nome, cidade, nivel e comissao atualizados.
- Soft delete via `admin/parceiros/apagar.php` com sessao admin simulada e CSRF valido:
  - Resultado: estado alterado para `inativo`.
- GET direto em `admin/parceiros/salvar.php`:
  - Resultado: bloqueado com `Metodo invalido`; contagem de registros nao mudou.
- POST sem CSRF em `admin/parceiros/salvar.php`:
  - Resultado: bloqueado com `CSRF invalido`; contagem de registros nao mudou.
- Validacao no browser interno em `http://127.0.0.1:8797/admin/parceiros/index.php`:
  - Titulo carregado: `Parceiros | RG Auto Sales`.
  - Confirmado hero `RG Partner Network v1`.
  - Confirmado botao `Novo Parceiro`.
  - Confirmado link `Parceiros` no menu.
  - Confirmada tabela/listagem.
- Limpeza:
  - Registro temporario removido.
  - Helper e script temporarios de teste removidos.
  - Servidor PHP local de teste encerrado.

## Resultado final

Modulo `RG Partner Network v1` implementado com listagem, filtros, busca, cards, criacao, edicao, detalhe, WhatsApp e inativacao por soft delete. Todas as paginas exigem `require_admin()`. Acoes mutativas aceitam apenas `POST`, validam CSRF via `require_post_csrf()` e usam prepared statements.
