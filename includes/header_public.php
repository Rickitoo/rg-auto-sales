<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
$currentSearch = trim($_GET['q'] ?? '');
$user = current_user();
?>

<header class="header header--rg">
    <div class="header__overlay">
        <div class="container">
            <div class="navbar">
                <div class="logo">
                    <a href="<?= h(public_url('index.php')) ?>">
                        <img src="<?= h(asset('ImagensRG/logo.png')) ?>" alt="RG Auto Sales" width="120">
                    </a>
                </div>

                <nav>
                    <ul id="MenuItems">
                        <li><a href="<?= h(public_url('index.php')) ?>">Início</a></li>
                        <li><a href="<?= h(public_url('products.php')) ?>">Carros</a></li>
                        <li><a href="<?= h(public_url('about.php')) ?>">Sobre</a></li>
                        <li><a href="<?= h(public_url('contacto.php')) ?>">Contacto</a></li>
                        <li><a href="<?= h(public_url('test_drive.php')) ?>">Test Drive</a></li>
                        <li><a href="<?= h(public_url('leasing.php')) ?>">Leasing</a></li>
                        <?php if ($user): ?>
                            <li><a href="<?= h(is_admin() ? url('admin/dashboard.php') : public_url('dashboard.php')) ?>"><?= h($user['nome']) ?></a></li>
                            <li><a href="<?= h(url('auth/logout.php')) ?>">Sair</a></li>
                        <?php else: ?>
                            <li><a href="<?= h(public_url('account.php')) ?>">Conta</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>

                <form class="header-search" action="<?= h(public_url('products.php')) ?>" method="GET">
                    <input
                        type="text"
                        name="q"
                        value="<?= h($currentSearch) ?>"
                        placeholder="Ex.: Prado, BMW X3, Hilux..."
                        aria-label="Pesquisar carros"
                    >
                    <button type="submit" aria-label="Pesquisar">
                        <i class="fas fa-search"></i>
                    </button>
                </form>

                <button class="menu-icon" type="button" onclick="menutoggle()" aria-label="Abrir menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>
    </div>
</header>
