<?php
require_once __DIR__ . '/../app/core/bootstrap.php';

$carro_id = (int)($_POST['carro_id'] ?? 0);
$nome = $_POST['nome'] ?? '';
$telefone = $_POST['telefone'] ?? '';
$mensagem = $_POST['mensagem'] ?? '';
$preco = (float)($_POST['preco'] ?? 0);
$entrada = (float)($_POST['entrada'] ?? 0);
$meses = (int)($_POST['meses'] ?? 0);
$prestacao = (float)($_POST['prestacao'] ?? 0);

$stmt = mysqli_prepare($conexao, "
    INSERT INTO pedidos_leasing
    (carro_id, nome, telefone, mensagem, preco, entrada, meses, prestacao)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
mysqli_stmt_bind_param($stmt, 'isssddid', $carro_id, $nome, $telefone, $mensagem, $preco, $entrada, $meses, $prestacao);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($ok) {
    echo "ok";
} else {
    echo "erro";
}
