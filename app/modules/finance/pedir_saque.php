<?php
require_once __DIR__ . '/../../core/bootstrap.php';
require_admin();

if (!isset($_SESSION['user_id'])) exit();

$user_id = $_SESSION['user_id'];

$wallet = mysqli_fetch_assoc(mysqli_query($conexao,"
SELECT saldo_disponivel FROM wallet WHERE user_id=$user_id
"));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $valor = (float)$_POST['valor'];

    if ($valor <= 0) {
        $erro = "Valor inválido.";
    } elseif ($valor > $wallet['saldo_disponivel']) {
        $erro = "Saldo insuficiente.";
    } else {

        mysqli_query($conexao,"
        INSERT INTO saques (user_id, valor)
        VALUES ($user_id, $valor)
        ");

        // desconta do disponível
        mysqli_query($conexao,"
        UPDATE wallet 
        SET saldo_disponivel = saldo_disponivel - $valor
        WHERE user_id = $user_id
        ");

        redirect_to('public/dashboard.php?ok=1');
    }
}
?>