<?php
// admin/vendedor_apagar.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include("../auth.php");     // se não tiveres auth, remove esta linha
include("../conexao.php");
include("auth_check.php");


if (session_status() === PHP_SESSION_NONE) session_start();

function fail($msg){ die($msg); }

$id    = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$token = $_POST['token'] ?? '';

if ($id <= 0) fail("ID inválido.");

if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
  fail("Ação bloqueada (token inválido).");
}

// 1) Buscar fotos para apagar do disco
$stmt = mysqli_prepare($conexao, "SELECT arquivo FROM vendedores_fotos WHERE vendedor_id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$arquivos = [];
while($r = mysqli_fetch_assoc($res)) {
  $arquivos[] = (string)$r['arquivo'];
}
mysqli_stmt_close($stmt);

// 2) Apagar pedido (CASCADE apaga fotos no BD)
$stmt2 = mysqli_prepare($conexao, "DELETE FROM vendedores WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt2, "i", $id);

if (!mysqli_stmt_execute($stmt2)) {
  mysqli_stmt_close($stmt2);
  fail("Erro ao apagar: " . mysqli_error($conexao));
}
mysqli_stmt_close($stmt2);

// 3) Apagar ficheiros do disco
foreach($arquivos as $relPath) {
  // segurança: só apagar se estiver dentro de uploads/
  if (strpos($relPath, 'uploads/') !== 0) continue;

  $full = realpath(__DIR__ . "/../" . $relPath);
  if ($full && file_exists($full)) {
    @unlink($full);
  }
}

mysqli_close($conexao);

// voltar para lista
header("Location: vendedores_pedidos.php?msg=apagado");
exit;
