# RG Auto Sales - Fix 21 - CSRF marcar_pago legado

Data: 2026-05-25  
Fase: Deploy Beta Privado / hardening final  
Escopo: `app/modules/finance/marcar_pago.php` e chamada legado em `app/modules/finance/financeiro.php`

## Problema encontrado

A rota legado `app/modules/finance/marcar_pago.php` aceitava `id` por `GET` ou `POST` e a tela financeira chamava a rota por link:

```php
marcar_pago.php?id=...
```

Embora o `UPDATE` financeiro já usasse prepared statement, o fluxo permitia entrada por GET para uma operação sensível e o POST final não validava CSRF.

## Alteracao aplicada

- `app/modules/finance/marcar_pago.php` agora exige `POST`.
- Requisicoes `GET` sao redirecionadas para `app/modules/finance/financeiro.php?msg=metodo_invalido`.
- A rota valida `csrf_token` com `hash_equals()` antes de processar qualquer pagamento.
- O identificador da venda e aceito apenas de `POST` (`id` ou `venda_id`) e validado como inteiro positivo.
- A confirmacao de pagamento continua usando o formulario existente, agora com `csrf_input()` e flag interna `confirmar_pagamento`.
- O bloco financeiro existente foi preservado:
  - busca da venda por prepared statement;
  - validacao de `status === "PAGO"`;
  - validacao de `pode_pagar`;
  - `UPDATE vendas SET status='PAGO', forma_pagamento=?, data_pagamento=NOW() WHERE id=? LIMIT 1`.
- A chamada antiga em `app/modules/finance/financeiro.php` foi convertida de link GET para formulario POST com `csrf_input()`.

## Arquivos alterados

- `app/modules/finance/marcar_pago.php`
- `app/modules/finance/financeiro.php`
- `storage/reports/deploy-beta-fix-20260525-21-csrf-marcar-pago-legado.md`

## Validacoes feitas

### Busca de chamadas antigas

Comando:

```powershell
rg -n 'marcar_pago\.php|marcar_pago\.php\?' app admin views -S
```

Resultado:

```text
app\modules\finance\financeiro.php:94:<form method="POST" action="marcar_pago.php" style="display:inline;">
```

Nao restou chamada `marcar_pago.php?id=...`.

### Lint individual

```text
C:\xampp\php\php.exe -l app/modules/finance/marcar_pago.php
No syntax errors detected in app/modules/finance/marcar_pago.php

C:\xampp\php\php.exe -l app/modules/finance/financeiro.php
No syntax errors detected in app/modules/finance/financeiro.php
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
Relatorio: C:\Users\rickg\OneDrive\Ambiente de Trabalho\RG_AUTO_SALES\storage\reports\php-lint-20260525-130036.txt
```

## Resultado HTTP

Teste local com servidor PHP em `127.0.0.1:8790` e sessao admin sintetica.

### GET direto

Requisicao:

```text
GET /app/modules/finance/marcar_pago.php?id=1
```

Resultado:

```text
HTTP/1.1 302 Found
Location: /app/modules/finance/financeiro.php?msg=metodo_invalido
```

Conclusao: GET direto nao executa pagamento.

### POST sem CSRF

Requisicao:

```text
POST /app/modules/finance/marcar_pago.php
Body: id=1&forma_pagamento=Cash&confirmar_pagamento=1
```

Resultado:

```text
HTTP/1.1 403 Forbidden
CSRF inválido.
```

Conclusao: POST sem CSRF valido e bloqueado antes do processamento financeiro.

## Observacoes de seguranca

- A correcao remove o vetor P0 de alteracao financeira sem CSRF na rota legado.
- A rota permanece protegida por `require_admin()` e pela checagem de role admin existente.
- Nao foram alterados calculos, queries financeiras de negocio, regras de elegibilidade (`pode_pagar`) ou mensagens comerciais.
- Nao houve alteracao em cron, uploads, fotos, leads/status, `mudar_estado.php`, `vendas.php` ou `detalhe_venda.php`.

## Conclusao

O P0 de CSRF/GET destrutivo em `app/modules/finance/marcar_pago.php` foi corrigido. A rota agora exige POST autenticado com CSRF valido e mantem intacta a regra financeira existente.
