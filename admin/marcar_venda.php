<?php
// admin/marcar_venda.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include("../auth.php");
include("../conexao.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function money($v) {
    return number_format((float)$v, 2, ',', '.') . " MT";
}

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    die("ID inválido.");
}

// Buscar carro
$sql = "SELECT * FROM carros WHERE id = $id LIMIT 1";
$res = mysqli_query($conexao, $sql);

if (!$res || mysqli_num_rows($res) === 0) {
    die("Carro não encontrado.");
}

$carro = mysqli_fetch_assoc($res);
$erro = "";
$sucesso = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
        die("CSRF inválido.");
    }

    $preco_venda = trim($_POST['preco_venda'] ?? '');
    $comissao    = trim($_POST['comissao'] ?? '');
    $data_venda  = trim($_POST['data_venda'] ?? '');

    $preco_venda = str_replace(',', '.', $preco_venda);
    $comissao    = str_replace(',', '.', $comissao);

    $preco_venda_num = is_numeric($preco_venda) ? (float)$preco_venda : 0;
    $comissao_num    = ($comissao !== '' && is_numeric($comissao)) ? (float)$comissao : 0;

    if ($preco_venda_num <= 0) {
        $erro = "Informe um preço de venda válido.";
    } elseif ($data_venda === '') {
        $erro = "Informe a data da venda.";
    } else {
        $stmt = mysqli_prepare($conexao, "
            UPDATE carros
            SET status = 'vendido',
                preco_venda = ?,
                comissao = ?,
                data_venda = ?
            WHERE id = ?
        ");

        if (!$stmt) {
            $erro = "Erro ao preparar atualização.";
        } else {
            mysqli_stmt_bind_param($stmt, "ddsi", $preco_venda_num, $comissao_num, $data_venda, $id);

            if (mysqli_stmt_execute($stmt)) {
                $sucesso = "Venda registada com sucesso.";

                // Recarregar dados atualizados
                $res = mysqli_query($conexao, $sql);
                if ($res && mysqli_num_rows($res) > 0) {
                    $carro = mysqli_fetch_assoc($res);
                }
            } else {
                $erro = "Erro ao guardar venda: " . mysqli_stmt_error($stmt);
            }

            mysqli_stmt_close($stmt);
        }
    }
}

// Imagem de capa
$capa = $carro['imagem'] ?? '';

if (empty($capa)) {
    $resFoto = mysqli_query($conexao, "SELECT foto FROM carros_fotos WHERE carro_id = $id ORDER BY ordem ASC, id ASC LIMIT 1");
    if ($resFoto && mysqli_num_rows($resFoto) > 0) {
        $fotoRow = mysqli_fetch_assoc($resFoto);
        $capa = $fotoRow['foto'];
    }
}

$imgSrc = !empty($capa) ? "../uploads/" . $capa : "";
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Marcar Venda</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        *{box-sizing:border-box}
        body{
            margin:0;
            font-family:Arial, sans-serif;
            background:#f4f6f9;
            color:#1f2937;
        }
        .container{
            max-width:900px;
            margin:0 auto;
            padding:20px;
        }
        .card{
            background:#fff;
            border-radius:16px;
            padding:22px;
            box-shadow:0 4px 18px rgba(0,0,0,.08);
            margin-bottom:20px;
        }
        .title{
            margin:0 0 8px 0;
            font-size:30px;
        }
        .muted{
            color:#6b7280;
        }
        .carro-box{
            display:grid;
            grid-template-columns:160px 1fr;
            gap:20px;
            align-items:start;
        }
        .thumb{
            width:160px;
            height:120px;
            object-fit:cover;
            border-radius:10px;
            border:1px solid #ddd;
            background:#f3f4f6;
        }
        .noimg{
            width:160px;
            height:120px;
            border-radius:10px;
            border:1px solid #ddd;
            background:#f3f4f6;
            display:flex;
            align-items:center;
            justify-content:center;
            color:#666;
            font-size:13px;
        }
        .info p{
            margin:0 0 10px 0;
        }
        form{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:16px;
        }
        .full{
            grid-column:1 / -1;
        }
        label{
            display:block;
            font-weight:bold;
            margin-bottom:6px;
        }
        input{
            width:100%;
            padding:12px 14px;
            border:1px solid #d1d5db;
            border-radius:10px;
            font-size:14px;
            background:#fff;
        }
        .btn{
            display:inline-block;
            padding:12px 16px;
            border:none;
            border-radius:10px;
            text-decoration:none;
            cursor:pointer;
            font-weight:bold;
            text-align:center;
        }
        .btn-primary{ background:#0d6efd; color:#fff; }
        .btn-secondary{ background:#6c757d; color:#fff; }
        .alert{
            padding:12px 15px;
            border-radius:10px;
            margin-bottom:15px;
            font-weight:bold;
        }
        .alert-danger{
            background:#fee2e2;
            color:#991b1b;
        }
        .alert-success{
            background:#dcfce7;
            color:#166534;
        }
        .actions{
            display:flex;
            gap:10px;
            flex-wrap:wrap;
            margin-top:8px;
        }
        @media (max-width: 700px){
            .carro-box{
                grid-template-columns:1fr;
            }
            form{
                grid-template-columns:1fr;
            }
        }
    </style>
</head>
<body>
<div class="container">

    <div class="card">
        <h1 class="title">Marcar Venda</h1>
        <p class="muted">Registar venda de um carro no sistema da RG Auto</p>
    </div>

    <div class="card">
        <div class="carro-box">
            <div>
                <?php if ($imgSrc !== ''): ?>
                    <img src="<?= h($imgSrc) ?>" alt="Capa do carro" class="thumb">
                <?php else: ?>
                    <div class="noimg">Sem foto</div>
                <?php endif; ?>
            </div>

            <div class="info">
                <p><strong>ID:</strong> <?= (int)$carro['id'] ?></p>
                <p><strong>Carro:</strong> <?= h($carro['marca']) ?> <?= h($carro['modelo']) ?></p>
                <p><strong>Ano:</strong> <?= h($carro['ano']) ?></p>
                <p><strong>Preço atual:</strong> <?= money($carro['preco']) ?></p>
                <p><strong>Status atual:</strong> <?= h(ucfirst($carro['status'])) ?></p>
            </div>
        </div>
    </div>

    <div class="card">
        <?php if ($erro !== ''): ?>
            <div class="alert alert-danger"><?= h($erro) ?></div>
        <?php endif; ?>

        <?php if ($sucesso !== ''): ?>
            <div class="alert alert-success"><?= h($sucesso) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">

            <div>
                <label for="preco_venda">Preço de venda (MT)</label>
                <input
                    type="number"
                    step="0.01"
                    name="preco_venda"
                    id="preco_venda"
                    value="<?= h($carro['preco_venda'] ?? '') ?>"
                    required
                >
            </div>

            <div>
                <label for="comissao">Comissão (MT)</label>
                <input
                    type="number"
                    step="0.01"
                    name="comissao"
                    id="comissao"
                    value="<?= h($carro['comissao'] ?? '') ?>"
                >
            </div>

            <div class="full">
                <label for="data_venda">Data da venda</label>
                <input
                    type="datetime-local"
                    name="data_venda"
                    id="data_venda"
                    value="<?= !empty($carro['data_venda']) ? date('Y-m-d\TH:i', strtotime($carro['data_venda'])) : date('Y-m-d\TH:i') ?>"
                    required
                >
            </div>

            <div class="full actions">
                <button type="submit" class="btn btn-primary">Confirmar Venda</button>
                <a href="listar_carros.php" class="btn btn-secondary">Voltar</a>
            </div>
        </form>
    </div>

</div>
</body>
</html>