# RG Auto Sales - Fix 24 - CSRF pedir_saque

Data: 2026-05-25  
Fase: Deploy Beta Privado / hardening final  
Escopo: `app/modules/finance/pedir_saque.php`

## Problema encontrado

A auditoria v5 identificou `app/modules/finance/pedir_saque.php` como rota financeira que processava pedido de saque via POST sem validacao CSRF.

Fluxo anterior:

```text
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valor = (float)$_POST['valor'];
    INSERT INTO saques (user_id, valor)
    UPDATE wallet SET saldo_disponivel = saldo_disponivel - $valor
}
```

Tambem havia dependencia de `$_SESSION['user_id']` sem normalizacao/validacao positiva explicita.

## Arquivo real identificado

- `app/modules/finance/pedir_saque.php`

Busca de chamadas:

```powershell
rg -n "pedir_saque\.php|pedir_saque" admin app views public -S
```

Resultado: nao foram encontradas chamadas ativas em `admin`, `app`, `views` ou `public`.

## Alteracao aplicada

- GET e qualquer metodo diferente de POST agora redirecionam para `public/dashboard.php?msg=metodo_invalido`.
- POST exige `csrf_token` valido com `hash_equals()`.
- `user_id` e validado como inteiro positivo.
- `valor` e validado como valor monetario positivo.
- A regra financeira foi preservada:
  - buscar `saldo_disponivel`;
  - bloquear valor invalido;
  - bloquear saldo insuficiente;
  - inserir pedido em `saques`;
  - descontar o valor de `wallet.saldo_disponivel`;
  - redirecionar para `public/dashboard.php?ok=1`.
- As queries foram trocadas para prepared statements sem alterar calculos ou regra de negocio.

## Arquivos alterados

- `app/modules/finance/pedir_saque.php`
- `storage/reports/deploy-beta-fix-20260525-24-csrf-pedir-saque.md`

## Fora do escopo

Nao foram alterados:

- `app/modules/finance/marcar_vendido.php`;
- CRM;
- carros/fotos;
- uploads.

## Validacoes feitas

### Lint individual

```text
C:\xampp\php\php.exe -l app/modules/finance/pedir_saque.php
No syntax errors detected in app/modules/finance/pedir_saque.php
```

### Lint global

Comando:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1
```

Resultado:

```text
PHP CLI: C:\xampp\php\php.exe
Arquivos OK: 199
Arquivos com erro: 0
Relatorio: C:\Users\rickg\OneDrive\Ambiente de Trabalho\RG_AUTO_SALES\storage\reports\php-lint-20260525-163001.txt
```

## Resultado HTTP local

Teste local com servidor PHP em `127.0.0.1:8793`, MySQL local e sessao admin sintetica.

### GET direto

```text
GET /app/modules/finance/pedir_saque.php?valor=1
HTTP/1.1 302 Found
Location: /public/dashboard.php?msg=metodo_invalido
```

Conclusao: GET direto nao cria nem processa saque.

### POST sem CSRF

```text
POST /app/modules/finance/pedir_saque.php
Body: valor=1
HTTP/1.1 403 Forbidden
CSRF invalido.
```

Conclusao: POST sem token CSRF valido e bloqueado antes do processamento financeiro.

## Observacoes de seguranca

- A rota continua protegida por `require_admin()`.
- O pedido de saque agora exige POST autenticado com CSRF valido.
- `user_id` e `valor` sao normalizados e validados antes de qualquer escrita.
- Nao foi encontrada view ativa chamando essa rota, portanto nao houve formulario antigo para converter.
- A regra financeira de saque/retirada nao foi alterada.

## Conclusao

O P0 em `app/modules/finance/pedir_saque.php` foi corrigido. A rota nao aceita mais processamento sem CSRF e GET direto nao realiza acao financeira.
