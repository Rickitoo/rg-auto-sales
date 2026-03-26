<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include("conexao.php");
include("includes/funcoes_carros.php");

function nomeCarro($row) {
    return trim(($row['marca'] ?? '') . ' ' . ($row['modelo'] ?? ''));
}

function precoFmt($row) {
    return number_format((float)($row['preco'] ?? 0), 0, ',', '.');
}

/*
|--------------------------------------------------------------------------
| FILTROS
|--------------------------------------------------------------------------
*/
$q         = trim($_GET['q'] ?? '');
$marca     = trim($_GET['marca'] ?? '');
$ano_min   = (int)($_GET['ano_min'] ?? 0);
$ano_max   = (int)($_GET['ano_max'] ?? 0);
$preco_min = (float)str_replace(',', '.', $_GET['preco_min'] ?? 0);
$preco_max = (float)str_replace(',', '.', $_GET['preco_max'] ?? 0);
$sort      = $_GET['sort'] ?? 'recentes';

$mapSort = [
    'recentes'    => 'c.data_registo DESC, c.id DESC',
    'menor_preco' => 'c.preco ASC, c.id DESC',
    'maior_preco' => 'c.preco DESC, c.id DESC',
    'ano_desc'    => 'c.ano DESC, c.id DESC',
    'ano_asc'     => 'c.ano ASC, c.id DESC',
];
$orderSql = $mapSort[$sort] ?? $mapSort['recentes'];

/*
|--------------------------------------------------------------------------
| LISTA DE MARCAS PARA O FILTRO
|--------------------------------------------------------------------------
*/
$resMarcas = mysqli_query($conexao, "
    SELECT DISTINCT marca
    FROM carros
    WHERE status = 'disponivel' AND marca IS NOT NULL AND marca <> ''
    ORDER BY marca ASC
");
if (!$resMarcas) {
    die("Erro ao buscar marcas: " . mysqli_error($conexao));
}

/*
|--------------------------------------------------------------------------
| QUERY PRINCIPAL COM FILTROS
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
    WHERE c.status = 'disponivel'
";

$params = [];
$types = '';

if ($q !== '') {
    $sql .= " AND (c.marca LIKE ? OR c.modelo LIKE ? OR c.descricao LIKE ?)";
    $like = "%{$q}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

if ($marca !== '') {
    $sql .= " AND c.marca = ?";
    $params[] = $marca;
    $types .= 's';
}

if ($ano_min > 0) {
    $sql .= " AND c.ano >= ?";
    $params[] = $ano_min;
    $types .= 'i';
}

if ($ano_max > 0) {
    $sql .= " AND c.ano <= ?";
    $params[] = $ano_max;
    $types .= 'i';
}

if ($preco_min > 0) {
    $sql .= " AND c.preco >= ?";
    $params[] = $preco_min;
    $types .= 'd';
}

if ($preco_max > 0) {
    $sql .= " AND c.preco <= ?";
    $params[] = $preco_max;
    $types .= 'd';
}

$sql .= " ORDER BY $orderSql";

$stmt = mysqli_prepare($conexao, $sql);
if (!$stmt) {
    die("Erro ao preparar catálogo: " . mysqli_error($conexao));
}

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if (!$res) {
    die("Erro ao buscar catálogo: " . mysqli_error($conexao));
}

/*
|--------------------------------------------------------------------------
| CONTAGEM
|--------------------------------------------------------------------------
*/
$totalCarros = 0;
if ($res) {
    $tmpRows = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $tmpRows[] = $row;
    }
    $carros = $tmpRows;
    $totalCarros = count($carros);
} else {
    $carros = [];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/png" href="ImagensRG/logo.png" />
    <title>Carros - RG Auto Sales</title>

    <link rel="stylesheet" href="style.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    <style>
        .btn--outline{
            background:transparent;
            border:2px solid #01203f;
            color:#01203f;
        }
        .btn--outline:hover{
            background:#01203f;
            color:#fff;
        }
        .product-actions{
            display:flex;
            gap:8px;
            flex-wrap:wrap;
            margin-top:10px;
        }
        .btn--small{
            padding:8px 14px;
            font-size:14px;
        }
        .product-card img{
            width:100%;
            height:220px;
            object-fit:cover;
            border-radius:10px;
            background:#f5f5f5;
        }

        .filters-box{
            background:#fff;
            border-radius:16px;
            padding:18px;
            box-shadow:0 4px 18px rgba(0,0,0,.08);
            margin:20px 0 30px;
        }
        .filters-grid{
            display:grid;
            grid-template-columns:repeat(4, 1fr);
            gap:14px;
        }
        .filters-grid .full{
            grid-column:1 / -1;
        }
        .filters-box label{
            display:block;
            font-weight:600;
            margin-bottom:6px;
            color:#01203f;
        }
        .filters-box input,
        .filters-box select{
            width:100%;
            padding:11px 12px;
            border:1px solid #d1d5db;
            border-radius:10px;
            background:#fff;
            box-sizing:border-box;
        }
        .filters-actions{
            display:flex;
            gap:10px;
            flex-wrap:wrap;
            margin-top:16px;
        }
        .catalog-meta{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:10px;
            flex-wrap:wrap;
            margin-bottom:20px;
        }
        .catalog-count{
            color:#6b7280;
            font-weight:600;
        }

        @media (max-width: 1000px){
            .filters-grid{
                grid-template-columns:repeat(2, 1fr);
            }
        }
        @media (max-width: 600px){
            .filters-grid{
                grid-template-columns:1fr;
            }
        }
    </style>
</head>

<body>
<?php include("includes/header_public.php"); ?>

<div class="page-hero">
    <div class="container">
        <div class="row header__hero">
            <div class="col-2">
                <h1>Carros disponíveis</h1>
                <p>Use a busca e os filtros para encontrar a viatura certa mais rápido.</p>

                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <a class="btn"
                       href="https://wa.me/258862934721?text=Olá%20RG%20Auto%20Sales,%20quero%20ver%20carros%20disponíveis."
                       target="_blank" rel="noopener">
                        WhatsApp
                    </a>
                    <a class="btn btn--outline" href="Test_drive.html">Agendar Test Drive</a>
                </div>
            </div>
        </div>
    </div>
</div>

    <div class="small-container">
        <div class="filters-box">
            <form method="GET" action="products.php">
                <div class="filters-grid">
                    <div class="full">
                        <label for="q">Pesquisar</label>
                        <input
                            type="text"
                            id="q"
                            name="q"
                            placeholder="Ex.: Prado, BMW, Hilux..."
                            value="<?= h($q) ?>"
                        >
                    </div>

                    <div>
                        <label for="marca">Marca</label>
                        <select name="marca" id="marca">
                            <option value="">Todas</option>
                            <?php while ($m = mysqli_fetch_assoc($resMarcas)): ?>
                                <?php $marcaDb = trim((string)$m['marca']); ?>
                                <option value="<?= h($marcaDb) ?>" <?= $marca === $marcaDb ? 'selected' : '' ?>>
                                    <?= h($marcaDb) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div>
                        <label for="ano_min">Ano mínimo</label>
                        <input type="number" name="ano_min" id="ano_min" value="<?= $ano_min > 0 ? h((string)$ano_min) : '' ?>">
                    </div>

                    <div>
                        <label for="ano_max">Ano máximo</label>
                        <input type="number" name="ano_max" id="ano_max" value="<?= $ano_max > 0 ? h((string)$ano_max) : '' ?>">
                    </div>

                    <div>
                        <label for="sort">Ordenar</label>
                        <select name="sort" id="sort">
                            <option value="recentes" <?= $sort === 'recentes' ? 'selected' : '' ?>>Mais recentes</option>
                            <option value="menor_preco" <?= $sort === 'menor_preco' ? 'selected' : '' ?>>Menor preço</option>
                            <option value="maior_preco" <?= $sort === 'maior_preco' ? 'selected' : '' ?>>Maior preço</option>
                            <option value="ano_desc" <?= $sort === 'ano_desc' ? 'selected' : '' ?>>Ano mais novo</option>
                            <option value="ano_asc" <?= $sort === 'ano_asc' ? 'selected' : '' ?>>Ano mais antigo</option>
                        </select>
                    </div>

                    <div>
                        <label for="preco_min">Preço mínimo</label>
                        <input type="number" step="0.01" name="preco_min" id="preco_min" value="<?= $preco_min > 0 ? h((string)$preco_min) : '' ?>">
                    </div>

                    <div>
                        <label for="preco_max">Preço máximo</label>
                        <input type="number" step="0.01" name="preco_max" id="preco_max" value="<?= $preco_max > 0 ? h((string)$preco_max) : '' ?>">
                    </div>
                </div>

                <div class="filters-actions">
                    <button type="submit" class="btn">Aplicar filtros</button>
                    <a href="products.php" class="btn btn--outline">Limpar filtros</a>
                </div>
            </form>
        </div>

        <div class="catalog-meta">
            <h2 style="margin:0;">Catálogo</h2>
            <div class="catalog-count"><?= $totalCarros ?> carro(s) encontrado(s)</div>
        </div>

        <div class="row" style="margin-top:20px;">
            <?php if ($totalCarros === 0): ?>
                <p style="padding:10px;">Nenhum carro encontrado com esses filtros.</p>
            <?php else: ?>
                <?php foreach ($carros as $c): ?>
                    <?php
                    $titulo = nomeCarro($c);
                    $img = fotoCarroUrl($c);
                    $preco = precoFmt($c);
                    $ano = (int)($c['ano'] ?? 0);

                    $wa = "https://wa.me/258862934721?text=" . urlencode(
                        "Olá RG Auto Sales, tenho interesse no $titulo ($ano) no valor de $preco MT. Ainda está disponível?"
                    );
                    ?>
                    <div class="col-4 product-card">
                        <a href="product-details.php?id=<?= (int)$c['id'] ?>" class="product-link">
                            <img src="<?= h($img) ?>" alt="<?= h($titulo) ?>" />
                            <h4><?= h($titulo) ?>(<?= $ano ?>)</h4>
                        </a>

                        <p><?= $preco ?> MT</p>

                        <div class="product-actions">
                            <a class="btn btn--small" href="product-details.php?id=<?= (int)$c['id'] ?>">Detalhes</a>
                            <a class="btn btn--outline btn--small" href="Test_drive.html">Test Drive</a>
                            <a class="btn btn--small" href="<?= h($wa) ?>" target="_blank" rel="noopener">WhatsApp</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if($novo): ?>
                <span class="badge-novo">Novo</span>
            <?php endif; ?>
        </div>
    </div>

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

                <div class="footer-col-1">
                    <h3>Links úteis</h3>
                    <ul>
                        <li><a href="products.php">Carros</a></li>
                        <li><a href="Test_drive.html">Test Drive</a></li>
                        <li><a href="vender_carro.html">Vender</a></li>
                        <li><a href="contacto.html">Contactos</a></li>
                    </ul>
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
            menuItems.classList.toggle("show");
        }
    </script>

    <a class="wa-float"
       href="https://wa.me/258862934721?text=Olá%20RG%20Auto%20Sales,%20quero%20informações."
       target="_blank"
       rel="noopener"
       aria-label="Falar no WhatsApp com a RG Auto Sales">
        <i class="fa-brands fa-whatsapp"></i>
        <span>WhatsApp RG</span>
    </a>
</body>
</html> 