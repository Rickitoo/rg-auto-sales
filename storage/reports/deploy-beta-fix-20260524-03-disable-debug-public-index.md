# Deploy Beta Fix 03 - Remover Debug Explícito da Home Pública

Data: 2026-05-24

## Bloqueador tratado

Categoria: Bloqueia deploy

- Remover exposição de erros técnicos ao usuário final, especialmente `error_reporting(E_ALL)` e `display_errors=1` em rotas públicas/ativas.

## Correção aplicada

Arquivo atualizado:

- `public/index.php`

Removido:

```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

Também foi sincronizada a cópia servida pelo XAMPP:

- `C:\xampp\htdocs\RG_AUTO_SALES\public\index.php`

## Validação

Lint individual:

- `C:\xampp\php\php.exe -l public/index.php`
- Resultado: sem erros de sintaxe.

Lint PHP global:

- `powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1`
- Resultado: `Arquivos OK: 198`, `Arquivos com erro: 0`.
- Relatório: `storage/reports/php-lint-20260524-234204.txt`.

Varredura:

- `public/index.php` não contém mais `display_errors` nem `error_reporting(E_ALL)`.
- A cópia em `C:\xampp\htdocs\RG_AUTO_SALES\public\index.php` também não contém mais esses comandos.

Teste HTTP local:

- URL: `http://localhost/RG_AUTO_SALES/`
- Resultado: `HTTP/1.1 302 Found`
- Observação: o redirect para login é comportamento atual do bootstrap/sessão local e não foi alterado para preservar autenticação.

## Status

Resolvido para `public/index.php`.

## Próximo bloqueador recomendado

Remover debug explícito das demais rotas públicas ativas:

- `public/products.php`
- `public/product-details.php`
- `public/Formulario_cliente.php`
- `public/salvar_testdrive.php`
- `public/confirmacao.php`
