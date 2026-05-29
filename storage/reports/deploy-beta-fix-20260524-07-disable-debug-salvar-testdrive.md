# Deploy Beta Fix 07 - Remover Debug de salvar_testdrive.php

Data: 2026-05-24

## Bloqueador tratado

Categoria: Bloqueia deploy

- Remover debug explícito e erros técnicos em rota pública ativa.

## Correção aplicada

Arquivo atualizado:

- `public/salvar_testdrive.php`

Removido:

```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

Substituído:

- `die("Erro prepare: " . mysqli_error($conexao))`
- `die("Erro ao salvar lead: " . mysqli_stmt_error($stmt))`

Por mensagem amigável genérica com `HTTP 500`:

- `Não foi possível guardar o pedido de test drive neste momento. Tente novamente mais tarde.`

## Preservado

- Método `POST`.
- Validação de campos obrigatórios.
- Insert original na tabela `leads`.
- Montagem de mensagem WhatsApp.
- Redirect original para WhatsApp.

## Sincronização XAMPP

Também foi sincronizada a cópia servida pelo XAMPP:

- `C:\xampp\htdocs\RG_AUTO_SALES\public\salvar_testdrive.php`

## Validação

Lint individual:

- `C:\xampp\php\php.exe -l public/salvar_testdrive.php`
- Resultado: sem erros de sintaxe.

Lint PHP global:

- `powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1`
- Resultado: `Arquivos OK: 198`, `Arquivos com erro: 0`.
- Relatório: `storage/reports/php-lint-20260524-235131.txt`.

Varredura:

- Sem `display_errors`.
- Sem `error_reporting(E_ALL)`.
- Sem `mysqli_error`/`mysqli_stmt_error` expostos.
- Sem `var_dump`/`print_r`.

Teste HTTP local:

- URL: `http://localhost/RG_AUTO_SALES/public/salvar_testdrive.php`
- Resultado: `HTTP/1.1 302 Found`
- Redirect esperado para `public/test_drive.php` em acesso sem `POST`.

## Status

Resolvido para `public/salvar_testdrive.php`.
