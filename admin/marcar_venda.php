<?php
require_once(__DIR__ . "/../init.php");

if (!isset($_SESSION['admin'])) {
    header("Location: /RG_AUTO_SALES/login.php");
    exit();
}

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function money($v) {
    return number_format((float)$v, 2, ',', '.') . " MT";
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die("ID inválido.");
}

/* ===============================
   BUSCAR CARRO
=============================== */
$stmt = mysqli_prepare($conexao, "SELECT * FROM carros WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if (!$res || mysqli_num_rows($res) === 0) {
    die("Carro não encontrado.");
}

$carro = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

$erro = "";
$sucesso = "";

/* ===============================
   SUBMIT VENDA
=============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF inválido.");
    }

    $preco_venda = (float) str_replace(',', '.', $_POST['preco_venda'] ?? 0);
    $data_venda  = $_POST['data_venda'] ?? '';

    if ($preco_venda <= 0) {
        $erro = "Preço de venda inválido.";
    } elseif (empty($data_venda)) {
        $erro = "Data inválida.";
    } else {

        $preco_compra = (float)$carro['preco'];

        /* ===============================
           CÁLCULO DE COMISSÕES
        =============================== */
        $lucro = $preco_venda - $preco_compra;

        $comissao_vendedor = $lucro * 0.15;
        $comissao_parceiro = $lucro * 0.10;
        $comissao_rg = $lucro - ($comissao_vendedor + $comissao_parceiro);

        /* ===============================
           INSERIR VENDA
        =============================== */
        $stmt = mysqli_prepare($conexao, "
            INSERT INTO vendas (
                cliente_id,
                marca,
                modelo,
                valor_venda,
                preco_compra,
                lucro,
                comissao_vendedor,
                comissao_parceiro,
                comissao_rg,
                status,
                data_venda
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDENTE', ?)
        ");

        mysqli_stmt_bind_param(
            $stmt,
            "issdddddds",
            $carro['cliente_id'],
            $carro['marca'],
            $carro['modelo'],
            $preco_venda,
            $preco_compra,
            $lucro,
            $comissao_vendedor,
            $comissao_parceiro,
            $comissao_rg,
            $data_venda
        );

        if (mysqli_stmt_execute($stmt)) {

            mysqli_stmt_close($stmt);

            /* ===============================
               MARCAR CARRO COMO VENDIDO
            =============================== */
            $stmt2 = mysqli_prepare($conexao, "UPDATE carros SET status='vendido' WHERE id=?");
            mysqli_stmt_bind_param($stmt2, "i", $id);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_close($stmt2);

            $sucesso = "Venda registada com sucesso.";

        } else {
            $erro = "Erro ao registar venda.";
        }
    }
}

/* ===============================
   CSRF
=============================== */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Marcar Venda</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
body{font-family:Arial;background:#f4f6f9;margin:0;padding:20px}
.container{max-width:900px;margin:auto}
.card{background:#fff;padding:20px;border-radius:12px;margin-bottom:15px}
input{width:100%;padding:10px;margin-top:6px;border:1px solid #ccc;border-radius:8px}
button{padding:12px;border:0;background:#0d6efd;color:#fff;border-radius:8px;cursor:pointer}
.alert{padding:10px;border-radius:8px;margin-bottom:10px}
.error{background:#fee2e2}
.success{background:#dcfce7}
</style>

</head>
<body>

<div class="container">

<h2>Marcar Venda</h2>

<?php if ($erro): ?>
<div class="alert error"><?= h($erro) ?></div>
<?php endif; ?>

<?php if ($sucesso): ?>
<div class="alert success"><?= h($sucesso) ?></div>
<?php endif; ?>

<div class="card">
<p><strong>Carro:</strong> <?= h($carro['marca']) ?> <?= h($carro['modelo']) ?></p>
<p><strong>Preço base:</strong> <?= money($carro['preco']) ?></p>
</div>

<div class="card">
<form method="POST">

<input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">

<label>Preço de Venda</label>
<input type="number" step="0.01" name="preco_venda" required>

<label>Data da Venda</label>
<input type="datetime-local" name="data_venda" required>

<br><br>
<button type="submit">Confirmar Venda</button>

</form>
</div>

</div>

</body>
</html>