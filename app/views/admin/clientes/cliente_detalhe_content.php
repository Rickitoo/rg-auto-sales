<div class="ops-page">
    <div class="rg-panel">
        <div class="rg-panel-body rg-section-head">
            <div>
                <h2><?= h($cliente['nome']) ?></h2>
                <p>Cliente / Test-drive #<?= h($clienteId) ?></p>
            </div>
            <div class="rg-page-actions">
                <a class="btn btn-light" href="<?= h(url('admin/clientes/clientes.php')) ?>">Clientes</a>
                <a class="btn btn-primary" href="<?= h(url('admin/crm/inbox.php')) ?>">CRM Inbox</a>
                <?php if ($telefone !== ''): ?>
                    <a class="btn btn-success" target="_blank" rel="noopener" href="https://wa.me/<?= h($telefone) ?>?text=<?= h($msg) ?>">WhatsApp</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="rg-panel">
                <div class="rg-panel-body">
                    <h5 class="fw-bold mb-3">Dados do cliente</h5>
                    <div class="rg-detail-grid">
                        <div class="rg-detail-item">
                            <span class="label">Telefone</span>
                            <span class="value"><?= h($cliente['telefone']) ?></span>
                        </div>
                        <div class="rg-detail-item">
                            <span class="label">Email</span>
                            <span class="value"><?= h($cliente['email'] ?? '-') ?></span>
                        </div>
                        <div class="rg-detail-item">
                            <span class="label">Veiculo</span>
                            <span class="value"><?= h($carro ?: '-') ?></span>
                        </div>
                        <div class="rg-detail-item">
                            <span class="label">Test-drive</span>
                            <span class="value"><?= h($cliente['data']) ?> <?= h($cliente['hora']) ?></span>
                        </div>
                        <div class="rg-detail-item">
                            <span class="label">Status</span>
                            <span class="value"><?= h($cliente['status']) ?></span>
                        </div>
                    </div>

                    <div class="rg-alert rg-alert-success">
                        <?= nl2br(h($cliente['mensagem'] ?? '-')) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="rg-panel">
                <div class="rg-panel-body">
                    <h5 class="fw-bold mb-3">Historico CRM relacionado</h5>
                    <?php if ($leads): ?>
                        <div class="list-group">
                            <?php foreach ($leads as $lead): ?>
                                <a class="list-group-item list-group-item-action" href="<?= h(url('admin/crm/inbox.php?id=' . (int)$lead['id'])) ?>">
                                    <div class="d-flex justify-content-between gap-2">
                                        <strong><?= h($lead['marca'] . ' ' . $lead['modelo'] . ' ' . $lead['ano']) ?></strong>
                                        <span class="badge bg-dark"><?= h($lead['status']) ?></span>
                                    </div>
                                    <small class="text-muted"><?= h(date('d/m/Y H:i', strtotime($lead['criado_em']))) ?></small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-secondary mb-0">Nenhum lead relacionado encontrado para este cliente.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

