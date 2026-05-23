# RG_AUTO_SALES Legacy Wrapper Report

Generated: 2026-05-23

## Resultado

As funcoes reais que estavam em `includes/` foram migradas para locais oficiais:

- `clean()`, `redirect()`, `is_post()` -> `app/core/helpers/forms.php`
- `fotoCarroUrl()`, `getCarros()`, `getCarroById()` -> `app/modules/cars/helpers.php`
- `calcularComissoes()`, `rg_get_config()`, `rg_calc_comissao()`, `rg_split_comissao()` -> `app/modules/sales/commissions.php`
- `r2()`, `recalcular_venda()` -> `app/modules/finance/helpers.php`

O `app/core/bootstrap.php` agora carrega os novos arquivos oficiais diretamente.

## Classificacao

| Arquivo legado | Onde ainda e usado | Funcao atual | Recomendacao |
| --- | --- | --- | --- |
| `init.php` | Sem referencias por `require/include`; pode ser acessado diretamente por URL antiga | Wrapper para `app/core/bootstrap.php` | Manter temporariamente; remover depois de validar que nao ha links/bookmarks externos |
| `auth_check.php` | Sem referencias por `require/include`; pode ser acessado diretamente por URL antiga | Wrapper para `bootstrap.php` + `require_login()` | Manter temporariamente; remover quando rotas antigas forem descontinuadas |
| `app/core/init.php` | Sem referencias diretas encontradas | Wrapper interno para `app/core/bootstrap.php` | Migrar chamadas futuras para `bootstrap.php`; remover em limpeza posterior |
| `admin/admin_check.php` | Sem referencias diretas encontradas | Wrapper admin para `bootstrap.php` + `require_admin()` | Manter temporariamente; remover depois de revisar acessos antigos |
| `admin/includes/db.php` | Sem referencias antigas encontradas | Wrapper admin para `bootstrap.php` + `require_admin()` | Remover em etapa posterior; DB oficial e `app/core/database.php` |
| `admin/includes/funcoes_carros.php` | Sem referencias antigas encontradas | Wrapper admin para `bootstrap.php` + `require_admin()` | Remover em etapa posterior; funcoes oficiais estao em `app/modules/cars/helpers.php` |
| `admin/includes/header_public.php` | Usado apenas como compatibilidade para header publico admin antigo | Wrapper admin para `includes/header_public.php` | Manter enquanto existirem telas antigas em admin que chamam este header |
| `includes/helpers.php` | Sem referencias antigas encontradas | Wrapper para `bootstrap.php` | Remover depois que chamadas externas antigas forem descartadas |
| `includes/funcoes_carros.php` | Sem referencias antigas encontradas | Wrapper para `bootstrap.php` | Remover depois que chamadas externas antigas forem descartadas |
| `includes/funcoes_vendas.php` | Sem referencias antigas encontradas | Wrapper para `bootstrap.php` | Remover depois que chamadas externas antigas forem descartadas |
| `includes/financeiro.php` | Sem referencias antigas encontradas | Wrapper para `bootstrap.php` | Remover depois que chamadas externas antigas forem descartadas |
| `includes/config.php` | Sem referencias antigas encontradas | Wrapper para `bootstrap.php` | Remover depois que chamadas externas antigas forem descartadas |
| `includes/auth.php`, `includes/auth_user.php`, `includes/auth_admin.php` | Sem referencias antigas encontradas | Wrappers para login/admin | Manter temporariamente; substituir por `require_login()` / `require_admin()` em novas paginas |

## Modulos duplicados pendentes

Ha espelhos de funcionalidades entre `admin/*` e `app/modules/*`, principalmente:

- `admin/carros/*` e `app/modules/cars/*`
- `admin/leads/*` e `app/modules/leads/*`
- `admin/vendas/*` e `app/modules/sales/*`
- `admin/financeiro/*` e `app/modules/finance/*`

Recomendacao: nao remover esses duplicados ainda. O proximo passo seguro e escolher uma rota canonica por modulo, confirmar quais telas estao linkadas no menu/layout, e so depois transformar a copia antiga em redirect controlado ou remover.

## Validacao

Lint PHP executado com `C:\xampp\php\php.exe`.

- Arquivos OK: 173
- Erros de sintaxe: 0
- Relatorio lint: `storage/reports/php-lint-20260523-165109.txt`
