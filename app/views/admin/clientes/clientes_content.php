<div class="ops-page">
    <div class="rg-kpi-grid">
        <div class="rg-kpi-card is-info">
            <strong><?= h($totalClientes ?? 0) ?></strong>
            <span>Total de clientes</span>
            <small>Pedidos e test drives registados</small>
        </div>
        <div class="rg-kpi-card is-success">
            <strong><?= h($clientesConcluidos ?? 0) ?></strong>
            <span>Concluidos</span>
            <small>Atendimentos finalizados</small>
        </div>
        <div class="rg-kpi-card is-warning">
            <strong><?= h($clientesPendentes ?? 0) ?></strong>
            <span>Pendentes</span>
            <small>Contactos a acompanhar</small>
        </div>
    </div>

    <div class="rg-panel">
        <div class="rg-panel-body rg-section-head">
            <div>
                <h2>Clientes / Test Drives</h2>
                <p>Pedidos publicos, contactos e acompanhamento comercial.</p>
            </div>
            <div class="rg-page-actions">
                <a href="<?= h(url('admin/dashboard.php')) ?>" class="btn btn-light">Dashboard</a>
                <a href="<?= h(url('admin/crm/inbox.php')) ?>" class="btn btn-primary">CRM Inbox</a>
            </div>
        </div>
    </div>

    <div class="rg-table-wrap">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Telefone</th>
                    <th>Email</th>
                    <th>Veiculo</th>
                    <th>Data Test Drive</th>
                    <th>Status</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($clientes)): ?>
                    <?php foreach ($clientes as $row): ?>
                        <?php
                        $telefoneLimpo = preg_replace('/\D+/', '', (string)$row['telefone']);
                        $mensagemWhatsapp = rawurlencode(
                            'Ola ' . $row['nome'] . ', aqui e da RG Auto Sales. Estamos a dar seguimento ao seu pedido de test drive para o veiculo ' . $row['marca'] . ' ' . $row['modelo'] . '.'
                        );
                        $statusConcluido = ($row['status'] ?? '') === 'CONCLUIDO';
                        ?>
                        <tr>
                            <td><?= h($row['id']) ?></td>
                            <td><strong><?= h($row['nome']) ?></strong></td>
                            <td><?= h($row['telefone']) ?></td>
                            <td><?= h($row['email']) ?></td>
                            <td>
                                <?= h($row['marca']) ?> <?= h($row['modelo']) ?>
                                <br>
                                <small class="text-muted">Ano: <?= h($row['ano']) ?></small>
                            </td>
                            <td>
                                <?= h($row['data']) ?><br>
                                <small class="text-muted"><?= h($row['hora']) ?></small>
                            </td>
                            <td>
                                <span class="badge <?= $statusConcluido ? 'bg-success' : 'bg-warning text-dark' ?>">
                                    <?= h($row['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="https://wa.me/<?= h($telefoneLimpo) ?>?text=<?= h($mensagemWhatsapp) ?>" target="_blank" rel="noopener" class="btn btn-success btn-sm">
                                        WhatsApp
                                    </a>
                                    <a href="<?= h(url('admin/clientes/cliente_detalhe.php?id=' . (int)$row['id'])) ?>" class="btn btn-primary btn-sm">
                                        Ver
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">Nenhum cliente encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

