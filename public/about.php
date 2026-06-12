<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" type="image/png" href="<?= h(asset('ImagensRG/logo.png')) ?>" />
  <title>Sobre - RG Auto Sales</title>

  <link rel="stylesheet" href="<?= h(asset('css/style.css')) ?>" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
  <!-- HEADER (Opção A) -->
  <header class="header header--rg">
    <div class="header__overlay">
      <div class="container">

        <div class="navbar">
          <div class="logo">
            <a href="<?= h(public_url('index.php')) ?>">
              <img src="<?= h(asset('ImagensRG/logo.png')) ?>" alt="RG Auto Sales" width="120" />
            </a>
          </div>

          <nav>
            <ul id="MenuItems">
              <li><a href="<?= h(public_url('index.php')) ?>">Início</a></li>
              <li><a href="<?= h(public_url('products.php')) ?>">Carros</a></li>
              <li><a href="<?= h(public_url('about.php')) ?>">Sobre</a></li>
              <li><a href="<?= h(public_url('contacto.php')) ?>">Contacto</a></li>
              <li><a href="<?= h(public_url('account.php')) ?>">Conta</a></li>
              <li><a href="<?= h(public_url('test_drive.php')) ?>">Test Drive</a></li>
              <li><a href="<?= h(public_url('leasing.php')) ?>">Leasing</a></li>
              <li><a href="<?= h(public_url('importar_carro.php')) ?>">Importar</a></li>
              <li><a href="<?= h(public_url('vender_carro.php')) ?>">Vender</a></li>
            </ul>
          </nav>

          <a href="<?= h(public_url('cart.php')) ?>" aria-label="Carrinho">
            <img src="<?= h(asset('ImagensRG/png-transparent-computer-icons-shopping-cart-basket-shopping-cart-text-hand-share-icon.png')) ?>" alt="Carrinho" width="28" height="30" />
          </a>

          <button class="menu-icon" type="button" onclick="menutoggle()" aria-label="Abrir menu">
            <i class="fa-solid fa-bars"></i>
          </button>
        </div>

        <!-- Título no banner -->
        <div class="row header__hero">
          <div class="col-2">
            <h1>Sobre a RG Auto Sales</h1>
            <p>Rodando com confiança, vendendo com responsabilidade.</p>

            <div style="display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-start;">
              <a class="btn" href="<?= h(public_url('contacto.php')) ?>">Fale Connosco</a>
              <a class="btn btn--outline" href="https://wa.me/258862934721?text=Olá%20RG%20Auto%20Sales,%20quero%20mais%20informações." target="_blank" rel="noopener">
                WhatsApp
              </a>
            </div>
          </div>
        </div>

      </div>
    </div>
  </header>

  <!-- CONTEÚDO -->
  <div class="small-container">
    <h2 class="title">Quem Somos</h2>

    <div class="card" style="padding:18px; border-radius:12px;">
      <p style="color:#01203f;">
        Na <strong>RG Auto Sales</strong>, somos apaixonados por carros e, acima de tudo, comprometidos com a sua satisfação.
        Fundada com o objetivo de oferecer veículos de qualidade, confiança e preço justo, nossa missão é tornar a compra do seu carro
        uma experiência simples, segura e transparente.
      </p>

      <p style="color:#01203f; margin-top:12px;">
        Com uma seleção criteriosa de automóveis seminovos e usados, garantimos que cada veículo seja inspecionado com rigor antes de chegar até você.
        Valorizamos a honestidade em cada negociação e trabalhamos diariamente para construir uma relação duradoura com nossos clientes.
      </p>

      <p style="color:#01203f; margin-top:12px;">
        Além da venda, também oferecemos consultoria personalizada, ajudando você a encontrar o carro ideal de acordo com suas necessidades e estilo de vida.
        Seja seu primeiro carro ou uma nova conquista, a RG Auto Sales está aqui para te ajudar em cada passo.
      </p>

      <p style="color:#01203f; margin-top:12px;">
        <strong>RG Auto Sales</strong> – Rodando com confiança, vendendo com responsabilidade.
      </p>
    </div>
  </div>

  <!-- FOOTER -->
    <?php require_once __DIR__ . '/../includes/footer_public.php'; ?>
  <script>
    const menuItems = document.getElementById("MenuItems");
    function menutoggle(){
      menuItems.classList.toggle("show");
    }
  </script>
  <?php require_once __DIR__ . '/includes/wa_float.php'; ?>

</body>
</html>
