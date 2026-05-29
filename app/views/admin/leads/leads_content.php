<div class="leads-page">
    <div class="rg-panel">
        <div class="rg-panel-body rg-section-head">
            <div>
                <h2>Lista de Leads</h2>
                <p>Priorize contactos, acompanhe status e avance oportunidades para venda.</p>
            </div>
            <div class="rg-page-actions">
                <a class="btn btn-light" href="<?= h(url('admin/importacoes/index.php')) ?>">Importacoes</a>
                <a class="btn btn-light" href="<?= h(url('admin/crm/inbox.php')) ?>">Abrir CRM</a>
                <a class="btn btn-primary" href="<?= h(url('admin/leads/adicionar_lead.php')) ?>">Novo lead</a>
            </div>
        </div>
    </div>

    <?php if ($followCount > 0): ?>
        <div class="rg-alert rg-alert-warning">
            <?= h($followCount) ?> leads precisam de follow-up.
        </div>
    <?php endif; ?>

    <section class="rg-kpi-grid">
        <div class="rg-kpi-card is-info"><strong><?= h($count['total'] ?? 0) ?></strong><span>Total</span></div>
        <div class="rg-kpi-card is-info"><strong><?= h($count['novo'] ?? 0) ?></strong><span>Novos</span></div>
        <div class="rg-kpi-card is-success"><strong><?= h($count['contactado'] ?? 0) ?></strong><span>Contactados</span></div>
        <div class="rg-kpi-card is-warning"><strong><?= h($count['negociacao'] ?? 0) ?></strong><span>Negociacao</span></div>
        <div class="rg-kpi-card is-success"><strong><?= h($count['fechado'] ?? 0) ?></strong><span>Fechados</span></div>
        <div class="rg-kpi-card is-danger"><strong><?= h($count['perdido'] ?? 0) ?></strong><span>Perdidos</span></div>
    </section>

    <div class="rg-panel">
        <div class="rg-panel-body">
            <form method="GET" action="<?= h(url('admin/leads/leads.php')) ?>" class="rg-filter-grid leads-filter-grid">
                <input type="text" name="q" value="<?= h($q) ?>" class="form-control" placeholder="Buscar por nome ou telefone">
                <select name="origem" class="form-select">
                    <option value="">Todas as origens</option>
                    <option value="importacao" <?= ($origemFiltro ?? '') === 'importacao' ? 'selected' : '' ?>>Importacao</option>
                    <option value="site" <?= ($origemFiltro ?? '') === 'site' ? 'selected' : '' ?>>Site</option>
                    <option value="ig" <?= ($origemFiltro ?? '') === 'ig' ? 'selected' : '' ?>>Instagram</option>
                    <option value="fb" <?= ($origemFiltro ?? '') === 'fb' ? 'selected' : '' ?>>Facebook</option>
                    <option value="wa" <?= ($origemFiltro ?? '') === 'wa' ? 'selected' : '' ?>>WhatsApp</option>
                    <option value="outro" <?= ($origemFiltro ?? '') === 'outro' ? 'selected' : '' ?>>Outro</option>
                </select>
                <select name="status" class="form-select">
                    <option value="">Todos os status</option>
                    <?php foreach (['novo','contactado','orcamento','aguardando_opcoes','negociacao','pagamento','embarcado','em_transito','desalfandegamento','entregue','fechado','perdido'] as $statusOption): ?>
                        <option value="<?= h($statusOption) ?>" <?= ($filtro ?? '') === $statusOption ? 'selected' : '' ?>><?= h(lead_status_label($statusOption)) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary" type="submit">Buscar</button>
                <a class="btn btn-light" href="<?= h(url('admin/leads/leads.php')) ?>">Limpar</a>
            </form>
        </div>
    </div>

    <div class="rg-table-wrap">
        <table class="table table-hover align-middle m-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nome</th>
                    <th>Telefone</th>
                    <th>Carro</th>
                    <th>Origem</th>
                    <th>Status</th>
                    <th>Score</th>
                    <th>Acoes</th>
                    <th>Follow-up</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($res)): ?>
                    <?php
                    $status = $row['status'];

                    $badge = lead_status_badge((string)$status);

                    $tel = preg_replace('/[^0-9]/', '', $row['telefone']);
                    $msg = urlencode(gerarMensagem($row));
                    $carro = trim(($row['marca'] ?? '') . ' ' . ($row['modelo'] ?? ''));
                    ?>

                    <tr class="<?= (int)$row['lead_score'] >= 50 ? 'lead-row-hot' : '' ?>">
                        <td><?= h($row['id']) ?></td>
                        <td><strong><?= h($row['nome']) ?></strong></td>
                        <td><?= h($row['telefone']) ?></td>
                        <td><?= h($carro !== '' ? $carro : '-') ?></td>
                        <td>
                            <?php if (($row['origem'] ?? '') === 'importacao'): ?>
                                <span class="badge bg-info">Importacao</span>
                            <?php else: ?>
                                <span class="badge bg-light text-dark"><?= h($row['origem'] ?? '-') ?></span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-<?= h($badge) ?>"><?= h(lead_status_label((string)$status)) ?></span></td>
                        <td><span class="badge bg-dark"><?= h((int)$row['lead_score']) ?></span></td>
                        <td>
                            <div class="rg-row-actions">
                                <a class="btn btn-sm btn-success" target="_blank" rel="noopener" href="https://wa.me/<?= h($tel) ?>?text=<?= h($msg) ?>">WhatsApp</a>
                                <form class="d-inline" method="POST" action="<?= h(url('admin/vendas/marcar_venda.php')) ?>">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="lead_id" value="<?= (int)$row['id'] ?>">
                                    <input type="hidden" name="carro_id" value="<?= (int)$row['carro_id'] ?>">
                                    <button class="btn btn-sm btn-primary" type="submit">Venda</button>
                                </form>
                                <a class="btn btn-sm btn-info" href="<?= h(url('admin/leads/ver_lead.php?id=' . (int)$row['id'])) ?>">Ver</a>
                                <a class="btn btn-sm btn-warning" href="<?= h(url('admin/crm/inbox.php?id=' . (int)$row['id'])) ?>">CRM</a>
                            </div>
                        </td>
                        <td>
                            <?= $followupField && !empty($row[$followupField])
                                ? h(date('d/m H:i', strtotime($row[$followupField])))
                                : '-' ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
