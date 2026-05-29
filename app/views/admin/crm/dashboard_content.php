<div class="crm-dashboard-page">
    <div class="rg-panel">
        <div class="rg-panel-body rg-section-head">
            <div>
                <h2>Dashboard CRM</h2>
                <p>Visao central da operacao comercial.</p>
            </div>
            <div class="rg-page-actions">
                <a class="btn btn-primary" href="<?= h(url('admin/crm/inbox.php')) ?>">Abrir Inbox</a>
                <a class="btn btn-light" href="<?= h(url('admin/leads/leads.php')) ?>">Leads</a>
                <a class="btn btn-success" href="<?= h(url('admin/vendas/nova_venda.php')) ?>">Nova venda</a>
                <a class="btn btn-dark" href="<?= h(url('admin/financeiro/dashboard_financeiro.php')) ?>">Financeiro</a>
            </div>
        </div>
    </div>

    <section class="rg-kpi-grid">
        <div class="rg-kpi-card is-info"><strong><?= h($totalLeads) ?></strong><span>Total de leads</span></div>
        <div class="rg-kpi-card is-info"><strong><?= h($leadsNovos) ?></strong><span>Leads novos</span></div>
        <div class="rg-kpi-card is-danger"><strong><?= h(count($leadsUrgentes)) ?></strong><span>Urgentes</span></div>
        <div class="rg-kpi-card is-warning"><strong><?= h($leadsNegociacao) ?></strong><span>Negociacao</span></div>
        <div class="rg-kpi-card is-success"><strong><?= h($vendasFechadas) ?></strong><span>Vendas fechadas</span></div>
        <div class="rg-kpi-card is-warning"><strong><?= h(count($followupsPendentes)) ?></strong><span>Follow-ups pendentes</span></div>
    </section>

    <section class="rg-crm-grid">
        <div class="rg-panel">
            <div class="rg-panel-body">
                <div class="rg-section-head">
                    <div>
                        <h2>Leads urgentes</h2>
                        <p>Prioridade comercial imediata.</p>
                    </div>
                    <div class="rg-page-actions">
                        <a href="<?= h(url('admin/crm/inbox.php')) ?>" class="btn btn-light">Ver na Inbox</a>
                    </div>
                </div>

                <?php if ($leadsUrgentes): ?>
                    <div class="rg-stack">
                        <?php foreach (array_slice($leadsUrgentes, 0, 7) as $lead): ?>
                            <a class="rg-list-row" href="<?= h(url('admin/crm/inbox.php?id=' . (int)$lead['id'])) ?>">
                                <div>
                                    <strong><?= h($lead['nome']) ?></strong>
                                    <small><?= h($lead['telefone']) ?><?= crm_dash_car($lead) !== '' ? ' - ' . h(crm_dash_car($lead)) : '' ?></small>
                                </div>
                                <span class="pill <?= h($lead['_attention']['class']) ?>"><?= h($lead['_attention']['dias']) ?> dia(s)</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty">Nenhum lead urgente neste momento.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="rg-panel">
            <div class="rg-panel-body">
                <div class="rg-section-head">
                    <div>
                        <h2>Funil por status</h2>
                        <p>Distribuicao atual dos leads.</p>
                    </div>
                    <div class="rg-page-actions">
                        <a href="<?= h(url('admin/leads/leads.php')) ?>" class="btn btn-light">Listar leads</a>
                    </div>
                </div>

                <div class="crm-funnel">
                    <?php foreach ($funil as $key => $item): ?>
                        <div class="crm-funnel-row">
                            <div class="crm-funnel-label"><?= h($item['label']) ?></div>
                            <div class="crm-funnel-bar">
                                <span style="width:<?= h((string)max(4, round(($item['total'] / $maxFunil) * 100))) ?>%"></span>
                            </div>
                            <div class="crm-funnel-total"><?= h($item['total']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="rg-crm-grid">
        <div class="rg-panel">
            <div class="rg-panel-body">
                <div class="rg-section-head">
                    <div>
                        <h2>Follow-ups pendentes</h2>
                        <p>Fila de acompanhamento.</p>
                    </div>
                    <div class="rg-page-actions">
                        <a href="<?= h(url('admin/crm/inbox.php')) ?>" class="btn btn-light">Abrir fila</a>
                    </div>
                </div>

                <?php if ($followupsPendentes): ?>
                    <div class="rg-stack">
                        <?php foreach (array_slice($followupsPendentes, 0, 8) as $lead): ?>
                            <a class="rg-list-row" href="<?= h(url('admin/crm/inbox.php?id=' . (int)$lead['id'])) ?>">
                                <div>
                                    <strong><?= h($lead['nome']) ?></strong>
                                    <small>Ultimo contacto: <?= h($lead['_attention']['dias'] ?? '-') ?> dia(s) atras</small>
                                </div>
                                <span class="pill <?= h($lead['_attention']['class']) ?>"><?= h($lead['_attention']['label']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty">Sem follow-ups pendentes.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="rg-panel">
            <div class="rg-panel-body">
                <div class="rg-section-head">
                    <div>
                        <h2>Links rapidos</h2>
                        <p>Atalhos operacionais.</p>
                    </div>
                </div>

                <div class="rg-stack">
                    <a class="rg-list-row" href="<?= h(url('admin/crm/inbox.php')) ?>"><div><strong>Inbox comercial</strong><small>Follow-up, WhatsApp e timeline</small></div><span class="pill pill-new">CRM</span></a>
                    <a class="rg-list-row" href="<?= h(url('admin/leads/leads.php')) ?>"><div><strong>Lista de leads</strong><small>Tabela operacional completa</small></div><span class="pill pill-soft">Leads</span></a>
                    <a class="rg-list-row" href="<?= h(url('admin/vendas/nova_venda.php')) ?>"><div><strong>Nova venda</strong><small>Criar venda manualmente</small></div><span class="pill pill-neg">Vendas</span></a>
                    <a class="rg-list-row" href="<?= h(url('admin/financeiro/dashboard_financeiro.php')) ?>"><div><strong>Financeiro</strong><small>Lucro, comissoes e pagamentos</small></div><span class="pill pill-stop">Fin</span></a>
                </div>
            </div>
        </div>
    </section>

    <section class="crm-activity-grid">
        <div class="rg-panel">
            <div class="rg-panel-body">
                <h2 class="crm-section-title">Ultimos leads</h2>
                <?php if ($recentLeads): ?>
                    <div class="rg-stack">
                        <?php foreach ($recentLeads as $lead): ?>
                            <a class="rg-list-row" href="<?= h(url('admin/crm/inbox.php?id=' . (int)$lead['id'])) ?>">
                                <div><strong><?= h($lead['nome']) ?></strong><small><?= h(date('d/m/Y H:i', strtotime($lead['criado_em']))) ?></small></div>
                                <span class="pill <?= h($lead['_attention']['class']) ?>"><?= h($lead['_attention']['label']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty">Nenhum lead registado.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="rg-panel">
            <div class="rg-panel-body">
                <h2 class="crm-section-title">Ultimos follow-ups</h2>
                <?php if ($recentFollowups): ?>
                    <div class="rg-stack">
                        <?php foreach ($recentFollowups as $item): ?>
                            <a class="rg-list-row" href="<?= h(url('admin/crm/inbox.php?id=' . (int)$item['lead_id'])) ?>">
                                <div><strong><?= h($item['nome'] ?: 'Lead #' . $item['lead_id']) ?></strong><small><?= h(mb_strimwidth($item['mensagem'], 0, 70, '...')) ?></small></div>
                                <span class="pill pill-soft"><?= h(date('d/m', strtotime($item['criado_em']))) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty">Sem follow-ups registados.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="rg-panel">
            <div class="rg-panel-body">
                <h2 class="crm-section-title">Ultimas vendas</h2>
                <?php if ($recentVendas): ?>
                    <div class="rg-stack">
                        <?php foreach ($recentVendas as $venda): ?>
                            <a class="rg-list-row" href="<?= h(url('admin/vendas/venda_detalhe.php?id=' . (int)$venda['id'])) ?>">
                                <div><strong><?= h(trim($venda['marca'] . ' ' . $venda['modelo'] . ' ' . $venda['ano'])) ?></strong><small><?= h(date('d/m/Y', strtotime($venda['data_venda'] ?: $venda['criado_em']))) ?></small></div>
                                <span class="pill pill-neg"><?= h($venda['status']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty">Sem vendas recentes.</div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>
