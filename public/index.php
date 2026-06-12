<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
require_once __DIR__ . '/../includes/public_car_images.php';


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
    <link rel="icon" type="image/png" href="<?= h(asset('ImagensRG/logo.png')) ?>" />
    <title>RG Auto Sales | Encontre o seu carro</title>
    <meta name="description" content="RG Auto Sales — viaturas de qualidade, procedência garantida e test drive." />
    <link rel="stylesheet" href="<?= h(asset('css/style.css')) ?>" />
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
        .header--rg{ margin-top:20px; min-height:auto; background:#01203f; }
        .header--rg .header__overlay{ min-height:auto; background:transparent; }
    </style>
</head>

<body>

    <form class="search-box" action="<?= h(public_url('products.php')) ?>" method="GET">
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
                <button class="menu-icon" type="button" onclick="menutoggle()" aria-label="Abrir menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>

            <section class="home-carousel" data-home-carousel aria-label="Destaques RG Auto Sales">
                <div class="home-carousel__track">
                    <article class="home-carousel__slide is-active" style="--slide-bg:url('<?= h(asset('ImagensRG/Mercedes.jpeg')) ?>')" aria-hidden="false">
                        <div class="home-carousel__content">
                            <p class="home-carousel__eyebrow">RG Auto Sales</p>
                            <h1>Comprar carros com confianca</h1>
                            <p>Escolha viaturas selecionadas, fale com a equipa RG e agende o seu test drive com facilidade.</p>
                            <a class="btn" href="<?= h(public_url('products.php')) ?>">Ver Carros</a>
                        </div>
                    </article>

                    <article class="home-carousel__slide" style="--slide-bg:url('<?= h(asset('ImagensRG/import1.jpeg')) ?>')" aria-hidden="true">
                        <div class="home-carousel__content">
                            <p class="home-carousel__eyebrow">Importacao</p>
                            <h1>Importar carro do Japao</h1>
                            <p>Acompanhamento comercial para encontrar opcoes, alinhar orcamento e seguir o processo ate Mocambique.</p>
                            <a class="btn" href="<?= h(public_url('importar_carro.php#pedido')) ?>">Importar Agora</a>
                        </div>
                    </article>

                    <article class="home-carousel__slide" style="--slide-bg:url('<?= h(asset('ImagensRG/import3.jpeg')) ?>')" aria-hidden="true">
                        <div class="home-carousel__content">
                            <p class="home-carousel__eyebrow">Venda com apoio</p>
                            <h1>Vender seu carro na RG</h1>
                            <p>Envie os dados da viatura e receba acompanhamento para transformar interesse em proposta.</p>
                            <a class="btn" href="<?= h(public_url('vender_carro.php')) ?>">Vender Meu Carro</a>
                        </div>
                    </article>
                </div>

                <button class="home-carousel__arrow home-carousel__arrow--prev" type="button" data-carousel-prev aria-label="Banner anterior">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <button class="home-carousel__arrow home-carousel__arrow--next" type="button" data-carousel-next aria-label="Proximo banner">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>

                <div class="home-carousel__indicators" aria-label="Selecionar banner">
                    <button class="is-active" type="button" data-carousel-index="0" aria-current="true" aria-label="Comprar carros com confianca"></button>
                    <button type="button" data-carousel-index="1" aria-current="false" aria-label="Importar carro do Japao"></button>
                    <button type="button" data-carousel-index="2" aria-current="false" aria-label="Vender seu carro na RG"></button>
                </div>
            </section>
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
                $img = public_car_image_url($c);
                $preco = money_mt($c['preco'] ?? 0);
                $ano = (int)($c['ano'] ?? 0);

                $wa = "https://wa.me/258862934721?text=" . urlencode(
                    "Olá RG Auto Sales, tenho interesse no $titulo no valor de $preco. Ainda está disponível?"
                );
                ?>
                <div class="col-4 home-car-card">
                    <a href="<?= h(public_url('product-details.php?id=' . $id)) ?>">
                        <img src="<?= h($img) ?>" alt="<?= h_local($titulo !== '' ? $titulo : 'Carro RG Auto Sales') ?>" width="320" height="220" loading="lazy" onerror="<?= h(public_car_img_fallback_attr()) ?>" />
                    </a>
                    <h4><?= h_local($titulo) ?></h4>
                    <p><?= $preco ?></p>

                    <div class="home-car-actions">
                        <a class="btn btn--small" href="<?= h(public_url('product-details.php?id=' . $id)) ?>">Detalhes</a>
                        <a class="btn btn-outline-dark btn--small" href="<?= h(public_url('test_drive.php')) ?>">Test Drive</a>
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
                $img = public_car_image_url($c);
                $preco = money_mt($c['preco'] ?? 0);

                $wa = "https://wa.me/258862934721?text=" . urlencode(
                    "Olá RG Auto Sales, tenho interesse no $titulo no valor de $preco. Ainda está disponível?"
                );
                ?>
                <div class="col-4 home-car-card">
                    <a href="<?= h(public_url('product-details.php?id=' . $id)) ?>">
                        <img src="<?= h($img) ?>" alt="<?= h_local($titulo !== '' ? $titulo : 'Carro RG Auto Sales') ?>" width="320" height="220" loading="lazy" onerror="<?= h(public_car_img_fallback_attr()) ?>" />
                    </a>
                    <h4><?= h_local($titulo) ?></h4>
                    <p><?= $preco ?></p>

                    <div class="home-car-actions">
                        <a class="btn btn--small" href="<?= h(public_url('product-details.php?id=' . $id)) ?>">Detalhes</a>
                        <a class="btn btn-outline-dark btn--small" href="<?= h(public_url('test_drive.php')) ?>">Test Drive</a>
                        <a class="btn btn--small" href="<?= h($wa) ?>" target="_blank" rel="noopener">WhatsApp</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

        <?php require_once __DIR__ . '/../includes/footer_public.php'; ?>
    <script src="<?= h(asset('js/main.js')) ?>"></script>
</body>
</html>
