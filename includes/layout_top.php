<?php
require_once __DIR__ . '/../app/core/bootstrap.php';

$paginaAtual = basename($_SERVER['PHP_SELF'] ?? '');
$caminhoAtual = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$user = current_user();

function menuAtivo($arquivo) {
    global $paginaAtual;
    return $paginaAtual === $arquivo ? 'active' : '';
}

function menuAtivoPath($path) {
    global $caminhoAtual;
    return str_ends_with($caminhoAtual, '/' . ltrim($path, '/')) ? 'active' : '';
}

function tituloPagina($pagina) {
    global $caminhoAtual;

    if (str_ends_with($caminhoAtual, '/admin/crm/dashboard.php')) {
        return 'CRM Dashboard';
    }

    $titulos = [
        'dashboard.php' => 'Dashboard',
        'listar_carros.php' => 'Carros',
        'editar_carro.php' => 'Editar Carro',
        'adicionar_carro.php' => 'Adicionar Carro',
        'vendas.php' => 'Vendas',
        'nova_venda.php' => 'Nova Venda',
        'venda_detalhe.php' => 'Detalhes da Venda',
        'clientes.php' => 'Clientes',
        'leads.php' => 'Leads',
        'funil.php' => 'CRM',
        'inbox.php' => 'CRM Inbox',
        'painel_inteligente.php' => 'Painel Inteligente',
        'custos.php' => 'Custos',
        'config.php' => 'Configuracoes',
        'vendedores_pedidos.php' => 'Pedidos de Vendedores',
        'relatorio_vendedores.php' => 'Relatorios',
    ];

    return $titulos[$pagina] ?? 'Admin RG Auto';
}

$tituloAtual = tituloPagina($paginaAtual);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title><?= h($tituloAtual) ?> | RG Auto Sales</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        *{box-sizing:border-box}
        body{margin:0;font-family:Arial,sans-serif;background:#f4f6f9;color:#172033}
        a{text-decoration:none}
        .admin-layout{display:flex;min-height:100vh}
        .sidebar{width:276px;background:#01203f;color:#fff;padding:22px 16px;position:sticky;top:0;height:100vh;overflow-y:auto}
        .brand{padding-bottom:18px;margin-bottom:18px;border-bottom:1px solid rgba(255,255,255,.14)}
        .brand-badge{display:inline-flex;padding:6px 10px;border-radius:999px;background:rgba(0,174,239,.16);color:#7dd3fc;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;margin-bottom:10px}
        .brand h2{margin:0;font-size:22px;color:#fff}
        .brand p{margin:6px 0 0;color:rgba(255,255,255,.72);font-size:13px;line-height:1.45}
        .menu-title{font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:rgba(255,255,255,.5);margin:18px 10px 8px;font-weight:800}
        .nav{display:flex;flex-direction:column;gap:6px}
        .nav a{display:flex;align-items:center;justify-content:space-between;padding:11px 13px;border-radius:10px;color:#dbeafe;font-weight:800;transition:.18s}
        .nav a:hover{background:rgba(255,255,255,.08);color:#fff}
        .nav a.active{background:#00aeef;color:#fff;box-shadow:0 8px 20px rgba(0,174,239,.25)}
        .sidebar-footer{margin-top:22px;padding-top:18px;border-top:1px solid rgba(255,255,255,.14)}
        .sidebar-footer a{display:block;text-align:center;color:#fff;background:rgba(255,255,255,.09);border-radius:10px;padding:11px 13px;font-weight:800}
        .main{flex:1;min-width:0;display:flex;flex-direction:column}
        .topbar{background:#fff;border-bottom:1px solid #e5e7eb;padding:16px 24px;display:flex;justify-content:space-between;align-items:center;gap:16px;position:sticky;top:0;z-index:20}
        .topbar-left h1{margin:0;font-size:24px;color:#111827}
        .topbar-left p{margin:5px 0 0;color:#667085;font-size:13px}
        .topbar-right{display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end}
        .top-btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 13px;border-radius:9px;font-weight:800;font-size:14px}
        .top-btn.primary{background:#00aeef;color:#fff}
        .top-btn.dark{background:#111827;color:#fff}
        .top-btn.light{background:#fff;color:#111827;border:1px solid #d0d5dd}
        .top-btn.danger{background:#dc3545;color:#fff}
        .content{padding:24px;flex:1}
        .page-card,.card{background:#fff;border-radius:12px;padding:18px;box-shadow:0 4px 18px rgba(16,24,40,.08);margin-bottom:18px}
        table{width:100%;border-collapse:collapse;background:#fff}
        th,td{padding:10px;border-bottom:1px solid #e5e7eb;text-align:left;font-size:14px}
        th{background:#f8fafc;color:#344054;font-weight:800}
        .btn{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:9px;padding:9px 12px;font-weight:800;background:#00aeef;color:#fff;cursor:pointer}
        .btn:hover{background:#01203f;color:#fff}
        @media(max-width:920px){
            .admin-layout{flex-direction:column}
            .sidebar{width:100%;height:auto;position:relative}
            .topbar{position:relative;align-items:flex-start;flex-direction:column;padding:16px}
            .topbar-right{justify-content:flex-start}
            .content{padding:16px}
        }
    </style>
    <link rel="stylesheet" href="<?= h(asset('css/admin-modern.css')) ?>">
</head>
<body>
<div class="admin-layout">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-badge">RG Auto Admin</div>
            <h2>RG Auto Sales</h2>
            <p>Operacao comercial, CRM, stock e vendas num unico painel.</p>
        </div>

        <div class="menu-title">Principal</div>
        <nav class="nav">
            <a href="<?= h(url('admin/dashboard.php')) ?>" class="<?= menuAtivoPath('admin/dashboard.php') ?>">Dashboard</a>
            <a href="<?= h(url('admin/carros/listar_carros.php')) ?>" class="<?= menuAtivo('listar_carros.php') ?>">Carros</a>
            <a href="<?= h(url('admin/vendas/vendas.php')) ?>" class="<?= menuAtivo('vendas.php') ?>">Vendas</a>
            <a href="<?= h(url('admin/clientes/clientes.php')) ?>" class="<?= menuAtivo('clientes.php') ?>">Clientes</a>
            <a href="<?= h(url('admin/leads/leads.php')) ?>" class="<?= menuAtivo('leads.php') ?>">Leads</a>
            <a href="<?= h(url('admin/crm/dashboard.php')) ?>" class="<?= menuAtivoPath('admin/crm/dashboard.php') ?>">CRM Dashboard</a>
            <a href="<?= h(url('admin/crm/inbox.php')) ?>" class="<?= menuAtivoPath('admin/crm/inbox.php') ?>">CRM Inbox</a>
            <a href="<?= h(url('admin/financeiro/dashboard_financeiro.php')) ?>" class="<?= menuAtivo('dashboard_financeiro.php') ?>">Financeiro</a>
        </nav>

        <div class="menu-title">Acao Comercial</div>
        <nav class="nav">
            <a href="<?= h(url('admin/carros/adicionar_carro.php')) ?>" class="<?= menuAtivo('adicionar_carro.php') ?>">Adicionar Carro</a>
            <a href="<?= h(url('admin/vendas/nova_venda.php')) ?>" class="<?= menuAtivo('nova_venda.php') ?>">Nova Venda</a>
            <a href="<?= h(url('admin/painel_inteligente.php')) ?>" class="<?= menuAtivo('painel_inteligente.php') ?>">Painel Inteligente</a>
            <a href="<?= h(url('admin/vendas/vendedores_pedidos.php')) ?>" class="<?= menuAtivo('vendedores_pedidos.php') ?>">Pedidos de Vendedores</a>
            <a href="<?= h(url('admin/config.php')) ?>" class="<?= menuAtivo('config.php') ?>">Configuracoes</a>
            <a href="<?= h(url('admin/relatorio_vendedores.php')) ?>" class="<?= menuAtivo('relatorio_vendedores.php') ?>">Relatorios</a>
        </nav>

        <div class="sidebar-footer">
            <a href="<?= h(public_url('index.php')) ?>" target="_blank">Ver site publico</a>
        </div>
    </aside>

    <main class="main">
        <div class="topbar">
            <div class="topbar-left">
                <h1><?= h($tituloAtual) ?></h1>
                <p><?= h($user['nome'] ?? 'Utilizador') ?> | Sistema interno RG Auto Sales</p>
            </div>
            <div class="topbar-right">
                <a href="<?= h(url('admin/carros/adicionar_carro.php')) ?>" class="top-btn primary">Adicionar carro</a>
                <a href="<?= h(url('admin/vendas/nova_venda.php')) ?>" class="top-btn dark">Nova venda</a>
                <a href="<?= h(public_url('index.php')) ?>" target="_blank" class="top-btn light">Ver site</a>
                <a href="<?= h(url('auth/logout.php')) ?>" class="top-btn danger">Sair</a>
            </div>
        </div>

        <div class="content">
