# Correcao Deploy Beta 18 - Upload seguro de fotos

Data: 2026-05-25  
Escopo: uploads reais de fotos/imagens no sistema RG Auto Sales.  
Resultado: uploads de fotos protegidos com validacao de extensao, MIME real, imagem real, tamanho, nome aleatorio e hardening de diretorio.

## Problema encontrado

A auditoria encontrou rotas reais de upload com validacao incompleta:

- alguns fluxos validavam apenas extensao;
- alguns validavam MIME, mas nao validavam imagem real com `getimagesize()`;
- alguns geravam nomes parcialmente derivados do nome original;
- pastas de upload nao tinham hardening uniforme contra execucao PHP/listagem;
- havia duplicacao de regras entre `admin/carros` e `app/modules/cars`.

Rotas reais de upload encontradas:

- `admin/carros/carro_save.php`
- `app/modules/cars/carro_save.php`
- `admin/carros/carro_fotos.php`
- `app/modules/cars/carro_fotos.php`
- `admin/carros/editar_carro.php`
- `app/modules/cars/editar_carro.php`
- `admin/gerir_fotos.php`
- `app/modules/cars/actions/vender_carro.php`

Nao foram encontradas rotas reais de upload de fotos de leads/clientes fora destes fluxos de carros/venda de carro.

## Alteracao aplicada

Foi criado o helper central:

- `app/core/helpers/upload_security.php`

O bootstrap agora carrega esse helper em:

- `app/core/bootstrap.php`

O helper `secure_uploaded_image()` aplica:

- extensoes permitidas: `jpg`, `jpeg`, `png`, `webp`;
- bloqueio de extensoes perigosas, incluindo `php`, `phtml`, `phar`, `php3`, `php4`, `php5`, `htaccess`;
- validacao de MIME real com `finfo_file()`;
- validacao de imagem real com `getimagesize()`;
- limite de tamanho definido pelo fluxo chamador;
- rejeicao de nomes com path traversal;
- nome final aleatorio com `bin2hex(random_bytes(16))`;
- checagem de destino dentro da pasta de upload;
- gravacao de `.htaccess` na pasta de upload usada, quando ausente.

Pastas publicas existentes tambem receberam hardening:

- `public/uploads/.htaccess`
- `public/assets/uploads/.htaccess`
- `public/assets/uploads/vendas/carros/.htaccess`

Conteudo aplicado:

```apache
php_flag engine off
Options -Indexes
<FilesMatch "\.(php|phtml|php3|php4|php5|phar|htaccess)$">
    Deny from all
</FilesMatch>
```

## Arquivos alterados

- `app/core/bootstrap.php`
- `app/core/helpers/upload_security.php`
- `admin/carros/carro_save.php`
- `app/modules/cars/carro_save.php`
- `admin/carros/carro_fotos.php`
- `app/modules/cars/carro_fotos.php`
- `admin/carros/editar_carro.php`
- `app/modules/cars/editar_carro.php`
- `admin/gerir_fotos.php`
- `app/modules/cars/actions/vender_carro.php`
- `public/uploads/.htaccess`
- `public/assets/uploads/.htaccess`
- `public/assets/uploads/vendas/carros/.htaccess`

Nao foram alterados fluxos de vendas/status de leads/cron nesta tarefa.

## Logica preservada

- Galerias continuam gravando nos mesmos campos/tabelas existentes.
- Capas de carro continuam sendo atualizadas pelos mesmos fluxos.
- Limites por fluxo foram preservados:
  - 3MB nos fluxos de cadastro/galeria principal;
  - 8MB nos fluxos legados de `editar_carro.php` e `gerir_fotos.php`;
  - maximo de 8 fotos em `vender_carro.php`;
  - maximo de 15 fotos na galeria de novo carro.
- Nao houve alteracao de layout global.
- Nao houve alteracao de regra comercial.

## Validacoes feitas

### Busca de handlers

Buscas executadas:

```powershell
rg -n "move_uploaded_file" admin app public -S
rg -n "FILES" admin app public -S
rg -n "multipart/form-data" admin app public -S
rg -n "uploads" admin app public -S
```

Resultado final:

- `move_uploaded_file()` ficou concentrado apenas em `app/core/helpers/upload_security.php`.
- Rotas reais de upload foram migradas para `secure_uploaded_image()`.

### Lint PHP individual

Arquivos validados com `C:\xampp\php\php.exe -l`:

- `app/core/bootstrap.php`
- `app/core/helpers/upload_security.php`
- `admin/carros/carro_save.php`
- `app/modules/cars/carro_save.php`
- `admin/carros/carro_fotos.php`
- `app/modules/cars/carro_fotos.php`
- `admin/carros/editar_carro.php`
- `app/modules/cars/editar_carro.php`
- `admin/gerir_fotos.php`
- `app/modules/cars/actions/vender_carro.php`

Resultado: sem erros de sintaxe.

### Lint PHP global

Comando:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1
```

Resultado:

- Arquivos OK: 199
- Arquivos com erro: 0
- Relatorio: `storage/reports/php-lint-20260525-122909.txt`

## Testes de upload

Teste multipart real executado com servidor PHP temporario e endpoint de teste descartavel em `storage`.

### Upload valido

Arquivo: PNG 1x1 valido.  
Resultado:

```text
HTTP/1.1 200 OK
OK test-5fc5e2ef3f43b2c431d7dcd326b2a34e.png
```

Confirmado:

- arquivo aceito;
- nome final aleatorio;
- extensao segura;
- `.htaccess` criado na pasta alvo.

### Upload invalido

Arquivo: `evil.php` enviado via multipart.  
Resultado:

```text
HTTP/1.1 400 Bad Request
ERR Formato nao permitido. Usa JPG/PNG/WEBP.
```

Confirmado:

- extensao perigosa bloqueada;
- arquivo nao salvo como imagem;
- validacao ocorre antes de mover o arquivo.

Os arquivos temporarios do teste foram removidos apos a validacao.

## Observacoes de seguranca

- Uploads agora dependem de validacao em camadas: extensao permitida, MIME real e imagem real.
- Arquivos executaveis PHP e variantes perigosas sao bloqueados antes do `move_uploaded_file()`.
- O nome final nao reutiliza o nome enviado pelo usuario.
- A checagem de destino reduz risco de path traversal.
- As pastas de upload recebem protecao contra execucao PHP e listagem.
- Nenhuma feature nova foi adicionada.
- Nenhum fluxo de vendas, leads/status ou cron foi alterado nesta tarefa.
