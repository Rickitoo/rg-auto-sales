<?php
session_start();

if (!function_exists('h')) {
    function h($v) {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

$currentSearch = trim($_GET['q'] ?? '');
?>

<header class="header header--rg">
    <div class="header__overlay">
        <div class="container">

            <div class="navbar">
                <div class="logo">
                    <a href="index.php">
                        <img src="ImagensRG/logo.png" alt="RG Auto Sales" width="120" />
                    </a>
                </div>

                <nav>
                    <ul id="MenuItems">
                        <li><a href="index.php">Início</a></li>
                        <li><a href="products.php">Carros</a></li>
                        <li><a href="about.php">Sobre</a></li>
                        <li><a href="contacto.php">Contacto</a></li>

                        <?php if(isset($_SESSION['username'])): ?>
                            <li>👤 <?= h($_SESSION['username']) ?></li>
                            <li><a href="logout.php">Logout</a></li>
                        <?php else: ?>
                            <li><a href="account.php">Conta</a></li>
                        <?php endif; ?>

                        <li><a href="Test_drive.php">Test Drive</a></li>
                        <li><a href="leasing.php">Leasing</a></li>
                        <li><a href="vender_carro.php">Vender</a></li>
                    </ul>
                </nav>

                <form class="header-search" action="products.php" method="GET">
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
