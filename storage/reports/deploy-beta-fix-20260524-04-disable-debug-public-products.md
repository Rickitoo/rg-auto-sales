# Deploy Beta Fix 04 - Remover Debug Explícito do Catálogo Público

Data: 2026-05-24

## Bloqueador tratado

Categoria: Bloqueia deploy

- Remover exposição de erros técnicos ao usuário final em rotas públicas/ativas.

## Correção aplicada

Arquivo atualizado:

- `public/products.php`

Removido:

```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

Também foi sincronizada a cópia servida pelo XAMPP:

- `C:\xampp\htdocs\RG_AUTO_SALES\public\products.php`

## Validação

Lint individual:

- `C:\xampp\php\php.exe -l public/products.php`
- Resultado: sem erros de sintaxe.

Lint PHP global:

- `powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1`
- Resultado: `Arquivos OK: 198`, `Arquivos com erro: 0`.
- Relatório: `storage/reports/php-lint-20260524-234351.txt`.

Varredura:

- `public/products.php` não contém mais `display_errors` nem `error_reporting(E_ALL)`.
- A cópia em `C:\xampp\htdocs\RG_AUTO_SALES\public\products.php` também não contém mais esses comandos.

Teste HTTP local:

- URL: `http://localhost/RG_AUTO_SALES/carros`
- Resultado: `HTTP/1.1 200 OK`.

## Status

Resolvido para `public/products.php`.

## Observação

Ainda existem `die("Erro...")` técnicos nesta rota em falhas SQL. Eles devem ser tratados em uma correção separada para manter o escopo pequeno.
