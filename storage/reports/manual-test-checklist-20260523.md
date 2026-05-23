# Checklist de teste manual pos-refactor - RG_AUTO_SALES

Data: 2026-05-23

Objetivo: validar no navegador as rotas canonicas, redirects legados, assets e fluxos principais antes de remover wrappers antigos.

Ambiente sugerido:

- Base local: `http://localhost/RG_AUTO_SALES`
- Executar com XAMPP/Apache ligado.
- Usar um navegador normal e, quando possivel, DevTools aberto na aba Network/Console.
- Fazer primeiro com usuario admin valido.
- Se o projeto estiver noutra pasta/dominio, substituir apenas a base da URL.

## 1. Login admin

| Campo | Valor |
| --- | --- |
| URL | `/auth/login.php` |
| Acao | Informar credenciais de admin e submeter login |
| Resultado esperado | Login concluido e redirecionamento para painel administrativo, preferencialmente `/admin/dashboard.php` |
| Erro possivel | `NOT FOUND`, loop de login, sessao nao criada, mensagem de credenciais invalidas mesmo com dados corretos |
| Prioridade | Critica |

## 2. Logout

| Campo | Valor |
| --- | --- |
| URL | `/auth/logout.php` e botao "Sair" no topo do admin |
| Acao | Clicar em sair depois de estar autenticado |
| Resultado esperado | Sessao encerrada e usuario volta para tela publica/login conforme fluxo atual |
| Erro possivel | Sessao permanece ativa, redirect para rota antiga quebrada, `headers already sent` |
| Prioridade | Critica |

## 3. Dashboard admin

| Campo | Valor |
| --- | --- |
| URL | `/admin/dashboard.php` |
| Acao | Abrir dashboard apos login |
| Resultado esperado | Cards/indicadores carregam, menu lateral aparece, links principais funcionam |
| Erro possivel | SQL error, include quebrado, asset ausente, link relativo para pagina inexistente |
| Prioridade | Critica |

## 4. Listar carros

| Campo | Valor |
| --- | --- |
| URL | `/admin/carros/listar_carros.php` |
| Acao | Abrir lista, filtrar por busca/status e limpar filtros |
| Resultado esperado | Lista de carros aparece, filtros preservam resultado, botoes de editar/fotos/venda aparecem |
| Erro possivel | Tabela vazia por erro SQL, links para `app/modules/cars/*`, imagem quebrada, paginacao/filtro quebrado |
| Prioridade | Critica |

## 5. Adicionar carro

| Campo | Valor |
| --- | --- |
| URL | `/admin/carros/adicionar_carro.php` |
| Acao | Preencher dados minimos e salvar; se houver upload, testar uma imagem pequena |
| Resultado esperado | Carro cadastrado e visivel na listagem/admin ou mensagem clara de sucesso |
| Erro possivel | POST para rota antiga, upload falha, permissao de pasta, redirect para pagina inexistente |
| Prioridade | Critica |

## 6. Editar carro

| Campo | Valor |
| --- | --- |
| URL | `/admin/carros/editar_carro.php?id=ID_VALIDO` |
| Acao | Alterar campo simples, salvar e voltar para lista |
| Resultado esperado | Alteracao persiste e botoes de fotos/lista apontam para rotas admin |
| Erro possivel | `ID invalido`, tabela/coluna errada, CSRF invalido, redirect antigo |
| Prioridade | Alta |

## 7. Apagar carro

| Campo | Valor |
| --- | --- |
| URL | Botao apagar em `/admin/carros/listar_carros.php` |
| Acao | Usar um carro de teste, confirmar exclusao se houver confirmacao |
| Resultado esperado | Carro removido/inativado conforme regra atual e retorno para lista |
| Erro possivel | CSRF invalido, redirect para rota antiga, exclusao fisica indevida, erro por dependencias de vendas/fotos |
| Prioridade | Critica |

## 8. Marcar carro como vendido

| Campo | Valor |
| --- | --- |
| URL | `/admin/vendas/marcar_venda.php?id=ID_CARRO` ou botao "Marcar Venda" na lista de carros |
| Acao | Abrir fluxo de venda de um carro disponivel e concluir conforme formulario |
| Resultado esperado | Venda criada ou carro marcado como vendido, com retorno para dashboard/detalhe de venda |
| Erro possivel | Link antigo `marcar_venda.php` sem pasta, venda duplicada, status do carro nao muda |
| Prioridade | Critica |

## 9. Listar leads

| Campo | Valor |
| --- | --- |
| URL | `/admin/leads/leads.php` e `/admin/leads/listar_leads.php` |
| Acao | Abrir as duas visoes e aplicar filtro/status quando disponivel |
| Resultado esperado | Leads carregam e a navegacao entre lista/detalhe funciona |
| Erro possivel | Query quebrada, status nao reconhecido, link para `app/modules/leads/*` |
| Prioridade | Critica |

## 10. Ver detalhe do lead

| Campo | Valor |
| --- | --- |
| URL | `/admin/leads/ver_lead.php?id=ID_VALIDO` |
| Acao | Abrir detalhe a partir da lista e testar envio/salvamento de mensagem se houver campo |
| Resultado esperado | Dados do lead aparecem, historico/mensagens carregam e POST retorna ao mesmo detalhe |
| Erro possivel | `Lead nao encontrado`, POST perdido, link relativo para `marcar_venda.php` ou `editar_lead.php` inexistente |
| Prioridade | Alta |

## 11. Alterar status do lead

| Campo | Valor |
| --- | --- |
| URL | Botoes/status em `/admin/leads/listar_leads.php` e pipeline `/admin/funil.php` |
| Acao | Mover lead para outro status e recarregar pagina |
| Resultado esperado | Status atualizado no banco e refletido na lista/pipeline |
| Erro possivel | Endpoint AJAX quebrado, status antigo/novo divergente, redirect para lista errada |
| Prioridade | Critica |

## 12. Abrir WhatsApp/follow-up

| Campo | Valor |
| --- | --- |
| URL | Botoes WhatsApp/follow-up em `/admin/leads/leads.php`, `/admin/leads/ver_lead.php`, `/admin/funil.php` |
| Acao | Clicar em WhatsApp e follow-up de um lead com telefone valido |
| Resultado esperado | WhatsApp abre em nova aba com numero/mensagem; follow-up salva ou redireciona conforme regra atual |
| Erro possivel | Telefone formatado errado, link `wa.me` sem codigo do pais, follow-up em rota antiga inexistente |
| Prioridade | Alta |

## 13. Fechar venda

| Campo | Valor |
| --- | --- |
| URL | `/admin/vendas/nova_venda.php`, `/admin/vendas/venda_detalhe.php?id=ID_VENDA`, ou fluxo a partir de lead/carro |
| Acao | Criar venda de teste, confirmar/aprovar quando aplicavel |
| Resultado esperado | Venda aparece em `/admin/vendas/vendas.php`, detalhe abre e status financeiro fica coerente |
| Erro possivel | Comissao nao calculada, carro/lead nao associado, redirect para dashboard errado |
| Prioridade | Critica |

## 14. Dashboard financeiro

| Campo | Valor |
| --- | --- |
| URL | `/admin/financeiro/dashboard_financeiro.php` |
| Acao | Abrir dashboard financeiro e conferir valores do mes |
| Resultado esperado | Recebido, pendente, total, custos e lucro carregam sem erro |
| Erro possivel | Coluna inexistente em `vendas`, tabela `custos` ausente sem tratamento, valores zerados indevidamente |
| Prioridade | Critica |

## 15. Comissoes/lucro real

| Campo | Valor |
| --- | --- |
| URL | `/admin/vendas/venda_detalhe.php?id=ID_VENDA` e `/admin/financeiro/dashboard_financeiro.php` |
| Acao | Conferir uma venda conhecida: valor venda, proprietario, lucro, comissao RG, vendedor e parceiro |
| Resultado esperado | Valores batem com a regra atual e aparecem iguais nos relatorios/detalhe |
| Erro possivel | Divergencia entre helper novo e codigo antigo, campo `comissao` versus `comissao_rg`, arredondamento errado |
| Prioridade | Critica |

## 16. Paginas publicas

| Campo | Valor |
| --- | --- |
| URL | `/public/index.php`, `/public/products.php`, `/public/product-details.php?id=ID_CARRO`, `/public/about.php`, `/public/contacto.php`, `/public/leasing.php`, `/public/account.php` |
| Acao | Navegar pelo menu publico, abrir produto e voltar |
| Resultado esperado | Paginas abrem com CSS/imagens, links usam `/public/*`, detalhes do carro carregam |
| Erro possivel | Link antigo `.html`, asset quebrado, produto sem imagem, `NOT FOUND` ao mudar pasta do projeto |
| Prioridade | Alta |

## 17. Formulario de test-drive

| Campo | Valor |
| --- | --- |
| URL | `/public/test_drive.php` |
| Acao | Preencher formulario e submeter com dados de teste |
| Resultado esperado | Dados gravados/enviados conforme fluxo atual e usuario recebe confirmacao/WhatsApp |
| Erro possivel | Form aponta para handler duplicado, redirect para WhatsApp quebra, validacao bloqueia dados validos |
| Prioridade | Critica |

## 18. Confirmacao publica

| Campo | Valor |
| --- | --- |
| URL | `/public/confirmacao.php` e fluxo apos formulario publico |
| Acao | Abrir confirmacao diretamente e tambem pelo fluxo de formulario |
| Resultado esperado | Mensagem publica aparece sem erro e links de retorno funcionam |
| Erro possivel | Confirmacao nao usada pelo fluxo real, `NOT FOUND`, asset ausente |
| Prioridade | Media |

## 19. Redirecionamentos antigos

| Campo | Valor |
| --- | --- |
| URL | `/app/modules/cars/listar_carros.php`, `/app/modules/leads/leads.php`, `/app/modules/leads/listar_leads.php`, `/app/modules/leads/lead_detalhe.php?id=ID_VALIDO`, `/app/modules/finance/financeiro.php`, `/views/crm/pipeline.php` |
| Acao | Abrir cada URL logado como admin |
| Resultado esperado | Redireciona para rota canonica em `admin/*`, preservando parametros quando existirem |
| Erro possivel | `Failed opening required`, redirect para URL duplicada, perda de query string, acesso sem protecao admin |
| Prioridade | Alta |

## 20. Assets CSS/JS/imagens

| Campo | Valor |
| --- | --- |
| URL | Qualquer pagina admin e publica; verificar Network no DevTools |
| Acao | Recarregar com cache desativado e filtrar por CSS, JS, imagens e fontes |
| Resultado esperado | Nenhum asset local retorna 404; layout nao fica quebrado; imagens de carro/logotipo carregam |
| Erro possivel | `asset()` apontando para pasta errada, imagens antigas fora de `public/assets`, links hardcoded para `ImagensRG/*` |
| Prioridade | Alta |

## Ordem recomendada de execucao

1. Login admin
2. Dashboard admin
3. Carros: listar, adicionar, editar, apagar e marcar vendido
4. Leads: listar, detalhe, status, WhatsApp/follow-up
5. Vendas: criar/fechar, detalhe e comissoes
6. Financeiro: dashboard e lucro real
7. Publico: paginas, produto, test-drive e confirmacao
8. Redirects antigos
9. Assets no DevTools

## Criterio para remover wrappers depois

So remover wrappers antigos quando:

- Todos os testes criticos passarem.
- Nenhum redirect antigo gerar `NOT FOUND`.
- Nenhum formulario POST apontar para `app/modules/*` legado.
- Nenhum endpoint AJAX usado pelo navegador retornar 404/500.
- Assets locais estiverem sem 404 no DevTools.
