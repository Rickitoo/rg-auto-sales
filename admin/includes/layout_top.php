<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$paginaAtual = basename($_SERVER['PHP_SELF']);

function menuAtivo($arquivo) {
    global $paginaAtual;
    return $paginaAtual === $arquivo ? 'active' : '';
}

function tituloPagina($pagina) {
    $titulos = [
        'dashboard.php'          => 'Dashboard',
        'listar_carros.php'      => 'Carros',
        'editar_carro.php'       => 'Editar Carro',
        'gerir_fotos.php'        => 'Gerir Fotos',
        'adicionar_carro.php'    => 'Adicionar Carro',
        'carro_add.php'          => 'Adicionar Carro',
        'vendas.php'             => 'Vendas',
        'nova_venda.php'         => 'Nova Venda',
        'venda_detalhe.php'      => 'Detalhes da Venda',
        'clientes.php'           => 'Clientes',
        'leads.php'              => 'Leads',
        'funil.php'              => 'Funil de Vendas',
        'custos.php'             => 'Custos',
        'config.php'             => 'Configurações',
        'vendedores_pedidos.php' => 'Pedidos de Vendedores',
        'relatorio_vendedores.php' => 'Relatório'
    ];

    return $titulos[$pagina] ?? 'Admin RG Auto';
}

$tituloAtual = tituloPagina($paginaAtual);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($tituloAtual) ?> | Admin RG Auto Sales</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        *{box-sizing:border-box}

        body{
            margin:0;
            font-family:Arial, sans-serif;
            background:#f4f6f9;
            color:#1f2937;
        }

        .admin-layout{
            display:flex;
            min-height:100vh;
        }

        .sidebar{
            width:270px;
            background:linear-gradient(180deg, #01203f 0%, #00152b 100%);
            color:#fff;
            padding:22px 16px;
            position:sticky;
            top:0;
            height:100vh;
            overflow-y:auto;
        }

        .brand{
            margin-bottom:24px;
            padding-bottom:18px;
            border-bottom:1px solid rgba(255,255,255,.12);
        }

        .brand-badge{
            display:inline-block;
            padding:6px 10px;
            border-radius:999px;
            background:rgba(0,174,239,.16);
            color:#7dd3fc;
            font-size:11px;
            font-weight:bold;
            letter-spacing:.08em;
            text-transform:uppercase;
            margin-bottom:10px;
        }

        .brand h2{
            margin:0;
            font-size:22px;
            color:#fff;
        }

        .brand p{
            margin:6px 0 0;
            color:rgba(255,255,255,.72);
            font-size:13px;
            line-height:1.45;
        }

        .menu-title{
            font-size:12px;
            text-transform:uppercase;
            letter-spacing:.08em;
            color:rgba(255,255,255,.5);
            margin:18px 10px 8px;
            font-weight:bold;
        }

        .nav{
            display:flex;
            flex-direction:column;
            gap:6px;
        }

        .nav a{
            display:block;
            padding:12px 14px;
            border-radius:12px;
            text-decoration:none;
            color:#dbeafe;
            font-weight:bold;
            transition:.2s ease;
        }

        .nav a:hover{
            background:rgba(255,255,255,.08);
            color:#fff;
        }

        .nav a.active{
            background:#00aeef;
            color:#fff;
            box-shadow:0 8px 20px rgba(0, 174, 239, .28);
        }

        .sidebar-footer{
            margin-top:24px;
            padding-top:18px;
            border-top:1px solid rgba(255,255,255,.12);
        }

        .sidebar-footer a{
            display:block;
            text-decoration:none;
            color:#fff;
            background:rgba(255,255,255,.08);
            padding:12px 14px;
            border-radius:12px;
            font-weight:bold;
            text-align:center;
        }

        .sidebar-footer a:hover{
            background:rgba(255,255,255,.14);
        }

        .main{
            flex:1;
            min-width:0;
        }

        .topbar{
            background:#fff;
            border-bottom:1px solid #e5e7eb;
            padding:16px 24px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:16px;
            position:sticky;
            top:0;
            z-index:20;
        }

        .topbar-left h1{
            margin:0;
            font-size:24px;
            color:#111827;
        }

        .topbar-left p{
            margin:5px 0 0;
            color:#6b7280;
            font-size:13px;
        }

        .topbar-right{
            display:flex;
            align-items:center;
            gap:10px;
            flex-wrap:wrap;
            justify-content:flex-end;
        }

        .top-btn{
            display:inline-block;
            padding:10px 14px;
            border-radius:10px;
            text-decoration:none;
            font-weight:bold;
            font-size:14px;
            border:none;
        }

        .top-btn.primary{
            background:#0d6efd;
            color:#fff;
        }

        .top-btn.dark{
            background:#212529;
            color:#fff;
        }

        .top-btn.light{
            background:#fff;
            color:#111827;
            border:1px solid #d1d5db;
        }

        .top-btn.danger{
            background:#dc3545;
            color:#fff;
        }

        .content{
            padding:24px;
        }

        .page-card{
            background:#fff;
            border-radius:16px;
            padding:20px;
            box-shadow:0 4px 18px rgba(0,0,0,.08);
            margin-bottom:20px;
        }

        @media (max-width: 920px){
            .admin-layout{
                flex-direction:column;
            }

            .sidebar{
                width:100%;
                height:auto;
                position:relative;
            }

            .topbar{
                position:relative;
                padding:16px;
                flex-direction:column;
                align-items:flex-start;
            }

            .topbar-right{
                width:100%;
                justify-content:flex-start;
            }

            .content{
                padding:16px;
            }
        }
    </style>
</head>
<body>
<div class="admin-layout">

    <aside class="sidebar">
        <div class="brand">
            <div class="brand-badge">RG Auto Admin</div>
            <h2>RG Auto Sales</h2>
            <p>Painel administrativo da operação comercial e gestão interna.</p>
        </div>

        <div class="menu-title">Principal</div>
        <nav class="nav">
            <a href="dashboard.php" class="<?= menuAtivo('dashboard.php') ?>">Dashboard</a>
            <a href="listar_carros.php" class="<?= menuAtivo('listar_carros.php') ?>">Carros</a>
            <a href="vendas.php" class="<?= menuAtivo('vendas.php') ?>">Vendas</a>
            <a href="clientes.php" class="<?= menuAtivo('clientes.php') ?>">Clientes</a>
            <a href="leads.php" class="<?= menuAtivo('leads.php') ?>">Leads</a>
            <a href="funil.php" class="<?= menuAtivo('funil.php') ?>">Funil</a>
            <a href="custos.php" class="<?= menuAtivo('custos.php') ?>">Custos</a>
        </nav>

        <div class="menu-title">Operações</div>
        <nav class="nav">
            <a href="carro_add.php" class="<?= menuAtivo('adicionar_carro.php') . ' ' . menuAtivo('carro_add.php') ?>">Adicionar Carro</a>
            <a href="nova_venda.php" class="<?= menuAtivo('nova_venda.php') ?>">Nova Venda</a>
            <a href="vendedores_pedidos.php" class="<?= menuAtivo('vendedores_pedidos.php') ?>">Pedidos de Vendedores</a>
            <a href="config.php" class="<?= menuAtivo('config.php') ?>">Configurações</a>
            <a href="relatorio_vendedores.php" class="<?= menuAtivo('relatorio_vendedores.php') ?>">Relatórios</a>
        </nav>

        <div class="sidebar-footer">
            <a href="../index.php" target="_blank">Ver site público</a>
        </div>
    </aside>

    <main class="main">
        <div class="topbar">
            <div class="topbar-left">
                <h1><?= htmlspecialchars($tituloAtual) ?></h1>
                <p>Sistema interno da RG Auto Sales</p>
            </div>

            <div class="topbar-right">
                <a href="adicionar_carro.php" class="top-btn primary">+ Adicionar carro</a>
                <a href="nova_venda.php" class="top-btn dark">+ Nova venda</a>
                <a href="../index.php" target="_blank" class="top-btn light">Ver site</a>
                <a href="/RG_AUTO_SALES/logout.php" class="top-btn danger">Sair</a>
            </div>
        </div>

        <div class="content">