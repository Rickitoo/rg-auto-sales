ALTER TABLE parceiro_leads
    ADD COLUMN IF NOT EXISTS crm_lead_id INT NULL AFTER lead_id,
    ADD COLUMN IF NOT EXISTS sincronizado_crm TINYINT(1) NOT NULL DEFAULT 0 AFTER crm_lead_id,
    ADD COLUMN IF NOT EXISTS sincronizado_em DATETIME NULL AFTER sincronizado_crm,
    ADD INDEX IF NOT EXISTS idx_parceiro_leads_crm_lead_id (crm_lead_id),
    ADD INDEX IF NOT EXISTS idx_parceiro_leads_sincronizado_crm (sincronizado_crm);

SET @partner_leads_sync_fk_sql := (
    SELECT IF(
        EXISTS (
            SELECT 1
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'leads'
        )
        AND NOT EXISTS (
            SELECT 1
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'parceiro_leads'
              AND CONSTRAINT_NAME = 'fk_parceiro_leads_crm_lead'
        ),
        'ALTER TABLE parceiro_leads ADD CONSTRAINT fk_parceiro_leads_crm_lead FOREIGN KEY (crm_lead_id) REFERENCES leads(id)',
        'SELECT 1'
    )
);

PREPARE partner_leads_sync_fk_stmt FROM @partner_leads_sync_fk_sql;
EXECUTE partner_leads_sync_fk_stmt;
DEALLOCATE PREPARE partner_leads_sync_fk_stmt;
