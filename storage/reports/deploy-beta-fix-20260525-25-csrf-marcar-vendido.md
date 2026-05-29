# RG Auto Sales - Fix 25 - CSRF marcar_vendido

Data: 2026-05-25  
Fase: Deploy Beta Privado / hardening final  
Escopo: `app/modules/finance/marcar_vendido.php`

## Problema encontrado

A auditoria v5 identificou `app/modules/finance/marcar_vendido.php` como rota legado que criava venda real e marcava carro como vendido lendo `id` por GET.

Fluxo anterior:

```text
$id = (int)($_GET['id'] ?? 0);
...
INSERT INTO vendas (...)
...
UPDATE carros SET status='vendido' WHERE id=?
```

Impacto: criacao/marcacao de venda por GET, sem CSRF.

## Arquivo real identificado

Foram encontrados dois arquivos com o nome `marcar_vendido.php`:

- `app/modules/finance/marcar_vendido.php`
- `app/modules/sales/marcar_vendido.php`

O P0 da auditoria v5 era `app/modules/finance/marcar_vendido.php`, porque criava venda real por GET. A rota `app/modules/sales/marcar_vendido.php` ja usava POST e CSRF e nao foi alterada nesta tarefa.

Busca de chamadas:

```powershell
rg -n 'app/modules/finance/marcar_vendido\.php|finance/marcar_vendido\.php|marcar_vendido\.php\?' admin app views public -S
```

Resultado: nao foram encontradas chamadas ativas para a rota financeira legado, portanto nao havia link antigo para converter.

## Alteracao aplicada

- `app/modules/finance/marcar_vendido.php` agora bloqueia GET com redirect para:

```text
app/modules/cars/listar_carros.php?msg=metodo_invalido
```

- A rota exige POST.
- Valida `csrf_token` com `hash_equals()`.
- Valida `id` como inteiro positivo vindo de `POST`.
- Preserva integralmente a logica existente:
  - buscar carro;
  - usar preco do carro como `preco_venda` e `preco_compra`;
  - calcular `lucro`;
  - calcular `comissao_vendedor`, `comissao_parceiro` e `comissao_rg`;
  - inserir venda em `vendas` com status `PENDENTE`;
  - atualizar carro para `status='vendido'`;
  - redirecionar para `app/modules/cars/listar_carros.php?success=1`.

## Arquivos alterados

- `app/modules/finance/marcar_vendido.php`
- `storage/reports/deploy-beta-fix-20260525-25-csrf-marcar-vendido.md`

## Fora do escopo

Nao foram alterados:

- `app/modules/finance/pedir_saque.php`;
- outras rotas financeiras;
- CRM;
- carros/fotos;
- uploads;
- `app/modules/sales/marcar_vendido.php`.

## Validacoes feitas

### Lint individual

```text
C:\xampp\php\php.exe -l app/modules/finance/marcar_vendido.php
No syntax errors detected in app/modules/finance/marcar_vendido.php
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
Relatorio: C:\Users\rickg\OneDrive\Ambiente de Trabalho\RG_AUTO_SALES\storage\reports\php-lint-20260525-170953.txt
```

## Resultado HTTP local

Teste local com servidor PHP em `127.0.0.1:8795`, sessao admin sintetica e MariaDB temporario para permitir o bootstrap real.

### GET direto

```text
GET /app/modules/finance/marcar_vendido.php?id=1
HTTP/1.1 302 Found
Location: /app/modules/cars/listar_carros.php?msg=metodo_invalido
```

Conclusao: GET direto nao cria venda e nao marca carro como vendido.

### POST sem CSRF

```text
POST /app/modules/finance/marcar_vendido.php
Body: id=1
HTTP/1.1 403 Forbidden
CSRF invalido.
```

Conclusao: POST sem token CSRF valido e bloqueado antes da criacao/marcacao de venda.

## Observacoes de seguranca

- A rota continua protegida por `require_admin()`.
- A criacao de venda agora exige POST autenticado com CSRF valido.
- O `id` deixa de ser aceito via query string.
- Nenhum calculo de comissao, valor ou regra comercial foi alterado.
- Nao foram encontradas views/chamadas ativas para converter.

## Conclusao

O P0 em `app/modules/finance/marcar_vendido.php` foi corrigido. A rota nao aceita mais criacao/marcacao de venda por GET e bloqueia POST sem CSRF.
