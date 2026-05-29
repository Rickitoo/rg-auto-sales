# RG Auto Sales - Fix 22 - CSRF carros/fotos residual

Data: 2026-05-25  
Fase: Deploy Beta Privado / hardening final  
Escopo: P0 residuais de carros/fotos encontrados na auditoria v5

## Problema encontrado

A auditoria v5 encontrou acoes destrutivas de carros/fotos executaveis por GET:

- apagar carro em `admin/carros/apagar_carro.php`;
- apagar carro em `app/modules/cars/apagar_carro.php`;
- mover/ordenar foto em `admin/mover_foto.php`.

As listagens ainda chamavam `apagar_carro.php?id=...&csrf_token=...` via link GET. A rota `admin/mover_foto.php` nao teve chamada ativa encontrada, mas existia e alterava a ordem de fotos via GET.

## Alteracao aplicada

- `admin/carros/apagar_carro.php`
  - bloqueia GET com redirect para `admin/carros/listar_carros.php?msg=metodo_invalido`;
  - exige POST;
  - valida `csrf_token` com `hash_equals()`;
  - valida `id` como inteiro positivo;
  - preserva a logica existente de remover ficheiros, apagar `carros_fotos`, apagar `carros` e redirecionar para a listagem.

- `app/modules/cars/apagar_carro.php`
  - bloqueia GET com redirect para `app/modules/cars/listar_carros.php?msg=metodo_invalido`;
  - exige POST;
  - valida `csrf_token` com `hash_equals()`;
  - valida `id` como inteiro positivo;
  - preserva a logica existente de remocao e redirect.

- `admin/mover_foto.php`
  - bloqueia GET com redirect para `admin/carros/listar_carros.php?msg=metodo_invalido`;
  - exige POST;
  - valida `csrf_token` com `hash_equals()`;
  - valida `id` e `carro_id` como inteiros positivos;
  - valida `dir` por whitelist `up/down`;
  - preserva a logica existente de troca de ordem e redirect.

- Chamadas convertidas de link GET para formulario POST:
  - `app/views/admin/carros/listar_carros_content.php`;
  - `app/modules/cars/listar_carros.php`.

## Arquivos alterados

- `admin/carros/apagar_carro.php`
- `app/modules/cars/apagar_carro.php`
- `admin/mover_foto.php`
- `app/views/admin/carros/listar_carros_content.php`
- `app/modules/cars/listar_carros.php`
- `storage/reports/deploy-beta-fix-20260525-22-csrf-carros-fotos-residual.md`

## Fora do escopo

Nao foram alterados:

- financeiro;
- follow-up;
- status/update legado;
- `app/modules/finance/marcar_vendido.php`;
- regras comerciais de venda/financeiro.

## Buscas realizadas

```powershell
rg -n "apagar_carro\.php|mover_foto\.php" admin app views public -S
```

Resultado inicial:

```text
app\views\admin\carros\listar_carros_content.php:115: link GET para admin/carros/apagar_carro.php
app\modules\cars\listar_carros.php:337: link GET para apagar_carro.php
admin\carros\apagar_carro.php
app\modules\cars\apagar_carro.php
```

Busca final:

```powershell
rg -n 'apagar_carro\.php\?|mover_foto\.php\?|href=.*apagar_carro\.php|href=.*mover_foto\.php' admin app views public -S
```

Resultado: sem ocorrencias.

## Validacoes feitas

### Lint individual

```text
C:\xampp\php\php.exe -l admin/carros/apagar_carro.php
No syntax errors detected in admin/carros/apagar_carro.php

C:\xampp\php\php.exe -l app/modules/cars/apagar_carro.php
No syntax errors detected in app/modules/cars/apagar_carro.php

C:\xampp\php\php.exe -l admin/mover_foto.php
No syntax errors detected in admin/mover_foto.php

C:\xampp\php\php.exe -l app/views/admin/carros/listar_carros_content.php
No syntax errors detected in app/views/admin/carros/listar_carros_content.php

C:\xampp\php\php.exe -l app/modules/cars/listar_carros.php
No syntax errors detected in app/modules/cars/listar_carros.php
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
Relatorio: C:\Users\rickg\OneDrive\Ambiente de Trabalho\RG_AUTO_SALES\storage\reports\php-lint-20260525-132250.txt
```

## Resultado HTTP local

Teste local com servidor PHP em `127.0.0.1:8791`, MySQL local e sessao admin sintetica.

### GET direto

```text
GET /admin/carros/apagar_carro.php?id=1
HTTP/1.1 302 Found
Location: /admin/carros/listar_carros.php?msg=metodo_invalido

GET /app/modules/cars/apagar_carro.php?id=1
HTTP/1.1 302 Found
Location: /app/modules/cars/listar_carros.php?msg=metodo_invalido

GET /admin/mover_foto.php?id=1&dir=up&carro_id=1
HTTP/1.1 302 Found
Location: /admin/carros/listar_carros.php?msg=metodo_invalido
```

Conclusao: GET direto nao executa acoes destrutivas.

### POST sem CSRF

```text
POST /admin/carros/apagar_carro.php
HTTP/1.1 403 Forbidden

POST /app/modules/cars/apagar_carro.php
HTTP/1.1 403 Forbidden

POST /admin/mover_foto.php
HTTP/1.1 403 Forbidden
```

Conclusao: POST sem token CSRF valido e bloqueado antes da acao destrutiva.

## Observacoes de seguranca

- O token CSRF deixou de trafegar em query string nas chamadas de apagar carro corrigidas.
- As rotas continuam protegidas por `require_admin()`.
- A validacao de IDs ocorre antes das queries destrutivas.
- A rota `admin/mover_foto.php` permanece funcional por POST caso alguma tela volte a usa-la, mas nao foi encontrada chamada ativa atual.
- As rotas mais modernas de ordenacao por AJAX/POST nao foram alteradas.

## Conclusao

Os P0 residuais de carros/fotos cobertos neste bloco foram corrigidos: apagar carro e mover foto nao aceitam mais execucao destrutiva por GET e agora exigem POST autenticado com CSRF valido.
