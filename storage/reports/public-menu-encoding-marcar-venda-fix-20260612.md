# Public menu, encoding e marcar venda - 2026-06-12

## Resumo

Foram corrigidos bugs reais no header/menu publico e melhorada a UI de `admin/vendas/marcar_venda.php`, sem alterar regras de negocio, calculos, comissoes, inserts, updates, redirects, permissoes, POST, CSRF ou honeypot.

## Ficheiros alterados

- `includes/header_public.php`
- `public/vender_carro.php`
- `public/importar_carro.php`
- `public/assets/css/style.css`
- `admin/vendas/marcar_venda.php`

## Origem do `InÃ­cio`

A origem comum encontrada foi `includes/header_public.php`, onde o link do menu publico estava corrompido como `InÃ­cio`. O texto foi corrigido para `Início` no include, e a verificacao de mojibake em `public/*.php`, `public/includes/*.php`, `includes/header_public.php`, `includes/footer_public.php` e `admin/vendas/marcar_venda.php` terminou sem ocorrencias.

Tambem havia copias antigas do menu em paginas publicas como `about.php`, `contacto.php` e `test_drive.php`; a verificacao final confirmou que essas paginas servidas ja apresentam `Início` e nao `InÃ­cio`.

## Correcoes aplicadas

- `public/vender_carro.php`
  - Passou a incluir o header publico completo via `includes/header_public.php`.
  - Foi adicionado footer publico e WhatsApp flutuante reutilizavel.
  - CSS local foi escopado para evitar quebrar header/footer.
  - Mobile foi ajustado para manter menu acessivel e conteudo sem corte lateral.

- `public/importar_carro.php`
  - Menu local incompleto foi removido.
  - A pagina passou a usar o mesmo header publico completo.
  - Foi removida a regra que deixava a navbar desalinhada dentro do hero.
  - Mobile foi ajustado para evitar corte lateral no hero/formulario.

- `includes/header_public.php`
  - Corrigido `Início`.
  - Adicionado link `Vender`.
  - Adicionada regra mobile pequena para garantir botao hamburguer visivel em paginas publicas.

- `admin/vendas/marcar_venda.php`
  - UI reorganizada em layout limpo com cabecalho, resumo de cliente/carro, card de formulario, inputs maiores e responsividade.
  - Mantidos `require_admin()`, POST, CSRF, hidden `lead_id`, validacoes, calculos, transacao, inserts, updates e redirects existentes.

## Validacao

- PHP lint completo:
  - `Arquivos OK: 220`
  - `Arquivos com erro: 0`
  - Relatorio: `storage/reports/php-lint-20260612-032000.txt`

- Paginas publicas testadas com HTTP 200:
  - `vender_carro.php`
  - `importar_carro.php`
  - `test_drive.php`
  - `about.php`
  - `contacto.php`

- Menus confirmados no HTML servido:
  - `vender_carro.php`: `Início | Carros | Sobre | Contacto | Test Drive | Leasing | Importar | Vender | Conta`
  - `importar_carro.php`: `Início | Carros | Sobre | Contacto | Test Drive | Leasing | Importar | Vender | Conta`
  - `test_drive.php`: `Início | Carros | Sobre | Contacto | Conta | Test Drive | Leasing | Importar | Vender`
  - `about.php`: `Início | Carros | Sobre | Contacto | Conta | Test Drive | Leasing | Importar | Vender`
  - `contacto.php`: `Início | Carros | Sobre | Contacto | Conta | Test Drive | Leasing | Importar | Vender`

- CSRF/formularios:
  - `POST public/vender_carro.php` sem CSRF: `403`
  - `POST public/importar_carro.php` sem CSRF: `403`
  - `csrf_input()` e honeypot foram preservados nos formularios publicos.

- Validacao visual:
  - Chrome headless em desktop e mobile.
  - Capturas principais em `storage/temp/public-menu-validation/`.
  - `marcar_venda.php` foi validado com fixture HTML temporaria gerada a partir da propria pagina, usando sessao admin simulada e POST sem `preco_venda`/`data_venda`, para renderizar o formulario sem acionar venda, inserts ou updates.

## Observacoes

O Browser integrado falhou no Windows com bloqueio de sandbox, por isso a validacao visual foi feita com Chrome headless local. Os erros de extensoes Google exibidos pelo Chrome nao afetaram as capturas nem o HTTP das paginas.
