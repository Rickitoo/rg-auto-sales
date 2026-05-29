<?php
require_once __DIR__ . '/../../core/bootstrap.php';
require_admin();

if ($_SESSION['user']['role'] !== 'admin') {
    die("Acesso negado");
}

if (!function_exists('h')) { function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); } }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('app/modules/finance/financeiro.php?msg=metodo_invalido');
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    http_response_code(403);
    exit('CSRF inválido.');
}

$id = intval($_POST['id'] ?? $_POST['venda_id'] ?? 0);
if ($id <= 0) die("ID inválido");

$erro = "";
$sucesso = "";

// =========================
// BUSCAR VENDA
// =========================
$q = mysqli_prepare($conexao, "
    SELECT status, pode_pagar 
    FROM vendas 
    WHERE id=? LIMIT 1
");

mysqli_stmt_bind_param($q, "i", $id);
mysqli_stmt_execute($q);
$res = mysqli_stmt_get_result($q);
$v = mysqli_fetch_assoc($res);
mysqli_stmt_close($q);

if (!$v) die("Venda não encontrada");

// =========================
// PROCESSAR PAGAMENTO
// =========================
if (isset($_POST['confirmar_pagamento'])) {

    $forma = $_POST['forma_pagamento'] ?? '';

    if (!$forma) {
        $erro = "Selecione a forma de pagamento";
    }
    elseif ($v['status'] === "PAGO") {
        $erro = "Venda já paga";
    }
    elseif ((int)$v['pode_pagar'] !== 1) {
        $erro = "Pagamento bloqueado pelo sistema";
    }
    else {

        $up = mysqli_prepare($conexao, "
            UPDATE vendas SET 
                status='PAGO',
                forma_pagamento=?,
                data_pagamento=NOW()
            WHERE id=? LIMIT 1
        ");

        mysqli_stmt_bind_param($up, "si", $forma, $id);

        if (mysqli_stmt_execute($up)) {
            $sucesso = "Pagamento realizado com sucesso!";
        } else {
            $erro = "Erro: " . mysqli_error($conexao);
        }

        mysqli_stmt_close($up);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Confirmar Pagamento</title>
</head>
<body>

<h2>Confirmar Pagamento</h2>

<?php if ($erro): ?>
<p style="color:red"><?= h($erro) ?></p>
<?php endif; ?>

<?php if ($sucesso): ?>
<p style="color:green"><?= h($sucesso) ?></p>
<?php endif; ?>

<form method="POST">

<?= csrf_input() ?>
<input type="hidden" name="id" value="<?= $id ?>">
<input type="hidden" name="confirmar_pagamento" value="1">

<label>Forma de Pagamento</label>
<select name="forma_pagamento" required>
<option value="">Selecionar</option>
<option value="M-Pesa">M-Pesa</option>
<option value="M-Kesh">M-Kesh</option>
<option value="Transferência">Transferência</option>
<option value="Cash">Cash</option>
</select>

<br><br>
<button type="submit">Confirmar Pagamento</button>

</form>

</body>
</html>
