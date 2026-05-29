<?php
$user = current_user() ?? [];
$pageTitle = $pageTitle ?? 'Admin';
$pageSubtitle = $pageSubtitle ?? 'Sistema interno RG Auto Sales';
?>
<header class="rg-admin-topbar">
    <button class="rg-admin-menu-toggle" type="button" data-admin-sidebar-toggle aria-label="Abrir menu">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <div class="rg-admin-page-title">
        <h1><?= h($pageTitle) ?></h1>
        <p><?= h($pageSubtitle) ?></p>
    </div>

    <div class="rg-admin-topbar__actions">
        <a href="<?= h(url('admin/carros/adicionar_carro.php')) ?>" class="rg-admin-action rg-admin-action--primary">Adicionar carro</a>
        <a href="<?= h(url('admin/vendas/nova_venda.php')) ?>" class="rg-admin-action rg-admin-action--dark">Nova venda</a>
        <div class="rg-admin-user">
            <strong><?= h($user['nome'] ?? 'Admin') ?></strong>
            <small><?= h($user['role'] ?? 'admin') ?></small>
        </div>
        <a href="<?= h(url('auth/logout.php')) ?>" class="rg-admin-action rg-admin-action--light">Sair</a>
    </div>
</header>

