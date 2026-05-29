# Deploy Beta Fix 06 - Remover Debug de Formulario_cliente.php

Data: 2026-05-24

## Bloqueador tratado

Categoria: Bloqueia deploy

- Remover debug explícito e erros técnicos em rota pública ativa.

## Correção aplicada

Arquivo atualizado:

- `public/Formulario_cliente.php`

Removido:

```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

Substituído:

- `die("Erro prepare: " . mysqli_error($conexao))`
- `die("Erro ao guardar agendamento: " . mysqli_stmt_error($stmt))`

Por mensagem amigável genérica com `HTTP 500`:

- `Não foi possível guardar o agendamento neste momento. Tente novamente mais tarde.`

## Preservado

- Método `POST`.
- Validações de campos obrigatórios.
- Validação de data.
- Insert em `clientes`.
- Insert em `leads`.
- Insert em `mensagens`.
- Redirect para WhatsApp.

## Sincronização XAMPP

Também foi sincronizada a cópia servida pelo XAMPP:

- `C:\xampp\htdocs\RG_AUTO_SALES\public\Formulario_cliente.php`

## Validação

Lint individual:

- `C:\xampp\php\php.exe -l public/Formulario_cliente.php`
- Resultado: sem erros de sintaxe.

Lint PHP global:

- `powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1`
- Resultado: `Arquivos OK: 198`, `Arquivos com erro: 0`.
- Relatório: `storage/reports/php-lint-20260524-234955.txt`.

Varredura:

- Sem `display_errors`.
- Sem `error_reporting(E_ALL)`.
- Sem `mysqli_error`/`mysqli_stmt_error` expostos.
- Sem `var_dump`/`print_r`.

Teste HTTP local:

- URL: `http://localhost/RG_AUTO_SALES/public/Formulario_cliente.php`
- Resultado: `HTTP/1.1 302 Found`
- Redirect esperado para `public/test_drive.php` em acesso sem `POST`.

## Status

Resolvido para `public/Formulario_cliente.php`.
