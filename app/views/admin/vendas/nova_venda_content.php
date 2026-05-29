<div class="sales-page">
  <div class="rg-panel">
    <div class="rg-panel-body rg-section-head">
      <div>
        <h2>Nova Venda</h2>
        <p>Modelo novo: lucro real, vendedor 15%, RG restante, lucro minimo 30.000 MT.</p>
      </div>
      <div class="rg-page-actions">
        <a class="btn btn-light" href="<?= h(url('admin/vendas/vendas.php')) ?>">Vendas</a>
        <a class="btn btn-primary" href="<?= h(url('admin/dashboard.php')) ?>">Dashboard</a>
      </div>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?php echo h($flash['type']); ?> alert-dismissible fade show" role="alert">
      <?php echo h($flash['msg']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="rg-panel">
    <div class="rg-panel-body">
      <form class="row g-2 align-items-end mb-4" method="GET" action="<?= h(url('admin/vendas/nova_venda.php')) ?>">
        <div class="col-md-8">
          <label class="form-label">Selecionar cliente</label>
          <select class="form-select" name="cliente_id" required>
            <option value="">-- Escolher --</option>
            <?php foreach ($clientes as $c): ?>
              <option value="<?php echo (int)$c['id']; ?>"
                <?php echo ($cliente_pre > 0 && (int)$c['id'] === $cliente_pre) ? 'selected' : ''; ?>>
                <?php echo h($c['nome']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4 d-grid">
          <button class="btn btn-dark" type="submit">Carregar dados</button>
        </div>
      </form>

      <form class="row g-3" method="POST" action="">
        <input type="hidden" name="token" value="<?php echo h($_SESSION['csrf_token']); ?>">
        <input type="hidden" name="cliente_id" value="<?php echo (int)$clienteSelecionadoId; ?>">

        <div class="col-12">
          <div class="p-3 bg-light rounded-3">
            <div class="fw-semibold">Cliente selecionado</div>
            <?php if ($clienteDados): ?>
              <div class="text-muted small">
                <?php echo h($clienteDados['nome']); ?> |
                <?php echo h($clienteDados['telefone']); ?> |
                <?php echo h($clienteDados['email']); ?>
              </div>
            <?php else: ?>
              <div class="text-muted small">Selecione um cliente acima para continuar.</div>
            <?php endif; ?>
          </div>
        </div>

        <div class="col-md-4">
          <label class="form-label">Marca</label>
          <input type="text" class="form-control" name="marca" required
                 value="<?php echo h($clienteDados['marca'] ?? ''); ?>"
                 <?php echo $clienteDados ? '' : 'disabled'; ?>>
        </div>

        <div class="col-md-4">
          <label class="form-label">Modelo</label>
          <input type="text" class="form-control" name="modelo" required
                 value="<?php echo h($clienteDados['modelo'] ?? ''); ?>"
                 <?php echo $clienteDados ? '' : 'disabled'; ?>>
        </div>

        <div class="col-md-4">
          <label class="form-label">Ano</label>
          <input type="number" class="form-control" name="ano" required
                 value="<?php echo h($clienteDados['ano'] ?? ''); ?>"
                 <?php echo $clienteDados ? '' : 'disabled'; ?>>
        </div>

        <div class="col-md-6">
          <label class="form-label">Valor de venda (MT)</label>
          <input type="number" step="0.01" class="form-control" name="valor_venda" required
                 placeholder="Ex: 700000"
                 <?php echo $clienteDados ? '' : 'disabled'; ?>>
          <div class="form-text">Quanto o cliente final pagou.</div>
        </div>

        <div class="col-md-6">
          <label class="form-label">Valor pago ao proprietario (MT)</label>
          <input type="number" step="0.01" class="form-control" name="valor_proprietario"
                 placeholder="Ex: 650000"
                 <?php echo $clienteDados ? '' : 'disabled'; ?>>
          <div class="form-text">Se ainda nao pagaste, podes deixar 0 e ajustar depois.</div>
        </div>

        <div class="col-md-6">
          <label class="form-label">Data da venda</label>
          <input type="date" class="form-control" name="data_venda"
                 value="<?php echo h(date('Y-m-d')); ?>"
                 <?php echo $clienteDados ? '' : 'disabled'; ?>>
        </div>

        <div class="col-md-6">
          <label class="form-label">Forma de pagamento</label>
          <select class="form-select" name="forma_pagamento" required <?php echo $clienteDados ? '' : 'disabled'; ?>>
            <option value="">-- Selecionar --</option>
            <option value="MPESA">M-Pesa</option>
            <option value="E-MOLA">E-Mola</option>
            <option value="TRANSFERENCIA">Transferencia</option>
            <option value="CASH">Cash</option>
            <option value="OUTRO">Outro</option>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Vendedor (opcional)</label>
          <select class="form-select" name="vendedor_id" <?php echo $clienteDados ? '' : 'disabled'; ?>>
            <option value="">-- Nenhum --</option>
            <?php foreach ($pessoas as $p): ?>
              <option value="<?php echo (int)$p['id']; ?>"><?php echo h($p['nome']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Captador (opcional)</label>
          <select class="form-select" name="captador_id" <?php echo $clienteDados ? '' : 'disabled'; ?>>
            <option value="">-- Nenhum --</option>
            <?php foreach ($pessoas as $p): ?>
              <option value="<?php echo (int)$p['id']; ?>"><?php echo h($p['nome']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12 d-grid">
          <button class="btn btn-success btn-lg" type="submit" <?php echo $clienteDados ? '' : 'disabled'; ?>>
            Criar Venda
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
