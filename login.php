<?php
session_start();
include("config_admin.php");

$erro = "";

// Gerar CSRF token (sempre antes do form)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    //  VALIDAR CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Erro de validação CSRF");
    }

    $user = trim($_POST["user"] ?? "");
    $pass = trim($_POST["pass"] ?? "");

    if ($user === $ADMIN_USER && password_verify($pass, $ADMIN_HASH)) {

        //  Segurança 
        session_regenerate_id(true);

        $_SESSION['admin_logado'] = true;

        header("Location: /RG_AUTO_SALES/admin/dashboard.php");
        exit;

    } else {
        $erro = "Credenciais inválidas.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Login | RG Auto Sales</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="ImagensRG/logo.png" />
  <title>RG Auto Sales | Encontre o seu carro</title>
  <meta name="description" content="RG Auto Sales — viaturas de qualidade, procedência garantida e test drive. Encontre o carro dos seus sonhos." />

  <link rel="stylesheet" href="style.css" />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap" rel="stylesheet" />

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    body{font-family:Arial,sans-serif;background:#f2f2f2;margin:0;padding:20px;}
    .wrap{max-width:420px;margin:0 auto;}
    .brand{font-weight:900;font-size:26px;letter-spacing:.5px;margin-bottom:12px;}
    .brand span{color:#0a7cff;}
    .card{background:#fff;padding:18px;border-radius:12px;box-shadow:0 8px 22px rgba(0,0,0,.08);}
    label{display:block;margin-top:12px;font-weight:700;}
    input{width:100%;padding:10px;border:1px solid #ddd;border-radius:10px;margin-top:6px;}
    button{margin-top:14px;width:100%;padding:10px;border:0;border-radius:10px;background:#000;color:#fff;font-weight:800;cursor:pointer;}
    .err{margin-top:10px;color:#b42318;font-weight:700;}
    .muted{margin-top:10px;color:#666;font-size:13px;}
  </style>

</head>

<body>

  <!-- Search box -->
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
          <a href="index.php">
            <img src="ImagensRG/logo.png" alt="RG Auto Sales" width="120">
          </a>
        </div>

        <nav>
          <ul id="MenuItems">
            <li><a href="index.php">Início</a></li>
            <li><a href="products.php">Carros</a></li>
            <li><a href="about.php">Sobre</a></li>
            <li><a href="contacto.php">Contacto</a></li>
            <li><a href="account.php">Conta</a></li>
            <li><a href="Test_drive.php">Test Drive</a></li>
            <li><a href="leasing.php">Leasing</a></li>
            <li><a href="vender_carro.php">Vender</a></li>
          </ul>
        </nav>

        <button class="menu-icon" type="button" onclick="menutoggle()" aria-label="Abrir menu">
          <i class="fa-solid fa-bars"></i>
        </button>
      </div>

</head>
<body>
  <div class="wrap">
    <div class="brand">RG <span>Auto Sales</span></div>
    <div class="card">
      <h2 style="margin:0;">Login Admin</h2>

      <?php if ($erro) { ?>
        <div class="err"><?php echo htmlspecialchars($erro); ?></div>
      <?php } ?>

      <form method="POST"<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <label>Usuário</label>
        <input type="text" name="user" required placeholder="admin">

        <label>Senha</label>
        <input type="password" name="pass" required placeholder="•••••">

        <button type="submit">Entrar</button>
      </form>

      <div class="muted">Login protegido com hash (password_verify).</div>
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
            <li><a href="products.php">Carros</a></li>
            <li><a href="Test_drive.php">Agendar Test Drive</a></li>
            <li><a href="vender_carro.php">Vender viatura</a></li>
            <li><a href="contacto.php">Contactos</a></li>
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

</body>
</html>
