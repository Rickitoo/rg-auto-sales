# Correcao Deploy Beta 19 - CSRF em remocao de fotos

Data: 2026-05-25  
Escopo: endpoints reais de remocao de fotos.  
Resultado: remocao de fotos protegida com POST + admin + CSRF.

## Problema encontrado

Ainda havia remocao de fotos sem CSRF completo:

- `admin/carros/carro_fotos_delete.php`
  - recebia JSON com `id`;
  - removia registro de `carros_fotos`;
  - atualizava capa do carro;
  - apagava arquivo fisico;
  - nao validava `csrf_token`.
- `app/modules/cars/carro_fotos_delete.php`
  - endpoint espelho chamado por `app/modules/cars/carro_fotos.php`;
  - tinha o mesmo padrao sem CSRF.
- `admin/apagar_foto.php`
  - removia foto via GET `id` e `carro_id`;
  - apagava arquivo fisico;
  - removia registro sem CSRF.

## Alteracao aplicada

- `carro_fotos_delete.php` agora:
  - exige `POST`;
  - bloqueia metodo diferente de POST com HTTP `405`;
  - valida `csrf_token` com `hash_equals()`;
  - valida `id` da foto como inteiro positivo antes de buscar/remover;
  - preserva a logica existente de delete, recalcule da capa e `unlink()`.
- `admin/apagar_foto.php` agora:
  - bloqueia GET destrutivo;
  - exige `POST`;
  - valida `csrf_token` com `hash_equals()`;
  - continua redirecionando apos a operacao quando POST valido;
  - redireciona GET direto para `admin/carros/listar_carros.php?msg=metodo_invalido`.
- Chamadores JS em:
  - `admin/carros/carro_fotos.php`
  - `app/modules/cars/carro_fotos.php`
  agora enviam `csrf_token` no JSON do `fetch`.

Nao houve alteracao em upload, layout global, vendas, leads/status, financeiro, cron ou regras comerciais.

## Arquivos alterados

- `admin/carros/carro_fotos_delete.php`
- `app/modules/cars/carro_fotos_delete.php`
- `admin/carros/carro_fotos.php`
- `app/modules/cars/carro_fotos.php`
- `admin/apagar_foto.php`
- `storage/reports/deploy-beta-fix-20260525-19-csrf-delete-fotos.md`

## Chamadas verificadas

Busca:

```powershell
rg -n "apagar_foto\.php\?|carro_fotos_delete\.php" admin app public -S
```

Resultado:

- nao ha links GET ativos para `admin/apagar_foto.php`;
- chamadas restantes para `carro_fotos_delete.php` sao `fetch()` POST em:
  - `admin/carros/carro_fotos.php`;
  - `app/modules/cars/carro_fotos.php`.

## Validacoes feitas

### Lint PHP individual

Arquivos validados:

- `admin/carros/carro_fotos_delete.php`
- `app/modules/cars/carro_fotos_delete.php`
- `admin/carros/carro_fotos.php`
- `app/modules/cars/carro_fotos.php`
- `admin/apagar_foto.php`

Resultado: sem erros de sintaxe.

### Lint PHP global

Comando:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1
```

Resultado:

- Arquivos OK: 199
- Arquivos com erro: 0
- Relatorio: `storage/reports/php-lint-20260525-124157.txt`

## Resultado HTTP local

Teste executado com servidor PHP temporario e MySQL local do XAMPP.

### Sem sessao

- `GET /admin/carros/carro_fotos_delete.php?id=1`
  - Resultado: `302`
  - Redirect: `/auth/login.php?...`
- `POST /admin/carros/carro_fotos_delete.php`
  - Body JSON: `{"id":1}`
  - Resultado: `302`
  - Redirect: `/auth/login.php?...`
- `GET /admin/apagar_foto.php?id=1&carro_id=1`
  - Resultado: `302`
  - Redirect: `/auth/login.php?...`
- `POST /admin/apagar_foto.php`
  - Body: `id=1&carro_id=1`
  - Resultado: `302`
  - Redirect: `/auth/login.php?...`

### Com sessao admin sintetica, sem CSRF

- `GET /admin/carros/carro_fotos_delete.php?id=1`
  - Resultado: `405`
  - Corpo JSON: `{"ok":false,"error":"Metodo invalido."}`
- `POST /admin/carros/carro_fotos_delete.php`
  - Body JSON: `{"id":1}`
  - Resultado: `403`
  - Corpo JSON: `{"ok":false,"error":"CSRF invalido."}`
- `GET /admin/apagar_foto.php?id=1&carro_id=1`
  - Resultado: `302`
  - Redirect: `/admin/carros/listar_carros.php?msg=metodo_invalido`
- `POST /admin/apagar_foto.php`
  - Body: `id=1&carro_id=1`
  - Resultado: `403`
  - Corpo: `CSRF inválido.`

Esses testes confirmam que GET direto nao executa remocao e POST sem CSRF nao remove fotos.

## Observacoes de seguranca

- Remocao de fotos agora depende de sessao admin e token CSRF valido.
- O endpoint JSON rejeita GET com `405` antes de qualquer query destrutiva.
- O endpoint legado `admin/apagar_foto.php` nao aceita mais remocao via URL.
- A logica existente de galeria, capa e remocao fisica de arquivo foi preservada.
- Esta correcao nao tratou `mudar_estado.php` nem `marcar_pago.php`, conforme solicitado.
