<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
$currentSearch = trim($_GET['q'] ?? '');
$user = current_user();
?>

<style>
@media (max-width: 900px) {
    .navbar {
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        gap: 10px !important;
        width: 100% !important;
        max-width: 100% !important;
    }
    .navbar .logo {
        order: 1 !important;
        flex: 0 0 auto !important;
    }
    .navbar .menu-icon {
        display: inline-flex !important;
        visibility: visible !important;
        order: 2 !important;
        flex: 0 0 44px !important;
        margin-left: auto !important;
        background: #00aeef !important;
        color: #fff !important;
        position: fixed !important;
        left: min(calc(100vw - 60px), 315px) !important;
        right: auto !important;
        top: 18px !important;
        z-index: 9999 !important;
    }
    .navbar .header-search {
        order: 3 !important;
        flex: 1 1 100% !important;
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
    }
    .navbar > nav {
        order: 4 !important;
        flex: 1 1 100% !important;
        width: 100% !important;
    }
}
</style>

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
                        <li><a href="<?= h(public_url('importar_carro.php')) ?>">Importar</a></li>
                        <li><a href="<?= h(public_url('vender_carro.php')) ?>">Vender</a></li>
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

                <button class="menu-icon" type="button" onclick="menutoggle()" aria-label="Abrir menu" aria-controls="MenuItems" aria-expanded="false">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>
    </div>
</header>
<script>
    window.menutoggle = function () {
        const menu = document.getElementById("MenuItems");
        const button = document.querySelector(".menu-icon");
        if (!menu) return;

        const isOpen = menu.classList.toggle("show");
        if (button) {
            button.setAttribute("aria-expanded", isOpen ? "true" : "false");
        }
    };

    document.addEventListener("click", function (event) {
        const menu = document.getElementById("MenuItems");
        const button = document.querySelector(".menu-icon");
        if (!menu || !button || !menu.classList.contains("show")) return;
        if (menu.contains(event.target) || button.contains(event.target)) return;

        menu.classList.remove("show");
        button.setAttribute("aria-expanded", "false");
    });
</script>
