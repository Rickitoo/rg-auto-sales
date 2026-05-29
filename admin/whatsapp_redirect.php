<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    exit();
}

$stmt = mysqli_prepare($conexao, "SELECT nome, telefone FROM leads WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$l = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($stmt);

if (!$l) {
    exit();
}

$telefone = preg_replace('/\D/', '', (string)$l['telefone']);
$msg = urlencode("Ola {$l['nome']}, estou a dar seguimento ao seu interesse.");

header("Location: https://wa.me/258{$telefone}?text=$msg");
exit();
