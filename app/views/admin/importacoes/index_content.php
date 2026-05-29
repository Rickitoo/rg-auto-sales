<div class="importacoes-page">
    <div class="rg-panel">
        <div class="rg-panel-body rg-section-head">
            <div>
                <h2>Pedidos de Importacao</h2>
                <p>Visao filtrada dos leads com origem importacao. O acompanhamento continua no CRM.</p>
            </div>
            <div class="rg-page-actions">
                <a class="btn btn-light" href="<?= h(url('admin/leads/leads.php?origem=importacao')) ?>">Ver em Leads</a>
                <a class="btn btn-primary" href="<?= h(public_url('importar_carro.php')) ?>" target="_blank" rel="noopener">Pagina publica</a>
            </div>
        </div>
    </div>

    <section class="rg-kpi-grid">
        <div class="rg-kpi-card is-info"><strong><?= h((int)($stats['total'] ?? 0)) ?></strong><span>Total de pedidos</span></div>
        <div class="rg-kpi-card is-info"><strong><?= h((int)($stats['novos'] ?? 0)) ?></strong><span>Novos pedidos</span></div>
        <div class="rg-kpi-card is-warning"><strong><?= h((int)($stats['negociacao'] ?? 0)) ?></strong><span>Em negociacao</span></div>
        <div class="rg-kpi-card is-success"><strong><?= h((int)($stats['transito'] ?? 0)) ?></strong><span>Embarcado / transito</span></div>
    </section>

    <div class="rg-panel">
        <div class="rg-panel-body">
            <form method="GET" action="<?= h(url('admin/importacoes/index.php')) ?>" class="rg-filter-grid importacoes-filter-grid">
                <select name="status" class="form-select">
                    <option value="">Todos os status</option>
                    <?php foreach ($allowedStatuses as $statusOption): ?>
                        <option value="<?= h($statusOption) ?>" <?= $statusFiltro === $statusOption ? 'selected' : '' ?>>
                            <?= h(importacao_status_label($statusOption)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary" type="submit">Filtrar</button>
                <a class="btn btn-light" href="<?= h(url('admin/importacoes/index.php')) ?>">Limpar</a>
            </form>
        </div>
    </div>

    <div class="rg-table-wrap">
        <table class="table table-hover align-middle m-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>Contacto</th>
                    <th>Viatura pretendida</th>
                    <th>Status</th>
                    <th>Criado em</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($leadsImportacao && mysqli_num_rows($leadsImportacao) > 0): ?>
                    <?php while ($lead = mysqli_fetch_assoc($leadsImportacao)): ?>
                        <?php
                        $status = (string)($lead['status'] ?? 'novo');
                        $badge = importacao_status_badge($status);
                        $telefone = preg_replace('/[^0-9]/', '', (string)($lead['telefone'] ?? ''));
                        $carro = trim(($lead['marca'] ?? '') . ' ' . ($lead['modelo'] ?? '') . ' ' . ($lead['ano'] ?? ''));
                        ?>
                        <tr>
                            <td><?= h((int)$lead['id']) ?></td>
                            <td>
                                <strong><?= h($lead['nome'] ?? '') ?></strong>
                                <small class="d-block text-muted"><?= h($lead['email'] ?? '') ?></small>
                            </td>
                            <td><?= h($lead['telefone'] ?? '') ?></td>
                            <td><?= h($carro !== '' ? $carro : '-') ?></td>
                            <td><span class="badge bg-<?= h($badge) ?>"><?= h(importacao_status_label($status)) ?></span></td>
                            <td><?= h(!empty($lead['criado_em']) ? date('d/m/Y H:i', strtotime($lead['criado_em'])) : '-') ?></td>
                            <td>
                                <div class="rg-row-actions">
                                    <a class="btn btn-sm btn-info" href="<?= h(url('admin/leads/ver_lead.php?id=' . (int)$lead['id'])) ?>">Abrir</a>
                                    <a class="btn btn-sm btn-warning" href="<?= h(url('admin/crm/inbox.php?id=' . (int)$lead['id'])) ?>">CRM</a>
                                    <?php if ($telefone !== ''): ?>
                                        <a class="btn btn-sm btn-success" target="_blank" rel="noopener" href="https://wa.me/<?= h($telefone) ?>">WhatsApp</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Sem pedidos de importacao encontrados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
