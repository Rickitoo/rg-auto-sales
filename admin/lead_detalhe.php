<?php
include("../auth.php");
include("../conexao.php");
include("auth_check.php");
include("admin/includes/db.php");
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("ID inválido.");

$stmt = mysqli_prepare($conexao, "SELECT * FROM leads WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
if(!$row) die("Lead não encontrado.");
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <title>Lead #<?=h($row['id'])?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Lead #<?=h($row['id'])?></h3>
    <a class="btn btn-outline-dark" href="funil.php">Voltar</a>
  </div>

  <div class="bg-white rounded shadow-sm p-3">
    <p><b>Nome:</b> <?=h($row['nome'] ?? '')?></p>
    <p><b>Telefone:</b> <?=h($row['telefone'] ?? '')?></p>
    <p><b>Email:</b> <?=h($row['email'] ?? '')?></p>
    <p><b>Tipo:</b> <?=h($row['tipo'] ?? '')?></p>
    <p><b>Status:</b> <?=h($row['status'] ?? '')?></p>
    <p><b>Carro:</b> <?=h(trim(($row['marca']??'').' '.($row['modelo']??'').' '.($row['ano']??'')))?></p>
    <p><b>Mensagem:</b><br><?=nl2br(h($row['mensagem'] ?? ''))?></p>
    <p class="text-muted m-0"><b>Criado em:</b> <?=h($row['criado_em'] ?? '')?></p>
  </div>
</div>
</body>
</html>