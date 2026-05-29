-- Migration manual: suporte a pedidos de importacao dentro da tabela leads.
-- Verificado pelo export storage/database/rg_auto_sales.sql: a tabela leads ja possui
-- origem, mensagem, status e criado_em. Esta migration apenas amplia ENUMs existentes.
-- Execute em janela de manutencao e faca backup antes de aplicar.

ALTER TABLE leads
  MODIFY tipo ENUM('testdrive','venda','importacao','consulta','orcamento') NOT NULL;

ALTER TABLE leads
  MODIFY origem ENUM('site','ig','fb','wa','outro','importacao') NOT NULL DEFAULT 'site';

ALTER TABLE leads
  MODIFY status ENUM(
    'novo',
    'contactado',
    'qualificado',
    'agendado',
    'orcamento',
    'aguardando_opcoes',
    'negociacao',
    'pagamento',
    'embarcado',
    'em_transito',
    'desalfandegamento',
    'entregue',
    'fechado',
    'perdido'
  ) NOT NULL DEFAULT 'novo';
