CREATE TABLE IF NOT EXISTS parceiro_leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parceiro_id INT NOT NULL,
    lead_id INT NULL,
    nome_lead VARCHAR(160) NULL,
    telefone_lead VARCHAR(40) NULL,
    modelo_interesse VARCHAR(160) NULL,
    origem VARCHAR(120) NULL,
    status ENUM('novo','contactado','negociacao','fechado','perdido') NOT NULL DEFAULT 'novo',
    valor_estimado DECIMAL(12,2) NULL,
    comissao_prevista DECIMAL(12,2) NULL,
    observacoes TEXT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_parceiro_leads_parceiro_id (parceiro_id),
    INDEX idx_parceiro_leads_lead_id (lead_id),
    INDEX idx_parceiro_leads_status (status),
    INDEX idx_parceiro_leads_criado_em (criado_em),
    CONSTRAINT fk_parceiro_leads_parceiro
        FOREIGN KEY (parceiro_id) REFERENCES parceiros(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
