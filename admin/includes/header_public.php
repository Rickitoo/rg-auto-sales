<?php
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
                        <li><a href="about.html">Sobre</a></li>
                        <li><a href="contacto.html">Contacto</a></li>
                        <li><a href="account.html">Conta</a></li>
                        <li><a href="Test_drive.html">Test Drive</a></li>
                        <li><a href="leasing.html">Leasing</a></li>
                        <li><a href="vender_carro.html">Vender</a></li>
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