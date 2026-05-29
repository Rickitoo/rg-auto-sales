<div class="lead-detail-page">
    <section class="rg-panel">
        <div class="rg-panel-body">
            <div class="rg-section-head">
                <div>
                    <h2>Lead #<?= h($row['id']) ?></h2>
                    <p>Dados registados para acompanhamento comercial.</p>
                </div>
                <div class="rg-page-actions">
                    <?php if (!empty($isImportacao)): ?>
                        <span class="lead-origin-badge">Importacao</span>
                    <?php endif; ?>
                    <a class="btn btn-light" href="<?= h(url('admin/funil.php')) ?>">Voltar</a>
                    <a class="btn btn-primary" href="<?= h(url('admin/leads/ver_lead.php?id=' . (int)$row['id'])) ?>">Abrir detalhe CRM</a>
                </div>
            </div>

            <div class="rg-detail-grid lead-legacy-detail-grid">
                <div class="rg-detail-item">
                    <span class="label">Nome</span>
                    <span class="value"><?= h($row['nome'] ?? '') ?></span>
                </div>
                <div class="rg-detail-item">
                    <span class="label">Telefone</span>
                    <span class="value"><?= h($row['telefone'] ?? '') ?></span>
                </div>
                <div class="rg-detail-item">
                    <span class="label">Email</span>
                    <span class="value"><?= h($row['email'] ?? '') ?></span>
                </div>
                <div class="rg-detail-item">
                    <span class="label">Tipo</span>
                    <span class="value"><?= h($row['tipo'] ?? '') ?></span>
                </div>
                <div class="rg-detail-item">
                    <span class="label">Status</span>
                    <span class="value"><?= h($row['status'] ?? '') ?></span>
                </div>
                <div class="rg-detail-item">
                    <span class="label">Carro</span>
                    <span class="value"><?= h(trim(($row['marca'] ?? '') . ' ' . ($row['modelo'] ?? '') . ' ' . ($row['ano'] ?? ''))) ?></span>
                </div>
                <div class="rg-detail-item">
                    <span class="label">Criado em</span>
                    <span class="value"><?= h($row['criado_em'] ?? '') ?></span>
                </div>
            </div>

            <div class="rg-panel lead-message-panel">
                <div class="rg-panel-body">
                    <span class="stat-label">Mensagem</span>
                    <div class="lead-message-block"><?= nl2br(h($row['mensagem'] ?? '')) ?></div>
                </div>
            </div>
        </div>
    </section>
</div>
