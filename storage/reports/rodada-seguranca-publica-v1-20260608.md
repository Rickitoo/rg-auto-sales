# Rodada Seguranca Publica v1 - RG Auto Sales

Data: 2026-06-08

## Escopo executado

- Identificados formularios publicos em `/public` e endpoints publicos relacionados em `/auth`.
- Adicionada protecao reutilizavel em `app/core/helpers/security.php`.
- Mantidas regras de negocio, CRM, vendas, importacao, financeiro e layout geral.

## Arquivos alterados nesta rodada

- `app/core/helpers/security.php`
- `auth/login.php`
- `auth/processa_leasing.php`
- `auth/processa_login.php`
- `auth/processa_registo.php`
- `public/Formulario_cliente.php`
- `public/account.php`
- `public/contacto.php`
- `public/importar_carro.php`
- `public/leasing.php`
- `public/salvar_testdrive.php`
- `public/test_drive.php`
- `public/vender_carro.php`
- `public/assets/uploads/vendas/.htaccess`
- `public/assets/uploads/vendas/carros/carros/.htaccess`

## Protecoes aplicadas

- CSRF publico usando `csrf_input()`, `csrf_token()` e `require_post_csrf()` ja existentes.
- Honeypot invisivel reutilizavel com campo `website`.
- Rate limit simples por IP/sessao nos handlers publicos.
- Validacao basica de telefone nos formularios publicos que gravam leads/pedidos.
- Validacao basica de email opcional/obrigatorio conforme o formulario existente.
- Login e registo retornam erros JSON para preservar o fluxo atual via `fetch`.

## Formularios publicos revisados

- `public/test_drive.php` -> `public/Formulario_cliente.php`
- `public/importar_carro.php`
- `public/vender_carro.php`
- `public/account.php` -> `auth/processa_login.php` e `auth/processa_registo.php`
- `public/leasing.php` -> `auth/processa_leasing.php`
- `public/contacto.php` tem formulario visual sem handler real de gravacao; recebeu campos ocultos por consistencia.
- `public/salvar_testdrive.php` parece handler legado sem chamada atual encontrada; foi protegido para POST com CSRF/honeypot/rate-limit.

## Uploads publicos

- Confirmado que `public/uploads/.htaccess`, `public/assets/uploads/.htaccess` e `public/assets/uploads/vendas/carros/.htaccess` ja bloqueavam execucao/listagem.
- Adicionado o mesmo bloqueio em:
  - `public/assets/uploads/vendas/.htaccess`
  - `public/assets/uploads/vendas/carros/carros/.htaccess`
- Regra aplicada: `php_flag engine off`, `Options -Indexes` e bloqueio de `php`, `phtml`, `php3`, `php4`, `php5`, `phar`, `htaccess`.

## Webhooks

- Verificados:
  - `admin/webhook_receiver.php`
  - `admin/whatsapp/webhook.php`
- Ambos continuam internos com `require_admin()`.
- Nenhum `require_admin()` foi removido.
- Nao foi encontrado webhook externo real sem autenticacao para proteger com segredo/assinatura nesta rodada.

## Lint PHP

- Comando: `powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1`
- Resultado: 204 arquivos OK, 0 erros.
- Relatorio gerado: `storage/reports/php-lint-20260608-201947.txt`

## Testes manuais/CLI feitos

- POST sem CSRF para `public/Formulario_cliente.php`: bloqueado com `CSRF invalido`.
- POST sem CSRF para `public/importar_carro.php`: bloqueado com `CSRF invalido`.
- POST sem CSRF para `auth/processa_login.php`: retorna JSON com sessao expirada/CSRF invalido.
- Honeypot preenchido com CSRF valido em `public/Formulario_cliente.php`: bloqueado com `Pedido invalido.`
- Honeypot preenchido com CSRF valido em `auth/processa_login.php`: retorna JSON `Pedido invalido.`
- Rate limit do helper validado: 10 tentativas permitidas e 11a tentativa bloqueada.
- Tentativa de teste via servidor PHP embutido foi abortada porque o ambiente nao manteve o servidor local acessivel; nao havia processo ouvindo na porta de teste ao final.

## Observacoes

- Ja existiam alteracoes locais fora desta rodada em arquivos admin, app, CSS e paginas publicas; nao foram revertidas.
- A protecao foi centralizada em helper para evitar duplicacao de logica.
