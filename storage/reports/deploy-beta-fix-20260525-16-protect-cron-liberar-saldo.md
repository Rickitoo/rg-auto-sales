# Correcao Deploy Beta 16 - Proteger cron_liberar_saldo.php

Data: 2026-05-25  
Arquivo principal: `admin/cron_liberar_saldo.php`  
Bloqueador tratado: execucao web autenticada de rotina financeira em lote.

## Problema encontrado

`admin/cron_liberar_saldo.php` podia ser executado como rota web apos `require_admin()`.

A rota contem mutacoes financeiras em lote:

- move `wallet.saldo_pendente` para `wallet.saldo_disponivel`;
- marca vendas `PAGO` como `processado=1`.

Isso criava risco P0 porque uma requisicao via navegador poderia disparar a rotina fora do contexto operacional esperado.

## Alteracao aplicada

Foi adicionado um bloqueio CLI-only no inicio de `admin/cron_liberar_saldo.php`, antes do bootstrap e antes de qualquer query:

```php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado.');
}
```

A logica financeira existente foi mantida intacta. Nao houve alteracao em calculos, queries, regras financeiras, seletores, updates ou fluxo de processamento.

## Arquivos alterados

- `admin/cron_liberar_saldo.php`
- `storage/reports/deploy-beta-fix-20260525-16-protect-cron-liberar-saldo.md`

## Validacoes feitas

### Revisao previa

Antes da alteracao, o conteudo atual de `admin/cron_liberar_saldo.php` foi verificado. O arquivo executava `require_admin()` e seguia diretamente para as consultas de vendas pagas, atualizacao de wallet e marcacao de vendas como processadas.

### Lint PHP individual

Comando:

```powershell
C:\xampp\php\php.exe -l admin\cron_liberar_saldo.php
```

Resultado:

```text
No syntax errors detected in admin\cron_liberar_saldo.php
```

### Lint PHP global

Comando:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1
```

Resultado:

- Arquivos OK: 198
- Arquivos com erro: 0
- Relatorio: `storage/reports/php-lint-20260525-113654.txt`

## Resultado HTTP

Teste local com servidor PHP temporario apontado para o workspace:

```text
GET http://127.0.0.1:8765/admin/cron_liberar_saldo.php
```

Resultado:

```text
STATUS=403
BODY=Acesso negado.
```

O bloqueio acontece antes de carregar o bootstrap, antes de `require_admin()` e antes de qualquer query financeira.

## Resultado CLI

O arquivo continua permitido para execucao via CLI porque o bloqueio so encerra quando `php_sapi_name() !== 'cli'`.

A execucao completa do cron via CLI nao foi disparada durante a validacao para evitar movimentar saldos ou marcar vendas como processadas em ambiente de trabalho. A verificacao aplicada foi sintatica e estrutural: em CLI, a condicao nao bloqueia; em HTTP, a rota retorna 403.

## Observacoes de seguranca

- A superficie web direta do cron financeiro foi removida.
- A rotina financeira permanece disponivel para execucao operacional via CLI.
- Nenhuma feature nova foi adicionada.
- Nenhum fluxo de leads/status, uploads ou fotos foi alterado nesta correcao.
- A regra de negocio existente de liberacao de saldo foi preservada sem mudancas.
