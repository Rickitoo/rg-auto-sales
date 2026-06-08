<?php
require_once __DIR__ . '/../app/core/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . public_url('products.php'));
    exit;
}

/*
|--------------------------------------------------------------------------
| CARRO ATUAL
|--------------------------------------------------------------------------
| Nota: usar carros_fotos.caminho, igual ao catálogo products.php.
*/
$sql = "
    SELECT 
        c.*,
        COALESCE(
            NULLIF(c.imagem, ''),
            (
                SELECT cf.caminho
                FROM carros_fotos cf
                WHERE cf.carro_id = c.id
                ORDER BY cf.ordem ASC, cf.id ASC
                LIMIT 1
            )
        ) AS imagem_principal
    FROM carros c
    WHERE c.id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conexao, $sql);
if (!$stmt) {
    die("Erro ao preparar detalhe do carro.");
}

mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if (!$res || mysqli_num_rows($res) === 0) {
    header('Location: ' . public_url('products.php'));
    exit;
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
$sqlFotos = "
    SELECT id, caminho, ordem
    FROM carros_fotos
    WHERE carro_id = ?
    ORDER BY ordem ASC, id ASC
";

$stmtFotos = mysqli_prepare($conexao, $sqlFotos);
if ($stmtFotos) {
    mysqli_stmt_bind_param($stmtFotos, 'i', $id);
    mysqli_stmt_execute($stmtFotos);
    $resFotos = mysqli_stmt_get_result($stmtFotos);

    if ($resFotos) {
        while ($row = mysqli_fetch_assoc($resFotos)) {
            $caminho = trim((string)($row['caminho'] ?? ''));
            if ($caminho !== '') {
                $fotos[] = fotoCarroUrl(['imagem_principal' => $caminho, 'imagem' => $caminho]);
            }
        }
    }
}

$galeria = [];
if (!empty($fotoPrincipal)) {
    $galeria[] = $fotoPrincipal;
}

foreach ($fotos as $f) {
    if ($f !== '' && !in_array($f, $galeria, true)) {
        $galeria[] = $f;
    }
}

if (empty($galeria)) {
    $galeria[] = asset('img/sem-foto.jpg');
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
                SELECT cf.caminho
                FROM carros_fotos cf
                WHERE cf.carro_id = c.id
                ORDER BY cf.ordem ASC, cf.id ASC
                LIMIT 1
            )
        ) AS imagem_principal
    FROM carros c
    WHERE c.status = 'disponivel' AND c.id <> ?
    ORDER BY c.data_registo DESC, c.id DESC
    LIMIT 4
";

$stmtMais = mysqli_prepare($conexao, $sqlMais);
$mais = false;
if ($stmtMais) {
    mysqli_stmt_bind_param($stmtMais, 'i', $id);
    mysqli_stmt_execute($stmtMais);
    $mais = mysqli_stmt_get_result($stmtMais);
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= h($nome) ?> - RG Auto Sales</title>

    <link rel="icon" type="image/png" href="<?= h(asset('ImagensRG/logo.png')) ?>" />
    <link rel="stylesheet" href="<?= h(asset('css/style.css')) ?>" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    <style>
        .product-detail-page{
            padding:35px 0;
        }

        .product-detail-grid{
            display:grid;
            grid-template-columns:1.15fr .85fr;
            gap:30px;
            align-items:start;
        }

        .product-gallery,
        .product-info-card,
        .related-card{
            background:#fff;
            border-radius:18px;
            box-shadow:0 8px 24px rgba(1,32,63,.10);
            padding:18px;
        }

        #mainImg{
            width:100%;
            max-height:480px;
            object-fit:cover;
            border-radius:14px;
            background:#f5f5f5;
            display:block;
        }

        .small-img-row{
            display:grid;
            grid-template-columns:repeat(auto-fill, minmax(76px, 1fr));
            gap:10px;
            margin-top:12px;
        }

        .thumb{
            width:100%;
            height:72px;
            object-fit:cover;
            border-radius:10px;
            cursor:pointer;
            border:2px solid transparent;
            background:#f5f5f5;
        }

        .thumb:hover{
            border-color:#01203f;
        }

        .product-info-card h1{
            text-align:left;
            color:#01203f;
            margin-bottom:8px;
            line-height:1.15;
        }

        .price-tag{
            color:#f97316;
            font-size:24px;
            font-weight:700;
            margin:10px 0;
        }

        .product-desc{
            color:#334155;
            margin-top:12px;
            line-height:1.7;
        }

        .product-actions{
            display:flex;
            gap:10px;
            flex-wrap:wrap;
            margin:18px 0;
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

        .features-list{
            color:#01203f;
            margin-top:10px;
            padding-left:18px;
            line-height:1.9;
        }

        .related-grid{
            display:grid;
            grid-template-columns:repeat(4, 1fr);
            gap:18px;
            margin-top:18px;
        }

        .related-card img{
            width:100%;
            height:170px;
            object-fit:cover;
            border-radius:12px;
            background:#f5f5f5;
        }

        .related-card h4{
            margin-top:10px;
        }

        @media (max-width: 900px){
            .product-detail-grid{
                grid-template-columns:1fr;
            }

            .related-grid{
                grid-template-columns:repeat(2, 1fr);
            }
        }

        @media (max-width: 600px){
            .product-detail-page{
                padding:20px 0;
            }

            .product-gallery,
            .product-info-card,
            .related-card{
                padding:14px;
                border-radius:14px;
            }

            .product-info-card h1{
                font-size:26px;
            }

            .price-tag{
                font-size:21px;
            }

            .product-actions .btn{
                width:100%;
                text-align:center;
            }

            .related-grid{
                grid-template-columns:1fr;
            }

            #mainImg{
                max-height:330px;
            }
        }
    </style>
</head>

<body>
    <?php require_once __DIR__ . '/../includes/header_public.php'; ?>

    <div class="page-hero">
        <div class="container">
            <div class="row header__hero">
                <div class="col-2">
                    <h1><?= h($nome) ?></h1>
                    <p>Veja fotos, características e fale com a RG Auto Sales.</p>
                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <a class="btn" href="<?= h($wa) ?>" target="_blank" rel="noopener">WhatsApp</a>
                        <a class="btn btn--outline" href="<?= h(public_url('test_drive.php')) ?>">Agendar Test Drive</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <main class="small-container product-detail-page">
        <div class="product-detail-grid">
            <section class="product-gallery">
                <img id="mainImg" src="<?= h($galeria[0]) ?>" alt="<?= h($nome) ?>" />

                <div class="small-img-row">
                    <?php foreach ($galeria as $thumb): ?>
                        <img class="thumb" src="<?= h($thumb) ?>" alt="Miniatura de <?= h($nome) ?>">
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="product-info-card">
                <h1><?= h($nome) ?></h1>
                <div class="price-tag"><?= $precoFmt ?> MT</div>

                <p class="product-desc">
                    <?= $desc !== '' ? nl2br(h($desc)) : 'Sem descrição detalhada por enquanto. Fale com a RG Auto Sales para confirmar estado, documentos e disponibilidade.' ?>
                </p>

                <div class="product-actions">
                    <a class="btn" href="<?= h($wa) ?>" target="_blank" rel="noopener">Falar no WhatsApp</a>
                    <a class="btn btn--outline" href="tel:+258862934721">Ligar</a>
                    <a class="btn btn--outline" href="<?= h(public_url('test_drive.php')) ?>">Test Drive</a>
                </div>

                <h3 style="margin-top:18px; text-align:left; color:#01203f;">Características</h3>
                <ul class="features-list">
                    <li><strong>Marca:</strong> <?= h($carro['marca'] ?? '—') ?></li>
                    <li><strong>Modelo:</strong> <?= h($carro['modelo'] ?? '—') ?></li>
                    <li><strong>Ano:</strong> <?= $ano > 0 ? h((string)$ano) : '—' ?></li>
                    <li><strong>Estado:</strong> Disponível</li>
                </ul>

                <p style="margin-top:12px; color:#01203f;">
                    <strong>Quer fechar rápido?</strong> Clique no WhatsApp e diga: “Quero este carro do site”.
                </p>
            </section>
        </div>

        <h2 class="title">Mais opções</h2>

        <div class="related-grid">
            <?php if ($mais && mysqli_num_rows($mais) > 0): ?>
                <?php while ($m = mysqli_fetch_assoc($mais)): ?>
                    <?php
                    $mNome = trim(($m['marca'] ?? '') . ' ' . ($m['modelo'] ?? ''));
                    $mAno = (int)($m['ano'] ?? 0);
                    $mPreco = number_format((float)($m['preco'] ?? 0), 0, ',', '.');
                    $mImg = fotoCarroUrl($m);
                    ?>
                    <article class="related-card">
                        <a href="<?= h(public_url('product-details.php?id=' . (int)$m['id'])) ?>" class="product-link">
                            <img src="<?= h($mImg) ?>" alt="<?= h($mNome) ?>" />
                            <h4><?= h($mNome) ?><?= $mAno > 0 ? ' (' . h((string)$mAno) . ')' : '' ?></h4>
                            <p><?= $mPreco ?> MT</p>
                        </a>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="padding:10px;">Sem mais opções por agora.</p>
            <?php endif; ?>
        </div>
    </main>

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
                    <img src="<?= h(asset('ImagensRG/logo.png')) ?>" alt="RG Auto Sales" />
                    <p>Nosso objetivo é tornar acessível o prazer de dirigir veículos de qualidade, com transparência e confiança.</p>
                </div>

                <div class="footer-col-4">
                    <h3>Siga a RG</h3>
                    <ul>
                        <li><a href="https://www.instagram.com/rgauto_sales/">Instagram</a></li>
                        <li><a href="https://www.facebook.com/profile.php?id=61588204178280&locale=pt_BR">Facebook</a></li>
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
            if (menuItems) menuItems.classList.toggle("show");
        }

        document.querySelectorAll(".thumb").forEach(t => {
            t.addEventListener("click", () => {
                const mainImg = document.getElementById("mainImg");
                if (mainImg) mainImg.src = t.src;
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
