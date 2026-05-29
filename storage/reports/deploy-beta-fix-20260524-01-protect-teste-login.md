# Deploy Beta Fix 01 - Proteger teste_login.php

Data: 2026-05-24

## Bloqueador tratado

Categoria: Bloqueia deploy

- Remover, bloquear ou proteger `teste_login.php`.

## Correção aplicada

No workspace:

- `teste_login.php` foi removido.
- `.htaccess` recebeu defesa adicional para bloquear `teste_login.php`.

No ambiente local servido pelo XAMPP:

- Foi encontrada uma cópia ativa em `C:\xampp\htdocs\RG_AUTO_SALES\teste_login.php`.
- Essa cópia foi neutralizada para responder `404` sem expor dados.

Conteúdo final da cópia servida:

```php
<?php
http_response_code(404);
exit;
```

## Por que foi necessário

O `localhost/RG_AUTO_SALES` estava servindo `C:\xampp\htdocs\RG_AUTO_SALES`, não o diretório de trabalho atual em OneDrive. Por isso, remover o arquivo no workspace não bastou para fechar o risco no HTTP local.

## Validação

Lint individual da cópia servida:

- `C:\xampp\php\php.exe -l C:\xampp\htdocs\RG_AUTO_SALES\teste_login.php`
- Resultado: sem erros de sintaxe.

Lint PHP global do workspace:

- `powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1`
- Resultado: `Arquivos OK: 198`, `Arquivos com erro: 0`.
- Relatório: `storage/reports/php-lint-20260524-233627.txt`.

Teste HTTP local:

- URL: `http://localhost/RG_AUTO_SALES/teste_login.php`
- Resultado: `HTTP/1.1 404 Not Found`.

## Status

Resolvido.

## Próximo bloqueador recomendado

Bloquear acesso público a pastas internas e backups:

- `.git/`
- `storage/`
- `app/`
- `vendor/`
- `scripts/`
- `includes/`
- dumps SQL
- backups
