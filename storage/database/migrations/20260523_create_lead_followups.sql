CREATE TABLE IF NOT EXISTS lead_followups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT NOT NULL,
    mensagem TEXT NOT NULL,
    status VARCHAR(50) NULL,
    admin_id INT NULL,
    admin_nome VARCHAR(150) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_lead_followups_lead_id (lead_id),
    INDEX idx_lead_followups_criado_em (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
