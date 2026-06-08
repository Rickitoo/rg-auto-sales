<?php
require_once __DIR__ . '/../app/core/bootstrap.php';

public_require_form_security('leasing', 5, 300);

$carro_id = (int)($_POST['carro_id'] ?? 0);
$nome = trim((string)($_POST['nome'] ?? ''));
$telefone = trim((string)($_POST['telefone'] ?? ''));
$mensagem = trim((string)($_POST['mensagem'] ?? ''));
$preco = (float)($_POST['preco'] ?? 0);
$entrada = (float)($_POST['entrada'] ?? 0);
$meses = (int)($_POST['meses'] ?? 0);
$prestacao = (float)($_POST['prestacao'] ?? 0);

if ($nome === '' || !public_valid_phone($telefone)) {
    http_response_code(422);
    echo "erro";
    exit;
}

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
