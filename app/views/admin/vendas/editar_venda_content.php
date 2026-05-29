<div class="sales-page">
  <div class="rg-panel">
    <div class="rg-panel-body rg-section-head">
      <div>
        <h2>Editar Venda #<?php echo (int)$v["id"]; ?></h2>
        <p>Cliente: <?php echo h($v["cliente_nome"]); ?> | <?php echo h($v["cliente_telefone"]); ?></p>
      </div>
      <div class="rg-page-actions">
        <a class="btn btn-light" href="<?= h(url('admin/vendas/venda_detalhe.php?id=' . (int)$v['id'])) ?>">Voltar ao detalhe</a>
        <a class="btn btn-dark" href="<?= h(url('app/modules/finance/custos.php?venda_id=' . (int)$v['id'])) ?>">Custos</a>
      </div>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?php echo h($flash["type"]); ?> alert-dismissible fade show" role="alert">
      <?php echo h($flash["msg"]); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if ($warningLucro): ?>
    <div class="alert alert-warning"><?php echo h($warningLucro); ?></div>
  <?php endif; ?>

  <div class="rg-panel">
    <div class="rg-panel-body">
      <form method="POST" class="row g-3">
        <input type="hidden" name="token" value="<?php echo h($_SESSION["csrf_token"]); ?>">
        <input type="hidden" name="id" value="<?php echo (int)$v["id"]; ?>">

        <div class="col-md-4">
          <label class="form-label">Marca</label>
          <input class="form-control" name="marca" required value="<?php echo h($v["marca"]); ?>">
        </div>

        <div class="col-md-4">
          <label class="form-label">Modelo</label>
          <input class="form-control" name="modelo" required value="<?php echo h($v["modelo"]); ?>">
        </div>

        <div class="col-md-4">
          <label class="form-label">Ano</label>
          <input class="form-control" type="number" name="ano" required value="<?php echo h($v["ano"]); ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label">Data da venda</label>
          <input class="form-control" type="date" name="data_venda" value="<?php echo h($v["data_venda"]); ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label">Forma de pagamento</label>
          <select class="form-select" name="forma_pagamento" required>
            <?php
              $fp = $v["forma_pagamento"] ?? "";
              $ops = ["MPESA"=>"M-Pesa","E-MOLA"=>"E-Mola","TRANSFERENCIA"=>"Transferencia","CASH"=>"Cash","OUTRO"=>"Outro"];
              echo '<option value="">-- Selecionar --</option>';
              foreach ($ops as $k => $label) {
                $sel = ($fp === $k) ? "selected" : "";
                echo '<option value="' . h($k) . '" ' . $sel . '>' . h($label) . '</option>';
              }
            ?>
          </select>
        </div>

        <?php if ($hasValorVenda): ?>
          <div class="col-md-6">
            <label class="form-label">Valor de venda (MT)</label>
            <input class="form-control" type="number" step="0.01" name="valor_venda"
                   value="<?php echo h($v["valor_venda"] ?? 0); ?>" required>
          </div>
        <?php else: ?>
          <div class="col-md-6">
            <label class="form-label">Valor do carro (MT) (modelo antigo)</label>
            <input class="form-control" type="number" step="0.01" name="valor_carro"
                   value="<?php echo h($v["valor_carro"] ?? 0); ?>" required>
          </div>
        <?php endif; ?>

        <?php if ($hasValorProp): ?>
          <div class="col-md-6">
            <label class="form-label">Valor pago ao proprietario (MT)</label>
            <input class="form-control" type="number" step="0.01" name="valor_proprietario"
                   value="<?php echo h($v["valor_proprietario"] ?? 0); ?>">
          </div>
        <?php endif; ?>

        <?php if ($hasPercVend || $hasPercRG || $hasLucroMin): ?>
          <div class="col-12"><hr></div>
          <div class="col-12"><div class="fw-semibold">Regras de comissao (opcional)</div></div>

          <?php if ($hasPercVend): ?>
            <div class="col-md-4">
              <label class="form-label">% Vendedor</label>
              <input class="form-control" type="number" step="0.01" name="perc_vendedor"
                     value="<?php echo h($v["perc_vendedor"] ?? 20); ?>">
            </div>
          <?php endif; ?>

          <?php if ($hasPercRG): ?>
            <div class="col-md-4">
              <label class="form-label">% RG</label>
              <input class="form-control" type="number" step="0.01" name="perc_rg"
                     value="<?php echo h($v["perc_rg"] ?? 80); ?>">
            </div>
          <?php endif; ?>

          <?php if ($hasLucroMin): ?>
            <div class="col-md-4">
              <label class="form-label">Lucro minimo (MT)</label>
              <input class="form-control" type="number" step="0.01" name="lucro_minimo"
                     value="<?php echo h($v["lucro_minimo"] ?? 30000); ?>">
            </div>
          <?php endif; ?>
        <?php endif; ?>

        <?php if ($temVendedor || $temCaptador): ?>
          <div class="col-12"><hr></div>
          <div class="col-12"><div class="fw-semibold">Equipa (opcional)</div></div>

          <?php if ($temVendedor): ?>
            <div class="col-md-6">
              <label class="form-label">Vendedor</label>
              <select class="form-select" name="vendedor_id">
                <option value="">-- Nenhum --</option>
                <?php foreach ($pessoas as $p):
                  $sel = ((int)($v["vendedor_id"] ?? 0) === (int)$p["id"]) ? "selected" : "";
                ?>
                  <option value="<?php echo (int)$p["id"]; ?>" <?php echo $sel; ?>>
                    <?php echo h($p["nome"]); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>

          <?php if ($temCaptador): ?>
            <div class="col-md-6">
              <label class="form-label">Captador</label>
              <select class="form-select" name="captador_id">
                <option value="">-- Nenhum --</option>
                <?php foreach ($pessoas as $p):
                  $sel = ((int)($v["captador_id"] ?? 0) === (int)$p["id"]) ? "selected" : "";
                ?>
                  <option value="<?php echo (int)$p["id"]; ?>" <?php echo $sel; ?>>
                    <?php echo h($p["nome"]); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>
        <?php endif; ?>

        <div class="col-12"><hr></div>

        <?php if ($hasLucro): ?>
          <div class="col-md-4">
            <div class="text-muted small">Total custos</div>
            <div class="fw-semibold"><?php echo $hasTCustos ? h(money($v["total_custos"] ?? 0)) : "-"; ?></div>
          </div>
          <div class="col-md-4">
            <div class="text-muted small">Lucro</div>
            <div class="fw-semibold"><?php echo h(money($v["lucro"] ?? 0)); ?></div>
          </div>
          <div class="col-md-4">
            <div class="text-muted small">Status</div>
            <div class="fw-semibold">
              <?php echo h($v["status"]); ?>
              <?php if ($hasApv && (int)($v["precisa_aprovacao"] ?? 0) === 1): ?>
                <span class="badge text-bg-dark ms-1">Precisa aprovacao</span>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <div class="col-12 d-grid">
          <button class="btn btn-success btn-lg" type="submit">Salvar alteracoes</button>
        </div>
      </form>
    </div>
  </div>
</div>
