<div class="sales-page">
  <div class="rg-panel">
    <div class="rg-panel-body rg-section-head">
      <div>
        <h2>Detalhe da Venda #<?php echo (int)$venda['id']; ?></h2>
        <p>Modelo: lucro real, comissoes apenas quando PAGO.</p>
      </div>
      <div class="rg-page-actions">
        <a class="btn btn-light" href="<?= h(url('admin/vendas/vendas.php')) ?>">Voltar</a>
        <a class="btn btn-primary" href="<?= h(url('admin/vendas/nova_venda.php')) ?>">Nova venda</a>
        <a class="btn btn-dark" href="<?= h(url('app/modules/finance/custos.php?venda_id=' . (int)$venda['id'])) ?>">Custos da venda</a>

        <?php if ($venda['status'] === 'PAGO'): ?>
          <a class="btn btn-success" href="<?= h(url('app/modules/finance/recibo.php?id=' . (int)$venda['id'])) ?>" target="_blank" rel="noopener">Recibo PDF</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?php echo h($flash['type']); ?> alert-dismissible fade show" role="alert">
      <?php echo h($flash['msg']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-8">
      <div class="rg-panel">
        <div class="rg-panel-body">
          <div class="d-flex justify-content-between align-items-start gap-3 mb-3 flex-wrap">
            <div>
              <div class="fw-semibold">Informacoes da venda</div>
              <?php if ($precisaApv === 1 && !empty($motivoApv)): ?>
                <div class="text-muted small mt-1"><?php echo h($motivoApv); ?></div>
              <?php endif; ?>
            </div>

            <?php
              $st = $venda['status'];
              $badge = 'secondary';
              if ($st === 'PENDENTE') $badge = 'warning';
              if ($st === 'PAGO') $badge = 'success';
              if ($st === 'CANCELADO') $badge = 'danger';
            ?>
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span class="badge text-bg-<?php echo h($badge); ?>"><?php echo h($st); ?></span>
              <?php if ($precisaApv === 1): ?>
                <span class="badge text-bg-dark">Precisa aprovacao</span>
              <?php endif; ?>
            </div>
          </div>

          <?php if ($precisaApv === 1 && !empty($motivoApv)): ?>
            <div class="alert alert-warning py-2 mb-3">
              <strong>Precisa aprovacao:</strong> <?php echo h($motivoApv); ?>
            </div>
          <?php endif; ?>

          <div class="rg-detail-grid">
            <div class="rg-detail-item">
              <span class="label">Data da venda</span>
              <span class="value"><?php echo h($venda['data_venda']); ?></span>
            </div>

            <div class="rg-detail-item">
              <span class="label">Criado em</span>
              <span class="value"><?php echo h($venda['criado_em']); ?></span>
              <?php if ($hasAtualizado && !empty($venda['atualizado_em'])): ?>
                <small class="text-muted">Atualizado: <?php echo h($venda['atualizado_em']); ?></small>
              <?php endif; ?>
              <?php if ($hasAprovEm && !empty($venda['aprovado_em'])): ?>
                <small class="text-muted d-block">Aprovado em: <?php echo h($venda['aprovado_em']); ?></small>
              <?php endif; ?>
            </div>

            <div class="rg-detail-item">
              <span class="label">Carro</span>
              <span class="value"><?php echo h($venda['marca']); ?> <?php echo h($venda['modelo']); ?> (<?php echo h($venda['ano']); ?>)</span>
            </div>

            <div class="rg-detail-item">
              <span class="label">Valor de venda</span>
              <span class="value"><?php echo h(money($valorVendaShow)); ?></span>
            </div>

            <div class="rg-detail-item">
              <span class="label">Valor pago ao proprietario</span>
              <span class="value"><?php echo h(money($valorPropShow)); ?></span>
            </div>

            <div class="rg-detail-item">
              <span class="label">Total de custos</span>
              <span class="value"><?php echo h(money($totalCustos)); ?></span>
            </div>

            <div class="rg-detail-item">
              <span class="label">Lucro real</span>
              <span class="value"><?php echo h(money($lucroShow)); ?></span>
              <small class="text-muted">Formula: valor_venda - valor_proprietario - custos</small>
            </div>

            <div class="rg-detail-item">
              <span class="label">Percentuais</span>
              <span class="value">
                Vendedor: <?php echo h(number_format($percVend, 2, ',', '.')); ?>% |
                RG: <?php echo h(number_format($percRG, 2, ',', '.')); ?>%
                <?php if ($hasParceiro): ?>
                  | Parceiro: <?php echo h(number_format($percParc, 2, ',', '.')); ?>%
                <?php endif; ?>
              </span>
              <small class="text-muted">So comissiona quando status = PAGO e lucro &gt; 0.</small>
            </div>

            <div class="rg-detail-item">
              <span class="label">Comissao do vendedor</span>
              <span class="value"><?php echo h(money($comVendShow)); ?></span>
            </div>

            <?php if ($hasParceiro): ?>
              <div class="rg-detail-item">
                <span class="label">Comissao do parceiro</span>
                <span class="value"><?php echo h(money($comParcShow)); ?></span>
              </div>
            <?php endif; ?>

            <div class="rg-detail-item">
              <span class="label">Comissao da RG</span>
              <span class="value"><?php echo h(money($comRGShow)); ?></span>
            </div>

            <div class="rg-detail-item">
              <span class="label">Lucro minimo configurado</span>
              <span class="value"><?php echo h(money((float)$venda["lucro_minimo"])); ?></span>
            </div>
          </div>

          <hr class="my-4">

          <div class="d-flex flex-wrap gap-2">
            <?php if (function_exists("recalcular_venda") && $venda['status'] !== 'CANCELADO'): ?>
              <form method="POST" action="">
                <input type="hidden" name="token" value="<?php echo h($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="acao" value="recalcular">
                <button class="btn btn-outline-dark" type="submit">Recalcular</button>
              </form>
            <?php endif; ?>

            <?php if ($venda['status'] === 'PENDENTE'): ?>
              <?php if ($precisaApv === 1): ?>
                <form method="POST" action="">
                  <input type="hidden" name="token" value="<?php echo h($_SESSION['csrf_token']); ?>">
                  <input type="hidden" name="acao" value="aprovar">
                  <button class="btn btn-dark" type="submit" onclick="return confirm('Aprovar esta venda para permitir pagamento?');">
                    Aprovar
                  </button>
                </form>
              <?php endif; ?>

              <form method="POST" action="">
                <input type="hidden" name="token" value="<?php echo h($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="acao" value="pagar">
                <button
                  class="btn btn-success"
                  type="submit"
                  <?php echo ($precisaApv === 1) ? 'disabled' : ''; ?>
                  onclick="return confirm('Marcar como PAGA?');"
                  title="<?php echo ($precisaApv === 1) ? ('Bloqueado: ' . h($motivoApv ?: 'Precisa aprovacao antes de pagar')) : ''; ?>"
                >
                  Marcar Pago
                </button>
              </form>

              <form method="POST" action="">
                <input type="hidden" name="token" value="<?php echo h($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="acao" value="cancelar">
                <button class="btn btn-outline-danger" type="submit" onclick="return confirm('Cancelar esta venda?');">
                  Cancelar
                </button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="rg-panel">
        <div class="rg-panel-body">
          <h5 class="fw-bold mb-3">Cliente</h5>
          <div class="rg-stack">
            <div class="rg-detail-item">
              <span class="label">Nome</span>
              <span class="value"><?php echo h($venda['cliente_nome']); ?></span>
            </div>
            <div class="rg-detail-item">
              <span class="label">Telefone</span>
              <span class="value"><?php echo h($venda['cliente_telefone']); ?></span>
            </div>
            <div class="rg-detail-item">
              <span class="label">Email</span>
              <span class="value"><?php echo h($venda['cliente_email']); ?></span>
            </div>
          </div>

          <div class="mt-3">
            <a class="btn btn-outline-primary w-100" href="<?= h(url('admin/dashboard.php')) ?>">Voltar ao Dashboard</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
