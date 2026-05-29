<div class="dashboard-page">
    <div class="rg-panel">
        <div class="rg-panel-body rg-section-head">
            <div>
                <h2>Dashboard RG Auto Sales</h2>
                <p>Resumo operacional do mes, follow-ups prioritarios e vendas recentes.</p>
            </div>
            <div class="rg-page-actions">
                <a href="<?= h(url('admin/vendas/nova_venda.php')) ?>" class="btn btn-primary">Nova venda</a>
                <a href="<?= h(url('admin/carros/adicionar_carro.php')) ?>" class="btn btn-light">Adicionar carro</a>
            </div>
        </div>
    </div>

    <div class="rg-kpi-grid">
        <div class="rg-kpi-card is-info">
            <strong><?= h($vendasMes) ?></strong>
            <span>Vendas no mes</span>
        </div>

        <div class="rg-kpi-card is-success">
            <strong><?= h(money($lucroMes)) ?></strong>
            <span>Lucro</span>
        </div>

        <div class="rg-kpi-card is-warning">
            <strong><?= h(number_format($taxaConversao, 1)) ?>%</strong>
            <span>Conversao</span>
        </div>
    </div>

    <div class="rg-dashboard-grid">
        <div class="rg-panel">
            <div class="rg-panel-body">
                <div class="rg-section-head">
                    <div>
                        <h2>Alertas</h2>
                        <p>Sinais que merecem atencao comercial.</p>
                    </div>
                </div>

                <?php if (empty($alertas)): ?>
                    <div class="rg-alert rg-alert-success">Tudo sob controlo.</div>
                <?php else: ?>
                    <div class="rg-stack">
                        <?php foreach ($alertas as $a): ?>
                            <div class="rg-alert rg-alert-warning"><?= h($a) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="rg-panel">
            <div class="rg-panel-body">
                <div class="rg-section-head">
                    <div>
                        <h2>Top Carros</h2>
                        <p>Modelos mais vendidos.</p>
                    </div>
                </div>

                <?php if (empty($topCarros)): ?>
                    <div class="empty">Nenhum carro vendido ainda.</div>
                <?php else: ?>
                    <div class="rg-stack">
                        <?php foreach ($topCarros as $c): ?>
                            <div class="rg-list-row">
                                <strong><?= h($c['marca'] . ' ' . $c['modelo']) ?></strong>
                                <span class="mini-badge"><?= h($c['total']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="rg-panel">
        <div class="rg-panel-body">
            <div class="rg-section-head">
                <div>
                    <h2>Follow-up Prioritario</h2>
                    <p>Leads ativos ordenados por urgencia.</p>
                </div>
                <div class="rg-page-actions">
                    <a href="<?= h(url('admin/leads/leads.php')) ?>" class="btn btn-light">Ver leads</a>
                </div>
            </div>

            <?php if (empty($leadsFollow)): ?>
                <div class="empty">Nenhum lead pendente para follow-up.</div>
            <?php else: ?>
                <div class="rg-stack">
                    <?php foreach ($leadsFollow as $l): ?>
                        <?php
                        $tel = preg_replace('/[^0-9]/', '', $l['telefone']);
                        $msg = urlencode("Ola {$l['nome']}, estou a dar seguimento ao seu interesse.");
                        $whatsappTelefone = str_starts_with($tel, '258') ? $tel : '258' . ltrim($tel, '0');
                        ?>
                        <div class="rg-list-row">
                            <div>
                                <strong><?= h($l['nome']) ?></strong>
                                <small><?= h($l['telefone'] ?? '') ?> | <?= h($l['status'] ?? '') ?></small>
                            </div>
                            <div class="rg-row-actions">
                                <a class="btn btn-success btn-sm" target="_blank" rel="noopener" href="https://wa.me/<?= h($whatsappTelefone) ?>?text=<?= h($msg) ?>">WhatsApp</a>
                                <a class="btn btn-primary btn-sm" href="<?= h(url('admin/leads/ver_lead.php?id=' . (int)$l['id'])) ?>">Ver</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="rg-panel">
        <div class="rg-panel-body">
            <div class="rg-section-head">
                <div>
                    <h2>Ultimas vendas</h2>
                    <p>Historico comercial recente.</p>
                </div>
                <div class="rg-page-actions">
                    <a href="<?= h(url('admin/vendas/vendas.php')) ?>" class="btn btn-light">Ver vendas</a>
                </div>
            </div>
        </div>

        <div class="rg-table-wrap">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Carro</th>
                        <th>Comissao</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vendas)): ?>
                        <tr>
                            <td colspan="4" class="empty">Nenhuma venda encontrada.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($vendas as $v): ?>
                            <tr>
                                <td><?= h($v['cliente']) ?></td>
                                <td><?= h($v['marca'] . ' ' . $v['modelo']) ?></td>
                                <td><?= h(money($v['comissao_rg'])) ?></td>
                                <td>
                                    <?php if ($v['status'] === 'PENDENTE'): ?>
                                        <form class="d-inline" method="POST" action="<?= h(url('admin/vendas/pagar_venda.php')) ?>">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                                            <button class="btn btn-sm btn-warning" type="submit" onclick="return confirm('Marcar esta venda como paga?');">Marcar pago</button>
                                        </form>
                                    <?php else: ?>
                                        <a class="btn btn-sm btn-success" href="<?= h(url('admin/vendas/venda_detalhe.php?id=' . (int)$v['id'])) ?>">PAGO</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
