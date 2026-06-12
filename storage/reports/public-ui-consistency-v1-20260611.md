# Public UI Consistency v1 - 2026-06-11

## Arquivos alterados

- `includes/public_car_images.php`
- `public/index.php`
- `public/products.php`
- `public/product-details.php`
- `public/about.php`
- `public/contacto.php`
- `public/test_drive.php`
- `public/importar_carro.php`
- `public/assets/css/style.css`

## Footers unificados

Substituidos footers HTML duplicados por `includes/footer_public.php` em:

- `public/about.php`
- `public/contacto.php`
- `public/test_drive.php`
- `public/products.php`
- `public/product-details.php`
- `public/importar_carro.php`

Os scripts especificos das paginas foram preservados. Em `products.php` e `product-details.php`, scripts duplicados de menu foram removidos porque `includes/header_public.php` ja define o comportamento do menu.

## Headers ajustados

- `public/products.php`, `public/product-details.php` e `public/leasing.php` ja usam `includes/header_public.php`.
- `public/index.php` manteve header proprio porque esta acoplado ao carousel/hero da home.
- `public/about.php`, `public/contacto.php`, `public/test_drive.php` e `public/importar_carro.php` mantiveram header proprio porque o titulo/hero fica dentro do header atual. Forcar `includes/header_public.php` nesta etapa teria risco visual maior.

## Fallback de imagens implementado

- Criado `includes/public_car_images.php` com:
  - `public_car_image_url()`
  - `public_car_placeholder_url()`
  - `public_car_img_fallback_attr()`
- Aplicado em:
  - `public/index.php`
  - `public/products.php`
  - `public/product-details.php`
- Quando a imagem do carro esta vazia ou o arquivo local nao existe, o sistema usa `public/assets/ImagensRG/logo.png`.
- Adicionado `onerror` nas imagens de carros para trocar para placeholder se a imagem quebrar no browser.
- Adicionado alt text seguro com fallback `Carro RG Auto Sales`.
- Adicionado `loading="lazy"` nas imagens de cards/listas e miniaturas abaixo da dobra.
- Adicionado `width`/`height` onde ja havia dimensoes estaveis no CSS.

## Testes feitos

- `php -l` nos arquivos PHP criados/alterados:
  - `includes/public_car_images.php`
  - `public/index.php`
  - `public/products.php`
  - `public/product-details.php`
  - `public/about.php`
  - `public/contacto.php`
  - `public/test_drive.php`
  - `public/importar_carro.php`
- Lint completo do projeto:
  - Script: `scripts/lint-php.ps1`
  - PHP CLI: `C:\xampp\php\php.exe`
  - Resultado: 219 arquivos OK, 0 erros.
  - Relatorio gerado: `storage/reports/php-lint-20260611-040029.txt`
- Teste HTTP local com servidor PHP em `127.0.0.1:8798`:
  - `public/index.php`: 200
  - `public/products.php`: 200
  - `public/product-details.php?id=1`: 200
  - `public/importar_carro.php`: 200
  - `public/vender_carro.php`: 200
  - `public/leasing.php`: 200
  - `public/contacto.php`: 200
  - `public/about.php`: 200
- `public/contacto.html` e `public/about.html` retornaram 404 porque nao existem no projeto atual; as paginas equivalentes sao `contacto.php` e `about.php`.
- Browser interno:
  - `products.php`: footer publico presente, 23 imagens de cards com lazy/fallback, 0 imagens quebradas.
  - `product-details.php?id=1`: footer publico presente, galeria e relacionados renderizados, 0 erros de console.
  - `index.php`: carousel preservado, footer publico presente, 8 imagens de carros com lazy/fallback, 0 imagens quebradas.

## Problemas encontrados

- O browser interno tinha um cookie de sessao expirado e a primeira tentativa de abrir `products.php` caiu no login; recarregando apos a sessao limpar, a pagina abriu normalmente.
- `style.css` referenciava `ImagensRG/Logo moderno da RG Auto Sales.png`, que retornava 404. Corrigido para `../ImagensRG/Logo_moderno_RG_Auto_Sales.png`.
- `vender_carro.php` e `leasing.php` nao tinham footer duplicado detectado. Foram testadas e preservadas para evitar alteracao visual fora do escopo seguro.

## Recomendacao final

Manter esta rodada como melhoria publica incremental. Em uma proxima etapa, avaliar uma migracao controlada dos headers com hero embutido para um componente comum parametrizavel, mas apenas depois de criar suporte explicito para titulo, subtitulo e acoes por pagina.
