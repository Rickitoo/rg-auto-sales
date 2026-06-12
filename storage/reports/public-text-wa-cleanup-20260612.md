# Limpeza de textos publicos e WhatsApp flutuante

Data: 2026-06-12

## Escopo executado

Melhoria segura limitada ao website publico:

- Correcao de textos com encoding/mojibake em `public/*.php`.
- Criacao de include unico para o botao WhatsApp flutuante.
- Substituicao de duplicacoes do botao flutuante pelo include.

Nao foram alterados backend, CRM, vendas, financeiro, importacao, autenticacao, CSRF, banco de dados, actions de formularios, inputs hidden, `csrf_input()` ou honeypot.

## Ficheiros alterados nesta tarefa

- `public/includes/wa_float.php`
- `public/about.php`
- `public/cart.php`
- `public/contacto.php`
- `public/products.php`
- `public/product-details.php`
- `public/test_drive.php`

Nota: o workspace ja tinha outras alteracoes pendentes antes desta tarefa. Elas foram preservadas.

## WhatsApp flutuante

Foi criado o include:

- `public/includes/wa_float.php`

Numero oficial mantido:

- `+258 862 934 721`
- Link base: `https://wa.me/258862934721`

Paginas que agora usam o include:

- `public/about.php`
- `public/cart.php`
- `public/contacto.php`
- `public/products.php`
- `public/product-details.php`
- `public/test_drive.php`

O detalhe do produto preserva o link contextual existente por meio de `$waFloatHref = $wa`.
O test drive preserva mensagem contextual por meio de `$waFloatText`.

## Textos corrigidos

Exemplos confirmados:

- `InÃ­cio` -> `Início`
- `confianÃ§a` -> `confiança`
- `informaÃ§Ãµes` -> `informações`
- `satisfaÃ§Ã£o` -> `satisfação`
- `veÃ­culos` -> `veículos`
- `preÃ§o` -> `preço`
- `missÃ£o` -> `missão`
- `experiÃªncia` -> `experiência`
- `seleÃ§Ã£o` -> `seleção`
- `automÃ³veis` -> `automóveis`
- `negociaÃ§Ã£o` -> `negociação`
- `relaÃ§Ã£o` -> `relação`
- `AlÃ©m` -> `Além`
- `vocÃª` -> `você`
- `estÃ¡` -> `está`
- `CatÃ¡logo` -> `Catálogo`
- `PreÃ§o mÃ­nimo` -> `Preço mínimo`
- `PreÃ§o mÃ¡ximo` -> `Preço máximo`
- `CaracterÃ­sticas` -> `Características`
- `FormulÃ¡rio` -> `Formulário`
- `ObservaÃ§Ãµes` -> `Observações`
- `horÃ¡rio` -> `horário`
- `pÃ¡gina` -> `página`
- `cÃ¡lculo` -> `cálculo`

Tambem foram corrigidas entidades de aspas/travos corrompidos em textos publicos, como `â€œ...â€` e `â€“`.

## Validacao

### Mojibake

Foi executada verificacao automatica por codepoints nos ficheiros:

- `public/*.php`
- `public/includes/*.php`

Resultado:

- `mojibake-check-ok`

### PHP lint completo

Comando:

`powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1`

Resultado:

- Arquivos OK: 220
- Erros: 0
- Relatorio gerado: `storage/reports/php-lint-20260612-020748.txt`

### HTTP 200

Paginas testadas com servidor PHP local:

- `public/index.php`: 200
- `public/products.php`: 200
- `public/product-details.php?id=3`: 200
- `public/importar_carro.php`: 200
- `public/vender_carro.php`: 200
- `public/contacto.php`: 200
- `public/about.php`: 200
- `public/test_drive.php`: 200
- `public/cart.php`: 200

### WhatsApp

Confirmado em HTML renderizado:

- `https://wa.me/258862934721?text=...`

O link usa o numero oficial da RG Auto Sales e abre com mensagem codificada para WhatsApp.
