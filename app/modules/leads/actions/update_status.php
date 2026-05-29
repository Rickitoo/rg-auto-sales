<?php
require_once __DIR__ . '/../../../core/bootstrap.php';
require_admin();

$redirectPath = 'admin/admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to($redirectPath . '?msg=metodo_invalido');
}

$token = $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    exit("Token invalido (CSRF).");
}

$id = intval($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';

$permitidos = ['aprovado', 'rejeitado', 'pendente'];

if ($id <= 0 || !in_array($status, $permitidos, true)) {
    die("Pedido invalido.");
}

/* 1) Buscar vendedor (inclui carro_id) */
$stmt = mysqli_prepare($conexao, "SELECT * FROM vendedores WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$v = mysqli_fetch_assoc($res);

if (!$v) {
    die("Vendedor nao encontrado.");
}

/* 2) Atualizar status */
$stmt2 = mysqli_prepare($conexao, "UPDATE vendedores SET status = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt2, "si", $status, $id);
mysqli_stmt_execute($stmt2);

/* 3) Se aprovado e ainda nao tem carro_id -> cria carro UMA vez */
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

    /* 4) Guardar ligacao vendedor -> carro */
    $stmt4 = mysqli_prepare($conexao, "UPDATE vendedores SET carro_id = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt4, "ii", $novoCarroId, $id);
    mysqli_stmt_execute($stmt4);
}

redirect_to($redirectPath . '?msg=status_ok');
