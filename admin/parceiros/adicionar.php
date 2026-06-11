<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

$tipos = ['captador' => 'Captador', 'revendedor' => 'Revendedor', 'importacao' => 'Importacao', 'marketing' => 'Marketing', 'fornecedor' => 'Fornecedor', 'outro' => 'Outro'];
$estados = ['ativo' => 'Ativo', 'inativo' => 'Inativo', 'pendente' => 'Pendente'];
$niveis = ['principal' => 'Principal', 'regular' => 'Regular', 'comunidade' => 'Comunidade'];
$parceiro = [
    'id' => '',
    'nome' => '',
    'telefone' => '',
    'whatsapp' => '',
    'email' => '',
    'cidade' => '',
    'tipo' => 'captador',
    'origem' => '',
    'estado' => 'ativo',
    'nivel' => 'regular',
    'comissao_padrao' => '',
    'notas' => '',
];

$pageTitle = 'Novo Parceiro';
$pageSubtitle = 'Adicionar contacto estrategico a rede RG Auto Sales';
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
                            <h2>Novo Parceiro</h2>
                            <p>Dados comerciais, contactos e classificacao da parceria.</p>
                        </div>
                        <div class="rg-page-actions">
                            <a class="btn btn-light" href="<?= h(url('admin/parceiros/index.php')) ?>">Parceiros</a>
                        </div>
                    </div>
                </div>

                <div class="rg-panel">
                    <div class="rg-panel-body">
                        <form method="POST" action="<?= h(url('admin/parceiros/salvar.php')) ?>" class="rg-form-grid">
                            <?= csrf_input() ?>
                            <input type="hidden" name="id" value="">
                            <?php require __DIR__ . '/editar.php'; ?>
                        </form>
                    </div>
                </div>
            </div>
        </section>
<?php require BASE_PATH . '/app/views/layouts/admin_footer.php'; ?>
