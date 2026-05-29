<div class="sales-page">
  <div class="rg-panel">
    <div class="rg-panel-body rg-section-head">
      <div>
        <h2>Vendas</h2>
        <p>Modelo comercial com lucro real, pagamentos e comissoes.</p>
      </div>
      <div class="rg-page-actions">
        <a class="btn btn-success" href="<?= h(url('admin/vendas/nova_venda.php')) ?>">+ Nova venda</a>
        <a class="btn btn-primary" href="<?= h(url('app/modules/finance/export_vendas_csv.php')) ?>">Exportar CSV</a>
        <a class="btn btn-light" href="<?= h(url('admin/dashboard.php')) ?>">Dashboard</a>
      </div>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?php echo h($flash['type']); ?> alert-dismissible fade show" role="alert">
      <?php echo h($flash['msg']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
  <?php endif; ?>

  <div class="rg-kpi-grid">
    <div class="rg-kpi-card is-success">
      <strong><?php echo h($vendasPagas); ?></strong>
      <span>Vendas pagas</span>
    </div>

    <div class="rg-kpi-card is-warning">
      <strong><?php echo h($vendasPend); ?></strong>
      <span>Vendas pendentes</span>
    </div>

    <div class="rg-kpi-card is-info">
      <strong><?php echo h(number_format($comissaoPaga, 2, ',', '.')); ?> MT</strong>
      <span><?= $hasCRG ? "Comissao RG paga" : "Comissao paga" ?></span>
    </div>

    <div class="rg-kpi-card is-warning">
      <strong><?php echo h(number_format($comissaoPend, 2, ',', '.')); ?> MT</strong>
      <span><?= $hasCRG ? "Comissao RG pendente" : "Pendente a receber" ?></span>
    </div>
  </div>

  <div class="rg-panel">
    <div class="rg-panel-body">
      <form class="row g-2 align-items-end" method="GET" action="">
        <div class="col-md-2">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="TODOS" <?php echo ($status === 'TODOS') ? 'selected' : ''; ?>>Todos</option>
            <option value="PENDENTE" <?php echo ($status === 'PENDENTE') ? 'selected' : ''; ?>>Pendente</option>
            <option value="PAGO" <?php echo ($status === 'PAGO') ? 'selected' : ''; ?>>Pago</option>
            <option value="CANCELADO" <?php echo ($status === 'CANCELADO') ? 'selected' : ''; ?>>Cancelado</option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label">Data (de)</label>
          <input type="date" name="data_de" class="form-control" value="<?php echo h($data_de); ?>">
        </div>

        <div class="col-md-2">
          <label class="form-label">Data (ate)</label>
          <input type="date" name="data_ate" class="form-control" value="<?php echo h($data_ate); ?>">
        </div>

        <div class="col-md-4">
          <label class="form-label">Pesquisar (nome/telefone/email)</label>
          <input type="text" name="q" class="form-control" placeholder="Ex: Gani / 84... / email" value="<?php echo h($q); ?>">
        </div>

        <div class="col-md-2 d-grid">
          <button class="btn btn-dark" type="submit">Filtrar</button>
        </div>
      </form>
    </div>
  </div>

  <div class="rg-table-wrap">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Data</th>
          <th>Cliente</th>
          <th>Carro</th>
          <th class="text-end">Valor</th>
          <th class="text-end"><?= $hasCRG ? "RG" : "Comissao" ?></th>
          <?php if ($hasLucro): ?><th class="text-end">Lucro</th><?php endif; ?>
          <th>Status</th>
          <th class="text-end">Acoes</th>
        </tr>
      </thead>
      <tbody>
      <?php if (count($vendas) === 0): ?>
        <tr><td colspan="<?php echo $hasLucro ? 9 : 8; ?>" class="text-center py-4 text-muted">Nenhuma venda encontrada.</td></tr>
      <?php else: ?>
        <?php foreach ($vendas as $v): ?>
          <tr>
            <td><?php echo (int)$v['id']; ?></td>
            <td><?php echo h($v['data_venda']); ?></td>
            <td>
              <div class="fw-semibold"><?php echo h($v['cliente_nome']); ?></div>
              <div class="text-muted small"><?php echo h($v['cliente_telefone']); ?> | <?php echo h($v['cliente_email']); ?></div>
            </td>
            <td><?php echo h($v['marca']); ?> <?php echo h($v['modelo']); ?> (<?php echo h($v['ano']); ?>)</td>
            <td class="text-end"><?php echo h(number_format((float)$v['valor_carro'], 2, ',', '.')); ?> MT</td>

            <td class="text-end">
              <?php
                if ($hasCRG && isset($v['comissao_rg'])) {
                    echo h(number_format((float)$v['comissao_rg'], 2, ',', '.')) . " MT";
                } else {
                    echo h(number_format((float)$v['comissao'], 2, ',', '.')) . " MT";
                }
              ?>
            </td>

            <?php if ($hasLucro): ?>
              <td class="text-end"><?php echo h(number_format((float)$v['lucro'], 2, ',', '.')); ?> MT</td>
            <?php endif; ?>

            <td>
              <?php
                $st = $v['status'];
                $badge = 'secondary';
                if ($st === 'PENDENTE') $badge = 'warning';
                if ($st === 'PAGO') $badge = 'success';
                if ($st === 'CANCELADO') $badge = 'danger';
              ?>
              <span class="badge text-bg-<?php echo h($badge); ?>"><?php echo h($st); ?></span>

              <?php if ($hasApv && isset($v['precisa_aprovacao']) && (int)$v['precisa_aprovacao'] === 1): ?>
                <span class="badge text-bg-dark ms-1">Precisa aprovacao</span>
              <?php endif; ?>
            </td>

            <td class="text-end">
              <div class="rg-row-actions justify-content-end">
                <a class="btn btn-sm btn-outline-primary" href="<?= h(url('admin/vendas/venda_detalhe.php?id=' . (int)$v['id'])) ?>">Ver</a>
                <a class="btn btn-sm btn-outline-secondary" href="<?= h(url('app/modules/finance/custos.php?venda_id=' . (int)$v['id'])) ?>">Custos</a>

                <?php if (function_exists("recalcular_venda") && $st !== 'CANCELADO'): ?>
                  <form class="d-inline" method="POST" action="">
                    <input type="hidden" name="id" value="<?php echo (int)$v['id']; ?>">
                    <input type="hidden" name="acao" value="recalcular">
                    <input type="hidden" name="token" value="<?php echo h($_SESSION['csrf_token']); ?>">
                    <button class="btn btn-sm btn-outline-dark" type="submit">Recalcular</button>
                  </form>
                <?php endif; ?>

                <?php if ($st === 'PENDENTE'): ?>
                  <form class="d-inline" method="POST" action="">
                    <input type="hidden" name="id" value="<?php echo (int)$v['id']; ?>">
                    <input type="hidden" name="acao" value="pagar">
                    <input type="hidden" name="token" value="<?php echo h($_SESSION['csrf_token']); ?>">
                    <button class="btn btn-sm btn-success" type="submit" onclick="return confirm('Marcar esta venda como PAGA?');">
                      Marcar Pago
                    </button>
                  </form>

                  <form class="d-inline" method="POST" action="">
                    <input type="hidden" name="id" value="<?php echo (int)$v['id']; ?>">
                    <input type="hidden" name="acao" value="cancelar">
                    <input type="hidden" name="token" value="<?php echo h($_SESSION['csrf_token']); ?>">
                    <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Cancelar esta venda?');">
                      Cancelar
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
    <div class="text-muted small">
      Total: <?php echo h($totalRows); ?> venda(s) | Pagina <?php echo h($page); ?> de <?php echo h($totalPages); ?>
    </div>

    <nav>
      <ul class="pagination mb-0">
        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
          <a class="page-link" href="?<?php echo h(buildQuery(['page' => $page - 1])); ?>">Anterior</a>
        </li>
        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
          <a class="page-link" href="?<?php echo h(buildQuery(['page' => $page + 1])); ?>">Proxima</a>
        </li>
      </ul>
    </nav>
  </div>
</div>
