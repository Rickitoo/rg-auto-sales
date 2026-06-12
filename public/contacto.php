<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" type="image/png" href="<?= h(asset('ImagensRG/logo.png')) ?>" />
  <title>Contacto - RG Auto Sales</title>

  <link rel="stylesheet" href="<?= h(asset('css/style.css')) ?>" />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
  <!-- HEADER (Opção A: banner com logo moderno) -->
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
            <h1>Fale Connosco</h1>
            <p>Envie uma mensagem ou use os contactos diretos para resposta rápida.</p>

            <div style="display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-start;">
              <a class="btn" href="https://wa.me/258862934721?text=Olá%20RG%20Auto%20Sales,%20preciso%20de%20informações." target="_blank" rel="noopener">
                WhatsApp
              </a>
              <a class="btn btn--outline" href="tel:+258862934721">Ligar</a>
            </div>
          </div>
        </div>

      </div>
    </div>
  </header>

  <!-- CONTEÚDO -->
  <div class="small-container">
    <h2 class="title">Fale Connosco</h2>

    <div class="row">
      <!-- Form -->
      <div class="col-2">
        <form action="#" method="post">
          <?= csrf_input() ?>
          <?= public_honeypot_input() ?>
          <input type="text" name="nome" placeholder="Seu nome" required /><br /><br />
          <input type="email" name="email" placeholder="Seu email" required /><br /><br />
          <textarea name="mensagem" rows="6" placeholder="Sua mensagem" required></textarea><br /><br />
          <button type="submit" class="btn">Enviar Mensagem</button>
        </form>
      </div>

      <!-- Contactos -->
      <div class="col-2">
        <h3>Contactos RG</h3>
        <p><strong>Endereço:</strong> Rua Comandante Augusto Cardoso, Maputo — Moçambique</p>

        <p>
          <strong>Email:</strong>
          <a href="mailto:rgSolutions420@gmail.com">rgSolutions420@gmail.com</a>
        </p>

        <p>
          <strong>WhatsApp:</strong>
          <a href="https://wa.me/258862934721?text=Olá%20RG%20Auto%20Sales,%20quero%20informações." target="_blank" rel="noopener">
            +258 862 934 721
          </a>
        </p>

        <p>
          <strong>Chamada:</strong>
          <a href="tel:+258862934721">+258 862 934 721</a>
        </p>

        <br />

        <h3>Localização no Mapa</h3>

        <!-- IMPORTANTE: este iframe é exemplo (embed). O teu link maps.app.goo.gl não funciona como embed -->
        <iframe
          title="Mapa RG Auto Sales"
          src="https://www.google.com/maps?q=Maputo&output=embed"
          width="100%"
          height="300"
          style="border:0; border-radius: 10px;"
          allowfullscreen=""
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade">
        </iframe>

        <p style="margin-top:10px;">
          <a class="btn btn--outline" href="https://maps.google.com/?q=Rua%20Comandante%20Augusto%20Cardoso,%20Maputo" target="_blank" rel="noopener">
            Abrir no Google Maps
          </a>
        </p>
      </div>
    </div>
  </div>

  <!-- FOOTER (mantive teu footer) -->
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
