# Pós-Beta: melhorias prioritárias

Data: 2026-06-05 00:04

## Escopo aplicado

- Removido `ini_set('display_errors', 1)` e `error_reporting(E_ALL)` das rotas públicas/admin encontradas na varredura.
- Criado helper reutilizável `require_post_csrf()` em `app/core/helpers/functions.php`.
- Aplicado `require_post_csrf()` em rotas de ação simples já protegidas por admin/auth, mantendo compatibilidade com os nomes de token existentes.
- Mantido o helper de upload existente `secure_uploaded_image()`, que valida MIME real, extensão permitida, tamanho máximo, nome aleatório e cria `.htaccess` para bloquear execução PHP/listagem.
- Melhorada responsividade mobile do website via override pequeno em `public/assets/css/style.css`: logo menor, menu hamburger mais claro, grid 2 por linha quando há largura suficiente, rodapé mais organizado.
- Substituído emoji visual solto em `public/confirmacao.php` por ícone Font Awesome.

## Checklist de rotas sensíveis

Critério: rotas com `INSERT`, `UPDATE`, `DELETE`, upload ou envio externo devem ter auth quando aplicável, método POST para mutação e CSRF para sessão de usuário.

| Área | Rotas revisadas | Estado |
| --- | --- | --- |
| Admin carros/upload | `admin/carros/*`, `admin/gerir_fotos.php`, `app/modules/cars/*` | Uploads usam `secure_uploaded_image()`. Rotas de fotos/order/delete já têm POST/JSON + CSRF. |
| Leads/CRM | `admin/leads/*`, `app/modules/leads/*`, `views/api/move_stage.php` | Já havia validações CSRF em rotas de mutação. Não alterado para evitar mexer em CRM nesta fase. |
| Vendas/financeiro | `admin/vendas/*`, `app/modules/finance/*`, `app/modules/sales/marcar_vendido.php` | Debug removido onde existia. `marcar_vendido.php` agora chama `require_post_csrf('token')`. Demais rotas mantidas por escopo. |
| Público formulários | `public/Formulario_cliente.php`, `public/salvar_testdrive.php`, `public/importar_carro.php`, `public/vender_carro.php` | São pontos públicos de captação; sem login por desenho. Recomenda-se CSRF público/honeypot/rate limit em rodada própria. |
| Envio externo | `admin/whatsapp/send.php`, `admin/send_message.php`, `app/modules/cars/actions/vender_carro.php` | Envio/admin já passa por auth. `vender_carro.php` agora usa helper central para POST + CSRF. |
| Webhooks | `admin/whatsapp/webhook.php`, `admin/webhook_receiver.php` | Ambos exigem `require_admin()`, então não funcionam como webhook público real. Se forem expostos a provedores externos, exigir segredo/assinatura antes de remover auth. |

## Uploads

Arquivos com upload encontrados:

- `admin/carros/editar_carro.php`
- `admin/carros/carro_save.php`
- `admin/carros/carro_fotos.php`
- `admin/gerir_fotos.php`
- `app/modules/cars/editar_carro.php`
- `app/modules/cars/carro_save.php`
- `app/modules/cars/carro_fotos.php`
- `app/modules/cars/actions/vender_carro.php`

Todos chamam `secure_uploaded_image()`. O helper valida `jpg/jpeg/png/webp`, MIME via `finfo`, `getimagesize()`, tamanho configurado por rota, nome aleatório com `random_bytes()` e grava `.htaccess` defensivo na pasta de upload.

## Responsividade

- Logo reduzido no mobile para 58px.
- Hamburger virou botão circular com área de toque estável.
- Menu mobile abre abaixo da navbar com altura suficiente para todos os links.
- Cards em `.small-container` ficam 2 por linha no mobile médio e 1 por linha abaixo de 420px.
- Rodapé no mobile organiza colunas em 2 por linha quando couber.

## Pendências conscientes

- Existem emojis em dashboards/admin/CRM/vendas/financeiro. Não foram trocados para respeitar a restrição de não alterar CRM, vendas, importação ou financeiro nesta fase.
- Webhooks continuam protegidos por autenticação admin. Caso sejam webhooks reais externos, a próxima mudança deve implementar validação por segredo/assinatura do provedor.
- Rotas públicas de captação podem receber uma rodada específica de anti-spam/CSRF público sem mudar regras de negócio.

## Validação

- `powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1`
- Resultado: 203 arquivos OK, 0 erros.
- Relatório de lint: `storage/reports/php-lint-20260605-000436.txt`
