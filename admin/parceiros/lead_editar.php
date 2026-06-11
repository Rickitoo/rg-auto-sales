<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

$statuses = ['novo' => 'Novo', 'contactado' => 'Contactado', 'negociacao' => 'Negociacao', 'fechado' => 'Fechado', 'perdido' => 'Perdido'];
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect_to('admin/parceiros/leads.php');
}

$stmt = mysqli_prepare($conexao, "SELECT * FROM parceiro_leads WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$lead = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$lead) {
    $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Lead de parceiro nao encontrado.'];
    redirect_to('admin/parceiros/leads.php');
}

$parceiros = [];
$res = mysqli_query($conexao, "SELECT id, nome FROM parceiros ORDER BY nome ASC");
while ($res && ($row = mysqli_fetch_assoc($res))) {
    $parceiros[] = $row;
}

$pageTitle = 'Editar Lead de Parceiro';
$pageSubtitle = 'Atualizar oportunidade manual da rede de parceiros';
$alerts = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);

require BASE_PATH . '/app/views/layouts/admin_header.php';
?>
<div class="rg-admin-shell">
    <?php require BASE_PATH . '/app/views/layouts/admin_sidebar.php'; ?>
    <main class="rg-admin-main">
        <?php require BASE_PATH . '/app/views/layouts/admin_topbar.php'; ?>
        <section class="rg-admin-content">
            <?php if (!empty($alerts)): ?><div class="rg-admin-alerts"><?php foreach ((array)$alerts as $alert): ?><div class="rg-admin-alert rg-admin-alert--<?= h($alert['type'] ?? 'info') ?>"><?= h($alert['message'] ?? '') ?></div><?php endforeach; ?></div><?php endif; ?>
            <div class="ops-page">
                <div class="rg-panel">
                    <div class="rg-panel-body rg-section-head">
                        <div><h2>Editar Lead de Parceiro</h2><p><?= h($lead['nome_lead'] ?: 'Lead #' . (int)$lead['id']) ?></p></div>
                        <div class="rg-page-actions"><a class="btn btn-light" href="<?= h(url('admin/parceiros/leads.php')) ?>">Leads de Parceiros</a><a class="btn btn-primary" href="<?= h(url('admin/parceiros/lead_detalhe.php?id=' . (int)$lead['id'])) ?>">Ver detalhe</a></div>
                    </div>
                </div>
                <div class="rg-panel">
                    <div class="rg-panel-body">
                        <form method="POST" action="<?= h(url('admin/parceiros/lead_salvar.php')) ?>" class="rg-form-grid">
                            <?= csrf_input() ?>
                            <input type="hidden" name="id" value="<?= h((int)$lead['id']) ?>">
                            <div class="rg-field-full">
                                <label for="parceiro_id">Parceiro</label>
                                <select id="parceiro_id" name="parceiro_id" class="form-select" required>
                                    <?php foreach ($parceiros as $parceiro): ?><option value="<?= h((int)$parceiro['id']) ?>" <?= (int)$lead['parceiro_id'] === (int)$parceiro['id'] ? 'selected' : '' ?>><?= h($parceiro['nome']) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div><label for="nome_lead">Nome do lead</label><input id="nome_lead" name="nome_lead" class="form-control" value="<?= h($lead['nome_lead']) ?>" required maxlength="160"></div>
                            <div><label for="telefone_lead">Telefone</label><input id="telefone_lead" name="telefone_lead" class="form-control" value="<?= h($lead['telefone_lead']) ?>" maxlength="40"></div>
                            <div><label for="modelo_interesse">Modelo de interesse</label><input id="modelo_interesse" name="modelo_interesse" class="form-control" value="<?= h($lead['modelo_interesse']) ?>" maxlength="160"></div>
                            <div><label for="origem">Origem</label><input id="origem" name="origem" class="form-control" value="<?= h($lead['origem']) ?>" maxlength="120"></div>
                            <div><label for="status">Status</label><select id="status" name="status" class="form-select"><?php foreach ($statuses as $value => $label): ?><option value="<?= h($value) ?>" <?= $lead['status'] === $value ? 'selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?></select></div>
                            <div><label for="valor_estimado">Valor estimado</label><input type="number" step="0.01" min="0" id="valor_estimado" name="valor_estimado" class="form-control" value="<?= h($lead['valor_estimado']) ?>"></div>
                            <div><label for="comissao_prevista">Comissao prevista</label><input type="number" step="0.01" min="0" id="comissao_prevista" name="comissao_prevista" class="form-control" value="<?= h($lead['comissao_prevista']) ?>"></div>
                            <div class="rg-field-full"><label for="observacoes">Observacoes</label><textarea id="observacoes" name="observacoes" class="form-control"><?= h($lead['observacoes']) ?></textarea></div>
                            <div class="rg-field-full rg-form-actions"><a class="btn btn-light" href="<?= h(url('admin/parceiros/leads.php')) ?>">Cancelar</a><button class="btn btn-primary" type="submit">Guardar Lead</button></div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
<?php require BASE_PATH . '/app/views/layouts/admin_footer.php'; ?>
