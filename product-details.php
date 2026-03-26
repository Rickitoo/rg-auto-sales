<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include("conexao.php");
include("includes/funcoes_carros.php");

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Carro inválido.");
}

/*
|--------------------------------------------------------------------------
| CARRO ATUAL
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT 
        c.*,
        COALESCE(
            NULLIF(c.imagem, ''),
            (
                SELECT cf.foto
                FROM carros_fotos cf
                WHERE cf.carro_id = c.id
                ORDER BY cf.ordem ASC, cf.id ASC
                LIMIT 1
            )
        ) AS imagem_principal
    FROM carros c
    WHERE c.id = $id
    LIMIT 1
";

$res = mysqli_query($conexao, $sql);
if (!$res || mysqli_num_rows($res) === 0) {
    die("Carro não encontrado.");
}

$carro = mysqli_fetch_assoc($res);

$nome = trim(($carro['marca'] ?? '') . ' ' . ($carro['modelo'] ?? ''));
$ano = (int)($carro['ano'] ?? 0);
$precoFmt = number_format((float)($carro['preco'] ?? 0), 0, ',', '.');
$desc = $carro['descricao'] ?? '';

$fotoPrincipal = fotoCarroUrl($carro);

/*
|--------------------------------------------------------------------------
| GALERIA DO CARRO
|--------------------------------------------------------------------------
*/
$fotos = [];
$resFotos = mysqli_query($conexao, "
    SELECT id, foto, ordem
    FROM carros_fotos
    WHERE carro_id = $id
    ORDER BY ordem ASC, id ASC
");

if ($resFotos) {
    while ($row = mysqli_fetch_assoc($resFotos)) {
        $foto = trim((string)($row['foto'] ?? ''));
        if ($foto !== '') {
            $fotos[] = "uploads/" . $foto;
        }
    }
}

// garante que a principal aparece primeiro
$galeria = [];
if (!empty($fotoPrincipal)) {
    $galeria[] = $fotoPrincipal;
}
foreach ($fotos as $f) {
    if ($f !== $fotoPrincipal) {
        $galeria[] = $f;
    }
}
if (empty($galeria)) {
    $galeria[] = "assets/img/sem-foto.jpg";
}

/*
|--------------------------------------------------------------------------
| WHATSAPP
|--------------------------------------------------------------------------
*/
$wa = "https://wa.me/258862934721?text=" . urlencode(
    "Olá RG Auto Sales, tenho interesse no $nome ($ano) no valor de $precoFmt MT. Ainda está disponível?"
);

/*
|--------------------------------------------------------------------------
| MAIS OPÇÕES
|--------------------------------------------------------------------------
*/
$sqlMais = "
    SELECT 
        c.id,
        c.marca,
        c.modelo,
        c.ano,
        c.preco,
        COALESCE(
            NULLIF(c.imagem, ''),
            (
                SELECT cf.foto
                FROM carros_fotos cf
                WHERE cf.carro_id = c.id
                ORDER BY cf.ordem ASC, cf.id ASC
                LIMIT 1
            )
        ) AS imagem_principal
    FROM carros c
    WHERE c.status = 'disponivel' AND c.id <> $id
    ORDER BY c.data_registo DESC
    LIMIT 4
";

$mais = mysqli_query($conexao, $sqlMais);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= h($nome) ?> - RG Auto Sales</title>

    <link rel="icon" type="image/png" href="ImagensRG/logo.png" />
    <link rel="stylesheet" href="style.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    <style>
        .product-actions{
            display:flex;
            gap:10px;
            flex-wrap:wrap;
        }
        .btn--outline{
            background:transparent;
            border:2px solid #01203f;
            color:#01203f;
        }
        .btn--outline:hover{
            background:#01203f;
            color:#fff;
        }
        .small-img-row{
            display:flex;
            gap:10px;
            flex-wrap:wrap;
            margin-top:12px;
        }
        .small-img-col{
            width:90px;
        }
        .thumb{
            width:100%;
            height:70px;
            object-fit:cover;
            border-radius:8px;
            cursor:pointer;
            border:1px solid #ddd;
        }
        #mainImg{
            width:100%;
            max-height:460px;
            object-fit:cover;
            border-radius:12px;
            background:#f5f5f5;
        }
    </style>
</head>

<body>
    <!-- HEADER -->
     <header class="header header--rg">
        <div class="header__overlay">
        <div class="container">

            <div class="navbar">
            <div class="logo">
                <a href="index.html">
                <img src="ImagensRG/logo.png" alt="RG Auto Sales" width="120" />
                </a>
            </div>

            <nav>
                <ul id="MenuItems">
                <li><a href="index.html">Início</a></li>
                <li><a href="products.html">Carros</a></li>
                <li><a href="about.html">Sobre</a></li>
                <li><a href="contacto.html">Contacto</a></li>
                <li><a href="account.html">Conta</a></li>
                <li><a href="test_drive.html">Test Drive</a></li>
                <li><a href="leasing.html">Leasing</a></li>
                <li><a href="vender_carro.html">Vender</a></li>
                </ul>
            </nav>

            <a href="cart.html" aria-label="Carrinho">
                <img src="ImagensRG/png-transparent-computer-icons-shopping-cart-basket-shopping-cart-text-hand-share-icon.png" alt="Carrinho" width="28" height="30" />
            </a>

            <button class="menu-icon" type="button" onclick="menutoggle()" aria-label="Abrir menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            </div>

            <div class="row header__hero">
                <div class="col-2">
                    <h1 id="carTitle">Detalhes do Carro</h1>
                    <p id="carSubtitle">Veja fotos, características e agende o seu test drive.</p>
                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <a class="btn" id="whatsHeader" href="#" target="_blank" rel="noopener">WhatsApp</a>
                        <a class="btn btn--outline" href="Test_drive.html">Agendar Test Drive</a>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </header>
    <?php include("includes/header_public.php"); ?>

    <div class="page-hero">
        <div class="container">
            <div class="row header__hero">
                <div class="col-2">
                    <h1><?= h($nome) ?></h1>
                    <p>Veja fotos, características e agende o seu test drive.</p>
                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <a class="btn" href="<?= h($wa) ?>" target="_blank" rel="noopener">WhatsApp</a>
                        <a class="btn btn--outline" href="Test_drive.html">Agendar Test Drive</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DETALHES -->
    <div class="small-container">
        <h2 class="title">Detalhes</h2>

        <div class="row single-products">
            <!-- Imagem -->
            <div class="col-2">
                <img id="mainImg" src="<?= h($galeria[0]) ?>" alt="<?= h($nome) ?>" />

                <div class="small-img-row">
                    <?php foreach ($galeria as $thumb): ?>
                        <div class="small-img-col">
                            <img class="thumb" src="<?= h($thumb) ?>" alt="Miniatura">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Info -->
            <div class="col-2">
                <h1 style="text-align:left; color:#01203f;"><?= h($nome) ?></h1>
                <h4 style="color:#f97316;">Preço: <?= $precoFmt ?> MT</h4>

                <p style="color:#01203f; margin-top:12px;">
                    <?= nl2br(h($desc)) ?>
                </p>

                <div class="rating" style="margin:10px 0;">
                    <i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star-o"></i>
                </div>

                <div class="product-actions" style="justify-content:flex-start;">
                    <a class="btn" href="<?= h($wa) ?>" target="_blank" rel="noopener">Falar no WhatsApp</a>
                    <a class="btn btn--outline" href="tel:+258862934721">Ligar</a>
                    <a class="btn btn--outline" href="Test_drive.html">Test Drive</a>
                </div>

                <h3 style="margin-top:18px; text-align:left; color:#01203f;">Características</h3>
                <ul style="color:#01203f; margin-top:10px; padding-left:18px;">
                    <li><strong>Ano:</strong> <?= h((string)$ano) ?></li>
                    <li><strong>Combustível:</strong> —</li>
                    <li><strong>Câmbio:</strong> —</li>
                    <li><strong>Tração:</strong> —</li>
                </ul>

                <p style="margin-top:12px; color:#01203f;">
                    <strong>Quer fechar rápido?</strong> Clique no WhatsApp e diga: “Quero este carro do site”.
                </p>
            </div>
        </div>

        <h2 class="title">Mais opções</h2>
        <div class="row">
            <?php if ($mais && mysqli_num_rows($mais) > 0): ?>
                <?php while ($m = mysqli_fetch_assoc($mais)): ?>
                    <?php
                    $mNome = trim(($m['marca'] ?? '') . ' ' . ($m['modelo'] ?? ''));
                    $mPreco = number_format((float)($m['preco'] ?? 0), 0, ',', '.');
                    $mImg = fotoCarroUrl($m);
                    ?>
                    <div class="col-4 product-card">
                        <a href="product-details.php?id=<?= (int)$m['id'] ?>" class="product-link">
                            <img src="<?= h($mImg) ?>" alt="<?= h($mNome) ?>" />
                            <h4><?= h($mNome) ?></h4>
                            <p><?= $mPreco ?> MT</p>
                        </a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="padding:10px;">Sem mais opções por agora.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        <div class="container">
            <div class="row">

                <div class="footer-col-1">
                    <h3>Contactos</h3>
                    <p>
                        WhatsApp: <a href="https://wa.me/258862934721" target="_blank" rel="noopener">+258 862 934 721</a><br />
                        Email: <a href="mailto:rgSolutions420@gmail.com">rgSolutions420@gmail.com</a>
                    </p>
                </div>

                <div class="footer-col-2">
                    <img src="ImagensRG/logo.png" alt="RG Auto Sales" />
                    <p>Nosso objetivo é tornar acessível o prazer de dirigir veículos de qualidade, com transparência e confiança.</p>
                </div>

                <div class="footer-col-4">
                    <h3>Siga a RG</h3>
                    <ul>
                        <li><a href="#">Instagram</a></li>
                        <li><a href="#">Facebook</a></li>
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

        document.querySelectorAll(".thumb").forEach(t => {
            t.addEventListener("click", () => {
                document.getElementById("mainImg").src = t.src;
            });
        });
    </script>

    <a class="wa-float"
       href="<?= h($wa) ?>"
       target="_blank"
       rel="noopener"
       aria-label="Falar no WhatsApp com a RG Auto Sales">
        <i class="fa-brands fa-whatsapp"></i>
        <span>WhatsApp RG</span>
    </a>

</body>
</html>