# Deploy Beta Fix 05 - Remover Debug Explícito do Detalhe Público do Carro

Data: 2026-05-24

## Bloqueador tratado

Categoria: Bloqueia deploy

- Remover exposição de erros técnicos ao usuário final em rotas públicas/ativas.

## Correção aplicada

Arquivo atualizado:

- `public/product-details.php`

Removido:

```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

Também foi sincronizada a cópia servida pelo XAMPP:

- `C:\xampp\htdocs\RG_AUTO_SALES\public\product-details.php`

## Validação

Lint individual:

- `C:\xampp\php\php.exe -l public/product-details.php`
- Resultado: sem erros de sintaxe.

Lint PHP global:

- `powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1`
- Resultado: `Arquivos OK: 198`, `Arquivos com erro: 0`.
- Relatório: `storage/reports/php-lint-20260524-234534.txt`.

Varredura:

- `public/product-details.php` não contém mais `display_errors` nem `error_reporting(E_ALL)`.
- A cópia em `C:\xampp\htdocs\RG_AUTO_SALES\public\product-details.php` também não contém mais esses comandos.

Teste HTTP local:

- URL: `http://localhost/RG_AUTO_SALES/carro/1`
- Resultado: `HTTP/1.1 200 OK`.

## Status

Resolvido para `public/product-details.php`.

## Observação

Ainda existem `die(...)` de validação nesta rota. Como não expõem SQL diretamente no caminho comum, ficam para uma correção específica posterior se necessário.
