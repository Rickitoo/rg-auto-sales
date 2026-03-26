<?php
include("auth.php");
ini_set('display_errors', 1);
error_reporting(E_ALL);

include("conexao.php");

$id     = intval($_GET['id'] ?? 0);
$status = $_GET['status'] ?? '';
$token  = $_GET['token'] ?? '';

$permitidos = ['aprovado', 'rejeitado', 'pendente'];

if ($id <= 0 || !in_array($status, $permitidos, true)) {
    die("Pedido inválido.");
}

if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    die("Token inválido (CSRF).");
}

/* 1) Buscar vendedor (inclui carro_id) */
$stmt = mysqli_prepare($conexao, "SELECT * FROM vendedores WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$v = mysqli_fetch_assoc($res);

if (!$v) {
    die("Vendedor não encontrado.");
}

/* 2) Atualizar status */
$stmt2 = mysqli_prepare($conexao, "UPDATE vendedores SET status = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt2, "si", $status, $id);
mysqli_stmt_execute($stmt2);

/* 3) Se aprovado e ainda não tem carro_id -> cria carro UMA vez */
$carroIdAtual = $v['carro_id'] ?? null;

if ($status === "aprovado" && empty($carroIdAtual)) {

    $descricao = trim($v['mensagem'] ?? '');
    if ($descricao === '') {
        $descricao = "Carro aprovado via vendedor RG Auto Sales";
    }

    $stmt3 = mysqli_prepare($conexao, "
        INSERT INTO carros (marca, modelo, ano, preco, descricao)
        VALUES (?, ?, ?, ?, ?)
    ");

    mysqli_stmt_bind_param(
        $stmt3,
        "ssids",
        $v['marca'],
        $v['modelo'],
        $v['ano'],
        $v['preco'],
        $descricao
    );

    mysqli_stmt_execute($stmt3);

    $novoCarroId = mysqli_insert_id($conexao);

    /* 4) Guardar ligação vendedor -> carro */
    $stmt4 = mysqli_prepare($conexao, "UPDATE vendedores SET carro_id = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt4, "ii", $novoCarroId, $id);
    mysqli_stmt_execute($stmt4);
}

header("Location: admin.php?msg=status_ok");
exit;
