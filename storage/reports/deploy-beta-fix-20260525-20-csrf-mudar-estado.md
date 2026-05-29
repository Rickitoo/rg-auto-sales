# Correcao Deploy Beta 20 - CSRF em mudar_estado.php

Data: 2026-05-25  
Arquivo principal: `admin/mudar_estado.php`  
Bloqueador tratado: alteracao de `clientes.estado` por GET e SQL inseguro no parametro `estado`.

## Problema encontrado

`admin/mudar_estado.php` alterava estado de cliente usando parametros de URL:

```php
$id = intval($_GET['id']);
$estado = $_GET['estado'];

mysqli_query($conexao, "
UPDATE clientes SET estado='$estado' WHERE id=$id
");
```

Riscos:

- alteracao destrutiva por GET;
- ausencia de CSRF;
- ausencia de whitelist para `estado`;
- interpolacao direta de `estado` no SQL.

## Alteracao aplicada

- GET direto foi bloqueado/redirecionado.
- A rota agora exige `POST`.
- A sessao/admin continua validada pelo padrao atual: `require_admin()`.
- `csrf_token` e validado com `hash_equals()`.
- `id` e validado como inteiro positivo.
- `estado` e validado por whitelist segura baseada nos estados existentes usados no projeto:
  - `novo`
  - `negociacao`
- O `UPDATE clientes` passou a usar prepared statement com `bind_param`.
- Redirect de sucesso preservado para `admin/leads/leads.php`.
- GET invalido redireciona para `admin/leads/leads.php?msg=metodo_invalido`.

Nao foram alterados fotos, uploads, leads/status, cron, financeiro ou `marcar_pago.php`.

## Arquivos alterados

- `admin/mudar_estado.php`
- `storage/reports/deploy-beta-fix-20260525-20-csrf-mudar-estado.md`

## Chamadores verificados

Busca:

```powershell
rg -n "mudar_estado\.php|estado=" admin app public -S
```

Resultado:

- nenhum link/chamador atual para `mudar_estado.php` encontrado;
- usos existentes de `estado` aparecem em `admin/dashboard_vendas.php` com valores `novo` e `negociacao`, usados para montar a whitelist.

Portanto, nao havia links GET antigos a converter para formulario POST.

## Validacoes feitas

### Lint PHP individual

Comando:

```powershell
C:\xampp\php\php.exe -l admin\mudar_estado.php
```

Resultado:

```text
No syntax errors detected in admin\mudar_estado.php
```

### Lint PHP global

Comando:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1
```

Resultado:

- Arquivos OK: 199
- Arquivos com erro: 0
- Relatorio: `storage/reports/php-lint-20260525-125219.txt`

## Resultado HTTP local

Teste executado com servidor PHP temporario e MySQL local do XAMPP.

### Sem sessao

- `GET /admin/mudar_estado.php?id=1&estado=negociacao`
  - Resultado: `302`
  - Redirect: `/auth/login.php?...`
- `POST /admin/mudar_estado.php`
  - Body: `id=1&estado=negociacao`
  - Resultado: `302`
  - Redirect: `/auth/login.php?...`

### Com sessao admin sintetica, sem CSRF

- `GET /admin/mudar_estado.php?id=1&estado=negociacao`
  - Resultado: `302`
  - Redirect: `/admin/leads/leads.php?msg=metodo_invalido`
- `POST /admin/mudar_estado.php`
  - Body: `id=1&estado=negociacao`
  - Resultado: `403`
  - Corpo: `CSRF inválido.`

Esses testes confirmam que GET direto nao altera estado e POST sem CSRF e bloqueado.

## Observacoes de seguranca

- A rota deixou de aceitar mutacao por URL.
- O parametro `estado` deixou de entrar diretamente no SQL.
- A whitelist reduz risco de valores inesperados em `clientes.estado`.
- O fluxo funcional existente foi preservado para POST valido.
- Esta tarefa nao corrigiu `app/modules/finance/marcar_pago.php`, conforme solicitado.
