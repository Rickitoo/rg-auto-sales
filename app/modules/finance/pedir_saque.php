<?php
require_once __DIR__ . '/../../core/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('public/dashboard.php?msg=metodo_invalido');
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    http_response_code(403);
    exit("CSRF invalido.");
}

$user = current_user();
$user_id = (int)($_SESSION['user_id'] ?? ($user['id'] ?? 0));

if ($user_id <= 0) {
    exit();
}

$stmtWallet = mysqli_prepare($conexao, "
SELECT saldo_disponivel FROM wallet WHERE user_id=? LIMIT 1
");
mysqli_stmt_bind_param($stmtWallet, "i", $user_id);
mysqli_stmt_execute($stmtWallet);
$resWallet = mysqli_stmt_get_result($stmtWallet);
$wallet = $resWallet ? mysqli_fetch_assoc($resWallet) : null;
mysqli_stmt_close($stmtWallet);

if (!$wallet) {
    exit("Carteira nao encontrada.");
}

$valor = (float)($_POST['valor'] ?? 0);

if ($valor <= 0) {
    $erro = "Valor invalido.";
} elseif ($valor > (float)$wallet['saldo_disponivel']) {
    $erro = "Saldo insuficiente.";
} else {
    $stmtSaque = mysqli_prepare($conexao, "
        INSERT INTO saques (user_id, valor)
        VALUES (?, ?)
    ");
    mysqli_stmt_bind_param($stmtSaque, "id", $user_id, $valor);
    mysqli_stmt_execute($stmtSaque);
    mysqli_stmt_close($stmtSaque);

    // desconta do disponivel
    $stmtWalletUpdate = mysqli_prepare($conexao, "
        UPDATE wallet
        SET saldo_disponivel = saldo_disponivel - ?
        WHERE user_id = ?
    ");
    mysqli_stmt_bind_param($stmtWalletUpdate, "di", $valor, $user_id);
    mysqli_stmt_execute($stmtWalletUpdate);
    mysqli_stmt_close($stmtWalletUpdate);

    redirect_to('public/dashboard.php?ok=1');
}
?>
