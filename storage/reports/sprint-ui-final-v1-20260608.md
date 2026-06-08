# Sprint UI Final v1 - RG Auto Sales

Data: 2026-06-08

## Objetivo
Melhorar a aparencia final do site publico com footer reutilizavel e carrossel na pagina inicial, sem alterar backend, CRM, vendas, financeiro, seguranca ou regras de negocio.

## Arquivos alterados
- `includes/footer_public.php`
- `public/index.php`
- `public/assets/css/style.css`
- `public/assets/js/main.js`
- `storage/reports/sprint-ui-final-v1-20260608.md`

## Protecoes aplicadas / preservadas
- Criado footer publico reutilizavel com logo, descricao, links rapidos, contactos, redes sociais e copyright dinamico.
- Substituido o footer duplicado da pagina inicial por `require_once __DIR__ . '/../includes/footer_public.php';`.
- Criado carrossel na pagina inicial com 3 banners, botoes claros, indicadores, setas e autoplay suave.
- Adicionados estilos responsivos para carrossel e footer com `max-width: 100%`, `overflow: hidden` nos componentes novos e botoes confortaveis no mobile.
- Mantidas as protecoes existentes de CSRF, honeypot e rate limit. Nenhum formulario foi alterado.
- Nao foram alteradas queries SQL, inserts, CRM, vendas, financeiro, admin ou regras de negocio.

## Nota de escopo
O escopo permitido desta sprint restringiu edicoes a `includes/footer_public.php`, `public/index.php`, `public/assets/css/style.css` e `public/assets/js/main.js`. Por isso, a substituicao de footer duplicado foi feita na pagina inicial, que estava dentro do escopo. Outras paginas publicas nao foram editadas nesta rodada para nao violar a restricao de arquivos permitidos.

## Lint PHP global
Comando executado:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1
```

Resultado final:
- PHP CLI: `C:\xampp\php\php.exe`
- Arquivos OK: 205
- Arquivos com erro: 0
- Relatorio: `storage/reports/php-lint-20260608-225541.txt`

## Testes manuais / validacao
- Servidor PHP local iniciado temporariamente para validar `public/index.php`.
- HTTP local validado com status `200`.
- Confirmado no HTML renderizado:
  - `data-home-carousel` presente.
  - `.public-footer` presente.
  - `assets/js/main.js` carregado.
  - Footer antigo da home (`Download do App`) removido.
- Capturas headless geradas com sucesso em 390px e 430px usando Edge.
- 768px foi validado parcialmente por tentativa headless e revisao estrutural; a captura limpa travou no Edge headless por comportamento do navegador local, nao por erro PHP.
- Desktop validado por resposta HTTP 200 e presenca dos componentes novos no HTML.
- Menu/carrossel receberam fallback visual em CSS para continuar legiveis mesmo se os icones externos nao carregarem.

## Observacoes
- Nao foi criado novo fluxo ou funcionalidade de negocio.
- O JavaScript adicionado limita-se ao menu publico existente e ao carrossel da pagina inicial.
- Os assets externos de fontes/icones podem afetar capturas headless offline, mas os componentes principais ficam operacionais com fallback CSS.
