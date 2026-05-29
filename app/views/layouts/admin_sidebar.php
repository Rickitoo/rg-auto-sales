<?php
if (!function_exists('rg_admin_nav_active')) {
    function rg_admin_nav_active(string $path): string
    {
        $currentPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        return str_ends_with($currentPath, '/' . ltrim($path, '/')) ? 'is-active' : '';
    }
}
?>
<aside class="rg-admin-sidebar" id="adminSidebar" aria-label="Menu administrativo">
    <div class="rg-admin-brand">
        <span class="rg-admin-brand__eyebrow">RG Auto Admin</span>
        <strong>RG Auto Sales</strong>
        <small>CRM, stock, vendas e financeiro.</small>
    </div>

    <nav class="rg-admin-nav">
        <span class="rg-admin-nav__label">Principal</span>
        <a href="<?= h(url('admin/dashboard.php')) ?>" class="<?= rg_admin_nav_active('admin/dashboard.php') ?>">Dashboard</a>
        <a href="<?= h(url('admin/clientes/clientes.php')) ?>" class="<?= rg_admin_nav_active('admin/clientes/clientes.php') ?>">Clientes</a>
        <a href="<?= h(url('admin/leads/leads.php')) ?>" class="<?= rg_admin_nav_active('admin/leads/leads.php') ?>">Leads</a>
        <a href="<?= h(url('admin/importacoes/index.php')) ?>" class="<?= rg_admin_nav_active('admin/importacoes/index.php') ?>">Importacoes</a>
        <a href="<?= h(url('admin/crm/dashboard.php')) ?>" class="<?= rg_admin_nav_active('admin/crm/dashboard.php') ?>">CRM Dashboard</a>
        <a href="<?= h(url('admin/crm/inbox.php')) ?>" class="<?= rg_admin_nav_active('admin/crm/inbox.php') ?>">CRM Inbox</a>

        <span class="rg-admin-nav__label">Operacao</span>
        <a href="<?= h(url('admin/carros/listar_carros.php')) ?>" class="<?= rg_admin_nav_active('admin/carros/listar_carros.php') ?>">Carros</a>
        <a href="<?= h(url('admin/carros/adicionar_carro.php')) ?>" class="<?= rg_admin_nav_active('admin/carros/adicionar_carro.php') ?>">Adicionar Carro</a>
        <a href="<?= h(url('admin/vendas/vendas.php')) ?>" class="<?= rg_admin_nav_active('admin/vendas/vendas.php') ?>">Vendas</a>
        <a href="<?= h(url('admin/vendas/nova_venda.php')) ?>" class="<?= rg_admin_nav_active('admin/vendas/nova_venda.php') ?>">Nova Venda</a>
        <a href="<?= h(url('admin/financeiro/dashboard_financeiro.php')) ?>" class="<?= rg_admin_nav_active('admin/financeiro/dashboard_financeiro.php') ?>">Financeiro</a>

        <span class="rg-admin-nav__label">Gestao</span>
        <a href="<?= h(url('admin/painel_inteligente.php')) ?>" class="<?= rg_admin_nav_active('admin/painel_inteligente.php') ?>">Painel Inteligente</a>
        <a href="<?= h(url('admin/vendas/vendedores_pedidos.php')) ?>" class="<?= rg_admin_nav_active('admin/vendas/vendedores_pedidos.php') ?>">Pedidos de Vendedores</a>
        <a href="<?= h(url('admin/relatorio_vendedores.php')) ?>" class="<?= rg_admin_nav_active('admin/relatorio_vendedores.php') ?>">Relatorios</a>
        <a href="<?= h(url('admin/config.php')) ?>" class="<?= rg_admin_nav_active('admin/config.php') ?>">Configuracoes</a>
    </nav>

    <div class="rg-admin-sidebar__footer">
        <a href="<?= h(public_url('index.php')) ?>" target="_blank" rel="noopener">Ver site publico</a>
    </div>
</aside>
