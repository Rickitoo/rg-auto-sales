<?php
$nome   = htmlspecialchars($_GET['nome'] ?? '');
$marca  = htmlspecialchars($_GET['marca'] ?? '');
$modelo = htmlspecialchars($_GET['modelo'] ?? '');

$numeroWhatsApp = "25862934721"; // WhatsApp RG

$mensagem = "Olá RG Auto Sales, acabei de enviar pedido para vender meu carro: $marca $modelo. Meu nome é $nome.";
$linkWhats = "https://wa.me/$25862934721?text=" . urlencode($mensagem);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="RG Auto Sales — viaturas de qualidade, procedência garantida e test drive. Encontre o carro dos seus sonhos." />
  <title>Pedido Recebido | RG Auto Sales</title>
  <link rel="stylesheet" href="style.css">
  <link rel="icon" type="image/png" href="ImagensRG/logo.png">
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap" rel="stylesheet" />

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body>


  <!-- Search box (AGORA NO BODY, correto) -->
  <div class="search-box">
    <input class="search-txt" type="text" placeholder="Pesquise aqui" aria-label="Pesquisar" />
    <a class="search-btn" href="#" aria-label="Botão pesquisar">
      <i class="fas fa-search"></i>
    </a>
  </div>

<header class="header header--rg">
  <div class="header__overlay">
    <div class="container">

      <!-- NAVBAR -->
      <div class="navbar">
        <div class="logo">
          <a href="index.html">
            <img src="ImagensRG/logo.png" alt="RG Auto Sales" width="120">
          </a>
        </div>

        <nav>
          <ul id="MenuItems">
            <li><a href="index.html">Início</a></li>
            <li><a href="products.html">Carros</a></li>
            <li><a href="about.html">Sobre</a></li>
            <li><a href="contacto.html">Contacto</a></li>
            <li><a href="account.html">Conta</a></li>
            <li><a href="Test_drive.html">Test Drive</a></li>
            <li><a href="leasing.html">Leasing</a></li>
            <li><a href="vender_carro.html">Vender</a></li>
          </ul>
        </nav>

        <button class="menu-icon" type="button" onclick="menutoggle()" aria-label="Abrir menu">
          <i class="fa-solid fa-bars"></i>
        </button>
      </div>

<div class="sucesso-page">
  <div class="sucesso-card">

    <img src="ImagensRG/logo.png" width="120" alt="RG Auto Sales">

    <h1>Obrigado, <?php echo $nome; ?>! 👏</h1>

    <p>
      Recebemos o seu pedido para vender o <strong><?php echo "$marca $modelo"; ?></strong>.
    </p>

    <div class="sucesso-acoes">
      <a href="<?php echo $linkWhats; ?>" class="btn-sucesso" target="_blank">
        Confirmar no WhatsApp
      </a>

      <a href="index.html" class="btn-sucesso-outline">
        Voltar ao início
      </a>
    </div>

  </div>
</div>

<div class="footer">
    <div class="container">
      <div class="row">

        <div class="footer-col-1">
          <h3>Download do App</h3>
          <p>Disponível para Android e iOS.</p>
          <div class="app-logo">
            <img src="ImagensRG/AppStore.png" alt="App Store" />
            <img src="ImagensRG/pngtree-google-play-store-vector-png-image_9183318.png" alt="Google Play" />
          </div>
        </div>

        <div class="footer-col-2">
          <img src="ImagensRG/logo.png" alt="RG Auto Sales" />
          <p>Nosso objetivo é tornar acessível o prazer de dirigir veículos de qualidade, com transparência e confiança.</p>
        </div>

        <div class="footer-col-1">
          <h3>Links úteis</h3>
          <ul>
            <li><a href="products.html">Carros</a></li>
            <li><a href="Test_drive.html">Agendar Test Drive</a></li>
            <li><a href="vender_carro.html">Vender viatura</a></li>
            <li><a href="contacto.html">Contactos</a></li>
          </ul>
        </div>

        <div class="footer-col-4">
          <h3>Siga a RG</h3>
          <ul>
            <li><a href="https://www.facebook.com/profile.php?id=61588204178280&locale=pt_BR">Facebook</a></li>
            <li><a href="https://www.instagram.com/rgauto_sales/">Instagram</a></li>
            <li><a href="#">TikTok</a></li>
            <li><a href="#">YouTube</a></li>
          </ul>
        </div>

      </div>

      <hr />
      <p class="copyright">Copyright 2026 - RG SALES</p>
    </div>
  </div>


<script>
// abre automaticamente WhatsApp após 3 segundos
setTimeout(function(){
  window.open("<?php echo $linkWhats; ?>", "_blank");
}, 3000);
</script>

</body>
</html>