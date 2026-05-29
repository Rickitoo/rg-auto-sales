<div class="finance-page">
    <div class="rg-panel">
        <div class="rg-panel-body rg-section-head">
            <div>
                <h2>Dashboard Financeiro</h2>
                <p>Visao do mes atual para recebidos, pendentes, custos e lucro real.</p>
            </div>
            <div class="rg-page-actions">
                <a class="btn btn-light" href="<?= h(url('admin/vendas/vendas.php')) ?>">Vendas</a>
                <a class="btn btn-primary" href="<?= h(url('admin/vendas/nova_venda.php')) ?>">Nova venda</a>
            </div>
        </div>
    </div>

    <div class="rg-kpi-grid">
        <div class="rg-kpi-card is-success">
            <strong><?= h(money($recebido)) ?></strong>
            <span>Recebido no mes</span>
        </div>

        <div class="rg-kpi-card is-warning">
            <strong><?= h(money($pendente)) ?></strong>
            <span>Pendente</span>
        </div>

        <div class="rg-kpi-card is-danger">
            <strong><?= h(money($custosMes)) ?></strong>
            <span>Custos</span>
        </div>

        <div class="rg-kpi-card is-info">
            <strong><?= h(money($lucro)) ?></strong>
            <span>Lucro real</span>
        </div>
    </div>

    <div class="page-card finance-forecast">
        <span>Previsao</span>
        <strong class="rg-forecast-value">
            <?= h(money($lucroPrevisto)) ?>
        </strong>
        <small>Se todas vendas forem pagas.</small>
    </div>

    <?php if ($pendente > 0): ?>
        <div class="rg-alert rg-alert-warning">
            Tens dinheiro pendente. Foco em cobrar clientes.
        </div>
    <?php endif; ?>

    <?php if ($lucro < 0): ?>
        <div class="rg-alert rg-alert-danger">
            Estas no prejuizo este mes.
        </div>
    <?php endif; ?>
</div>
