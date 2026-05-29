# Deploy Beta Fix 02 - Bloquear Pastas Internas e Arquivos Sensíveis

Data: 2026-05-24

## Bloqueador tratado

Categoria: Bloqueia deploy

- Garantir que `.git/`, `storage/`, `app/`, `vendor/`, `scripts/`, `includes/`, dumps SQL e backups não são acessíveis pelo navegador.

## Correção aplicada

Arquivo atualizado:

- `.htaccess`

Regras adicionadas antes da liberação de arquivos existentes:

```apache
RewriteRule ^(\.git|app|storage|vendor|scripts|includes)(/|$) - [F,L]
RewriteRule \.(env|sql|bak|backup|zip|tar|gz|7z)$ - [F,L]
```

Também foi sincronizado o `.htaccess` para a pasta servida pelo XAMPP:

- `C:\xampp\htdocs\RG_AUTO_SALES\.htaccess`

## Validação

Lint PHP global:

- `powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1`
- Resultado: `Arquivos OK: 198`, `Arquivos com erro: 0`.
- Relatório: `storage/reports/php-lint-20260524-233950.txt`.

Testes HTTP locais:

- `http://localhost/RG_AUTO_SALES/app/core/bootstrap.php`
  - Resultado: `HTTP/1.1 403 Forbidden`
- `http://localhost/RG_AUTO_SALES/includes/layout_top.php`
  - Resultado: `HTTP/1.1 403 Forbidden`
- `http://localhost/RG_AUTO_SALES/scripts/lint-php.ps1`
  - Resultado: `HTTP/1.1 403 Forbidden`
- `http://localhost/RG_AUTO_SALES/admin/dashboard.php`
  - Resultado: `HTTP/1.1 302 Found`
  - Redirect preservado para login.

## Status

Resolvido para as pastas internas testadas.

## Próximo bloqueador recomendado

Remover exposição de erros técnicos em rotas públicas/ativas:

- `error_reporting(E_ALL)`
- `die("Erro...")` com detalhes técnicos
