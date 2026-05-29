# RG Auto Sales - Fix 26 - CSRF criacao de utilizador/admin

Data: 2026-05-25  
Fase: Deploy Beta Privado / hardening final  
Escopo: P0 de criacao de utilizador/admin sem CSRF identificado na auditoria v6

## Problema encontrado

A auditoria v6 identificou `admin/criar_user.php` como rota real de criacao de conta interna sem protecao CSRF.

Fluxo anterior:

```text
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email    = $_POST['email'];
    $senha    = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role     = $_POST['role'];

    INSERT INTO users (username, email, password, role)
}
```

Impacto: um admin autenticado poderia ser induzido por CSRF a criar uma conta interna com role `admin` ou `vendedor`.

## Arquivos reais identificados

Busca realizada:

```powershell
rg -n "INSERT INTO users|INSERT INTO utilizadores|INSERT INTO vendedores|password_hash|criar_user|create user|role" admin app public actions views includes -g "*.php"
```

Arquivo real dentro do escopo:

- `admin/criar_user.php`

Observacao: tambem existe `app/modules/cars/actions/vender_carro.php`, mas ele cria pedidos comerciais em `vendedores`, nao conta interna/admin. Ficou fora do escopo deste fix, conforme pedido para nao mexer em CRM/leads/carros/outros P0.

## Alteracao aplicada

Arquivo alterado:

- `admin/criar_user.php`

Mudancas:

- Mantido `require_admin()` como protecao de sessao/admin.
- Mantido GET apenas para exibir formulario.
- Criacao permanece restrita a `POST`.
- POST agora exige `csrf_token` valido.
- Validacao feita com `hash_equals($_SESSION['csrf_token'], $csrfToken)`.
- POST sem token ou token invalido retorna `HTTP 403` com `CSRF invalido.`.
- Formulario agora inclui `csrf_input()`.
- Campos existentes (`username`, `email`, `password`, `role`) passaram a ser normalizados/validados no servidor antes do INSERT.
- `password_hash($password, PASSWORD_DEFAULT)` foi preservado.
- Roles existentes foram preservadas: `admin` e `vendedor`.
- Query de criacao continua usando prepared statement.

## Fora do escopo

Nao foram alterados:

- follow-up;
- CRM inbox;
- leads;
- carros;
- fluxo publico/administrativo de pedidos de vendedor;
- outras rotas P0 da auditoria v6.

## Validacoes feitas

### Lint individual

Comando:

```powershell
C:\xampp\php\php.exe -l admin\criar_user.php
```

Resultado:

```text
No syntax errors detected in admin\criar_user.php
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
Relatorio: C:\Users\rickg\OneDrive\Ambiente de Trabalho\RG_AUTO_SALES\storage\reports\php-lint-20260525-213255.txt
```

## Resultado HTTP local

Ambiente:

- servidor PHP local em `127.0.0.1:8796`;
- MariaDB temporario do workspace em `storage/temp/mysql-marcar-vendido-test`;
- sessao admin sintetica com `PHPSESSID=codexcsrf26`;
- token valido da sessao: `valid-token-26`.

### GET autenticado

Comando:

```powershell
curl.exe -i -s -b "PHPSESSID=codexcsrf26" http://127.0.0.1:8796/admin/criar_user.php
```

Resultado relevante:

```text
HTTP/1.1 200 OK
<form method="POST">
    <input type="hidden" name="csrf_token" value="valid-token-26">
```

Conclusao: GET nao cria utilizador/admin; apenas renderiza o formulario com CSRF.

### POST autenticado sem CSRF

Comando:

```powershell
curl.exe -i -s -b "PHPSESSID=codexcsrf26" -X POST `
  -d "username=csrf-no-token&email=csrf-no-token@example.test&password=secret123&role=admin" `
  http://127.0.0.1:8796/admin/criar_user.php
```

Resultado:

```text
HTTP/1.1 403 Forbidden
CSRF invalido.
```

Conclusao: POST sem CSRF valido e bloqueado antes da criacao de utilizador/admin.

## Conclusao

O P0 de criacao de utilizador/admin sem CSRF foi corrigido em `admin/criar_user.php`.

A rota continua exigindo admin autenticado, preserva as regras atuais de criacao, mantem o hash de senha existente e agora bloqueia POST sem CSRF com `403`.
