<?php
// admin/marcar_vendido.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include("../auth.php");
include("../conexao.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = intval($_GET['id'] ?? 0);
$csrf = $_GET['csrf_token'] ?? '';

if ($id <= 0) {
    die("ID inválido.");
}

if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    die("CSRF inválido.");
}

// Confirmar se o carro existe
$res = mysqli_query($conexao, "SELECT id, status FROM carros WHERE id = $id LIMIT 1");
if (!$res || mysqli_num_rows($res) === 0) {
    die("Carro não encontrado.");
}

// Marcar como vendido
$stmt = mysqli_prepare($conexao, "
    UPDATE carros
    SET status = 'vendido',
        data_venda = NOW()
    WHERE id = ?
");

if (!$stmt) {
    die("Erro ao preparar atualização.");
}

mysqli_stmt_bind_param($stmt, "i", $id);

if (!mysqli_stmt_execute($stmt)) {
    die("Erro ao marcar como vendido: " . mysqli_stmt_error($stmt));
}

mysqli_stmt_close($stmt);

header("Location: listar_carros.php");
exit;