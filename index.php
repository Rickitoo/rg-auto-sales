<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/conexao.php";
require_once __DIR__ . "/admin/includes/funcoes_carros.php";

function h_local($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function money_mt($v) {
    return number_format((float)$v, 0, ',', '.') . " MT";
}

function nome_carro($row) {
    return trim(($row['marca'] ?? '') . " " . ($row['modelo'] ?? '') . " " . ($row['ano'] ?? ''));
}

/*
|--------------------------------------------------------------------------
| BUSCAR 8 CARROS MAIS RECENTES DISPONÍVEIS
|--------------------------------------------------------------------------
*/
$status = "disponivel";

$stmt = mysqli_prepare($conexao, "
    SELECT
        c.*,
        COALESCE(
            NULLIF(c.imagem, ''),
            (
                SELECT cf.foto
                FROM caminho cf
                WHERE cf.carro_id = c.id
                ORDER BY cf.ordem ASC, cf.id ASC
                LIMIT 1
            )
        ) AS imagem_principal
    FROM carros c
    WHERE c.status = ?
    ORDER BY c.data_registo DESC, c.id DESC
    LIMIT 8
");

mysqli_stmt_bind_param($stmt, "s", $status);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$carros = [];
while ($res && ($r = mysqli_fetch_assoc($res))) {
    $carros[] = $r;
}

$destaque = array_slice($carros, 0, 4);
$recentes = array_slice($carros, 4, 4);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/png" href="ImagensRG/logo.png" />
    <title>RG Auto Sales | Encontre o seu carro</title>
    <meta name="description" content="RG Auto Sales — viaturas de qualidade, procedência garantida e test drive." />
    <link rel="stylesheet" href="style.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    <style>
        .home-car-card img{
            width:100%;
            height:220px;
            object-fit:cover;
            border-radius:10px;
            background:#f5f5f5;
        }
        .home-car-card h4{
            margin-top:12px;
            margin-bottom:6px;
        }
        .home-car-card p{
            margin:0 0 10px 0;
        }
        .home-car-actions{
            display:flex;
            gap:8px;
            flex-wrap:wrap;
        }
        .btn--small{
            padding:8px 14px;
            font-size:14px;
        }
        .btn-ghost{
            background:transparent;
            border:2px solid #fff;
            color:#fff;
        }
        .btn-ghost:hover{
            background:#fff;
            color:#01203f;
        }
        .btn-outline-dark{
            background:transparent;
            border:2px solid #01203f;
            color:#01203f;
        }
        .btn-outline-dark:hover{
            background:#01203f;
            color:#fff;
        }
    </style>
</head>

<body>

    <form class="search-box" action="products.php" method="GET">
      <input
          class="search-txt"
          type="text"
          name="q"
          placeholder="Ex.: Prado, BMW X3, Hilux..."
          aria-label="Pesquisar"
      />
      <button class="search-btn" type="submit" aria-label="Botão pesquisar">
          <i class="fas fa-search"></i>
      </button>
    </form>

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
        </div>
    </div>
</header>

    <div class="small-container">
        <h2 class="title">Carros em Destaque</h2>
        <div class="row">
            <?php if (count($destaque) === 0): ?>
                <p style="padding:0 10px;">Sem viaturas disponíveis no momento.</p>
            <?php endif; ?>

            <?php foreach ($destaque as $c): ?>
                <?php
                $id = (int)$c['id'];
                $titulo = nome_carro($c);
                $img = fotoCarroUrl($c);
                $preco = money_mt($c['preco'] ?? 0);
                $ano = (int)($c['ano'] ?? 0);

                $wa = "https://wa.me/258862934721?text=" . urlencode(
                    "Olá RG Auto Sales, tenho interesse no $titulo no valor de $preco. Ainda está disponível?"
                );
                ?>
                <div class="col-4 home-car-card">
                    <a href="product-details.php?id=<?= $id ?>">
                        <img src="<?= h($img) ?>" alt="<?= h_local($titulo) ?>" />
                    </a>
                    <h4><?= h_local($titulo) ?></h4>
                    <p><?= $preco ?></p>

                    <div class="home-car-actions">
                        <a class="btn btn--small" href="product-details.php?id=<?= $id ?>">Detalhes</a>
                        <a class="btn btn-outline-dark btn--small" href="Test_drive.php">Test Drive</a>
                        <a class="btn btn--small" href="<?= h($wa) ?>" target="_blank" rel="noopener">WhatsApp</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <h2 class="title">Mais Recentes</h2>
        <div class="row">
            <?php if (count($recentes) === 0): ?>
                <p style="padding:0 10px;">Sem mais viaturas recentes por agora.</p>
            <?php endif; ?>

            <?php foreach ($recentes as $c): ?>
                <?php
                $id = (int)$c['id'];
                $titulo = nome_carro($c);
                $img = fotoCarroUrl($c);
                $preco = money_mt($c['preco'] ?? 0);

                $wa = "https://wa.me/258862934721?text=" . urlencode(
                    "Olá RG Auto Sales, tenho interesse no $titulo no valor de $preco. Ainda está disponível?"
                );
                ?>
                <div class="col-4 home-car-card">
                    <a href="product-details.php?id=<?= $id ?>">
                        <img src="<?= h($img) ?>" alt="<?= h_local($titulo) ?>" />
                    </a>
                    <h4><?= h_local($titulo) ?></h4>
                    <p><?= $preco ?></p>

                    <div class="home-car-actions">
                        <a class="btn btn--small" href="product-details.php?id=<?= $id ?>">Detalhes</a>
                        <a class="btn btn-outline-dark btn--small" href="Test_drive.php">Test Drive</a>
                        <a class="btn btn--small" href="<?= h($wa) ?>" target="_blank" rel="noopener">WhatsApp</a>
                    </div>
                </div>
            <?php endforeach; ?>
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


    <script>
        const menuItems = document.getElementById("MenuItems");
        function menutoggle() {
            menuItems.classList.toggle("show");
        }
    </script>
</body>
</html>