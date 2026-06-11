<?php
if (!isset($parceiro)) {
    require_once __DIR__ . '/../../app/core/bootstrap.php';
    require_admin();

    $tipos = ['captador' => 'Captador', 'revendedor' => 'Revendedor', 'importacao' => 'Importacao', 'marketing' => 'Marketing', 'fornecedor' => 'Fornecedor', 'outro' => 'Outro'];
    $estados = ['ativo' => 'Ativo', 'inativo' => 'Inativo', 'pendente' => 'Pendente'];
    $niveis = ['principal' => 'Principal', 'regular' => 'Regular', 'comunidade' => 'Comunidade'];
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        redirect_to('admin/parceiros/index.php');
    }

    $stmt = mysqli_prepare($conexao, "SELECT * FROM parceiros WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $parceiro = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$parceiro) {
        $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Parceiro nao encontrado.'];
        redirect_to('admin/parceiros/index.php');
    }

    $pageTitle = 'Editar Parceiro';
    $pageSubtitle = 'Atualizar dados da rede de parceiros';
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
                            <div>
                                <h2>Editar Parceiro</h2>
                                <p><?= h($parceiro['nome']) ?></p>
                            </div>
                            <div class="rg-page-actions">
                                <a class="btn btn-light" href="<?= h(url('admin/parceiros/index.php')) ?>">Parceiros</a>
                                <a class="btn btn-primary" href="<?= h(url('admin/parceiros/detalhe.php?id=' . (int)$parceiro['id'])) ?>">Ver detalhe</a>
                            </div>
                        </div>
                    </div>

                    <div class="rg-panel">
                        <div class="rg-panel-body">
                            <form method="POST" action="<?= h(url('admin/parceiros/salvar.php')) ?>" class="rg-form-grid">
                                <?= csrf_input() ?>
                                <input type="hidden" name="id" value="<?= h((int)$parceiro['id']) ?>">
<?php } ?>
                                <div class="rg-field-full">
                                    <label for="nome">Nome</label>
                                    <input type="text" id="nome" name="nome" class="form-control" value="<?= h($parceiro['nome'] ?? '') ?>" required maxlength="160">
                                </div>
                                <div>
                                    <label for="telefone">Telefone</label>
                                    <input type="text" id="telefone" name="telefone" class="form-control" value="<?= h($parceiro['telefone'] ?? '') ?>" maxlength="40">
                                </div>
                                <div>
                                    <label for="whatsapp">WhatsApp</label>
                                    <input type="text" id="whatsapp" name="whatsapp" class="form-control" value="<?= h($parceiro['whatsapp'] ?? '') ?>" maxlength="40">
                                </div>
                                <div>
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" class="form-control" value="<?= h($parceiro['email'] ?? '') ?>" maxlength="160">
                                </div>
                                <div>
                                    <label for="cidade">Cidade</label>
                                    <input type="text" id="cidade" name="cidade" class="form-control" value="<?= h($parceiro['cidade'] ?? '') ?>" maxlength="100">
                                </div>
                                <div>
                                    <label for="tipo">Tipo</label>
                                    <select id="tipo" name="tipo" class="form-select" required>
                                        <?php foreach ($tipos as $valor => $label): ?>
                                            <option value="<?= h($valor) ?>" <?= ($parceiro['tipo'] ?? '') === $valor ? 'selected' : '' ?>><?= h($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="origem">Origem</label>
                                    <input type="text" id="origem" name="origem" class="form-control" value="<?= h($parceiro['origem'] ?? '') ?>" maxlength="120">
                                </div>
                                <div>
                                    <label for="estado">Estado</label>
                                    <select id="estado" name="estado" class="form-select" required>
                                        <?php foreach ($estados as $valor => $label): ?>
                                            <option value="<?= h($valor) ?>" <?= ($parceiro['estado'] ?? '') === $valor ? 'selected' : '' ?>><?= h($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="nivel">Nivel</label>
                                    <select id="nivel" name="nivel" class="form-select" required>
                                        <?php foreach ($niveis as $valor => $label): ?>
                                            <option value="<?= h($valor) ?>" <?= ($parceiro['nivel'] ?? '') === $valor ? 'selected' : '' ?>><?= h($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="comissao_padrao">Comissao padrao</label>
                                    <input type="number" step="0.01" min="0" id="comissao_padrao" name="comissao_padrao" class="form-control" value="<?= h($parceiro['comissao_padrao'] ?? '') ?>">
                                </div>
                                <div class="rg-field-full">
                                    <label for="notas">Notas</label>
                                    <textarea id="notas" name="notas" class="form-control"><?= h($parceiro['notas'] ?? '') ?></textarea>
                                </div>
                                <div class="rg-field-full rg-form-actions">
                                    <a class="btn btn-light" href="<?= h(url('admin/parceiros/index.php')) ?>">Cancelar</a>
                                    <button type="submit" class="btn btn-primary">Guardar Parceiro</button>
                                </div>
<?php if (isset($id)): ?>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
    <?php require BASE_PATH . '/app/views/layouts/admin_footer.php'; ?>
<?php endif; ?>
