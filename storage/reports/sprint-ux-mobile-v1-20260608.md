# Sprint UX Mobile v1 - RG Auto Sales

Data: 2026-06-08

## Escopo

Correcoes aplicadas apenas no site publico, sem alterar queries SQL, inserts, backend, regras de negocio, CRM, vendas, financeiro ou protecoes de seguranca.

## Arquivos alterados nesta sprint

- `public/assets/css/style.css`
- `includes/header_public.php`
- `public/leasing.php`
- `public/vender_carro.php`

## Correcoes aplicadas

- Adicionada camada final de CSS responsivo para remover scroll horizontal em containers, rows, cards, imagens, iframes e grids.
- Forcado `box-sizing: border-box` global e limites `max-width: 100%`.
- Inputs, selects, textareas e botoes recebem tamanho confortavel no mobile, com fonte minima de 16px.
- Botoes em mobile passam a ocupar largura confortavel e altura minima de toque.
- Cards de `products.php` ficam 1 por linha no mobile via override final para `.col-4`/`.product-card`.
- `product-details.php` passa a forcar galeria/informacoes em coluna e sem overflow nos grids internos.
- `header_public.php` passou a ter `menutoggle()` defensivo e `aria-expanded`, para paginas que usam o header sem script proprio.
- `leasing.php` agora carrega documento HTML completo e `style.css`, mantendo o fluxo e o handler existentes.
- Honeypots continuam ativos, mas foram ajustados via CSS para nao criarem area rolavel fora da tela.

## Validacao responsiva

Ferramenta: Edge headless via DevTools Protocol, com servidor PHP local e `session.save_path` apontado para `storage/temp` para evitar warnings de sessao do sandbox.

Larguras testadas:

- 390px
- 430px
- 768px
- Desktop 1280px

Paginas testadas:

- `/public/products.php`
- `/public/product-details.php?id=1`
- `/public/importar_carro.php`
- `/public/vender_carro.php`
- `/public/contacto.php`
- `/public/leasing.php`
- `/public/test_drive.php`

Resultados:

- Scroll horizontal: `overflow = 0` nas paginas testadas em 390px, 430px, 768px e desktop.
- Inputs no mobile: controles visiveis sem fonte abaixo de 16px e sem altura abaixo de 44px.
- Menu mobile: abre/fecha corretamente nas paginas com `#MenuItems` e `.menu-icon`.
- `products.php`: catalogo carregado com cards em mobile e links para detalhes.
- `product-details.php`: WhatsApp e Test Drive presentes; galeria/informacoes em coluna no mobile.
- Fluxo validado por links: `products -> product-details`, `product-details -> WhatsApp`, `product-details -> Test Drive`.

## PHP lint

Comando:

`powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1`

Resultado:

- Arquivos OK: 204
- Arquivos com erro: 0
- Relatorio: `storage/reports/php-lint-20260608-215037.txt`

## Observacoes

- Nao foram removidas nem alteradas protecoes CSRF, honeypot ou rate limit.
- Nao houve alteracao de SQL, inserts, CRM, vendas, financeiro ou admin.
- O browser interno do Codex bloqueou localhost com `ERR_BLOCKED_BY_CLIENT`; por isso a validacao visual foi feita com Edge headless local.
