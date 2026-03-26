<?php
include("auth.php");
ini_set('display_errors', 1);
error_reporting(E_ALL);

include("conexao.php");

$id = intval($_POST['id'] ?? 0);
$preco_venda = (float)($_POST['preco_venda'] ?? 0);
$comissao = (float)($_POST['comissao'] ?? 0);
$token = $_POST['token'] ?? '';

if ($id <= 0 || $preco_venda <= 0) {
    die("Dados inválidos.");
}

if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    die("Token inválido (CSRF).");
}

// Atualiza o carro como vendido
$stmt = mysqli_prepare($conexao, "
    UPDATE carros
    SET status='vendido', preco_venda=?, comissao=?, data_venda=NOW()
    WHERE id=?
");
if (!$stmt) die("Erro ao preparar: " . mysqli_error($conexao));

mysqli_stmt_bind_param($stmt, "ddi", $preco_venda, $comissao, $id);
mysqli_stmt_execute($stmt);

mysqli_stmt_close($stmt);
mysqli_close($conexao);

header("Location: admin.php?msg=vendido_ok");
exit;
