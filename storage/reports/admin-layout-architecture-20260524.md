# Arquitetura Admin Layout - RG Auto Sales

Data: 2026-05-24

## Objetivo

Criar uma camada global reutilizavel para o admin, separando logica de pagina, conteudo e estrutura visual.

## Padrao recomendado

Cada pagina admin deve funcionar como um controlador fino:

```php
<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

// 1. Buscar e preparar dados

$pageTitle = 'Titulo';
$pageSubtitle = 'Descricao curta da pagina';
$contentFile = BASE_PATH . '/app/views/admin/modulo/pagina_content.php';

require BASE_PATH . '/app/views/layouts/admin_layout.php';
```

O ficheiro `*_content.php` deve conter apenas HTML/PHP de apresentacao, usando variaveis preparadas pelo controlador.

## Estrutura criada

- `app/views/layouts/admin_header.php`
- `app/views/layouts/admin_sidebar.php`
- `app/views/layouts/admin_topbar.php`
- `app/views/layouts/admin_footer.php`
- `app/views/layouts/admin_layout.php`
- `app/views/admin/clientes/clientes_content.php`

## Responsabilidades

- `admin_layout.php`: valida acesso, resolve o conteudo, renderiza alertas e monta o shell.
- `admin_header.php`: metadados, Bootstrap e CSS global.
- `admin_sidebar.php`: navegacao global admin.
- `admin_topbar.php`: titulo, subtitulo, usuario e acoes rapidas.
- `admin_footer.php`: rodape e comportamento mobile do menu.
- `clientes.php`: exemplo refatorado como controlador.
- `clientes_content.php`: exemplo de view limpa.

## Compatibilidade

- Mantem `require_admin()`.
- Mantem helpers existentes: `h()`, `url()`, `asset()` e `public_url()`.
- Mantem redirect protegido para login com `next`.
- Nao altera banco de dados, autenticacao, dashboard, leads, vendas ou financeiro.

## Proximas fases

1. Migrar gradualmente paginas admin antigas para o novo padrao.
2. Criar componentes pequenos para tabelas, cards KPI, filtros e estados vazios.
3. Centralizar flash messages em helpers dedicados.
4. Padronizar encoding UTF-8 nos textos antigos.
5. Criar uma pasta `app/controllers/admin/` quando o volume de logica crescer.
