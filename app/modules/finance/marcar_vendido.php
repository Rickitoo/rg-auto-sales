<?php
require_once __DIR__ . '/../../core/bootstrap.php';
require_admin();

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die("ID inválido.");
}

/* ===============================
   BUSCAR CARRO
=============================== */
$stmt = mysqli_prepare($conexao, "SELECT * FROM carros WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if (!$res || mysqli_num_rows($res) === 0) {
    die("Carro não encontrado.");
}

$carro = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

/* ===============================
   PREÇO DE VENDA (pode vir fixo ou input depois)
=============================== */
$preco_venda = (float)$carro['preco'];
$preco_compra = (float)$carro['preco'];

$lucro = $preco_venda - $preco_compra;

$comissao_vendedor = $lucro * 0.15;
$comissao_parceiro = $lucro * 0.10;
$comissao_rg = $lucro - ($comissao_vendedor + $comissao_parceiro);

/* ===============================
   CRIAR VENDA REAL
=============================== */
$stmt = mysqli_prepare($conexao, "
    INSERT INTO vendas (
        cliente_id,
        marca,
        modelo,
        valor_venda,
        preco_compra,
        lucro,
        comissao_vendedor,
        comissao_parceiro,
        comissao_rg,
        status,
        data_venda
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDENTE', NOW())
");

mysqli_stmt_bind_param(
    $stmt,
    "issdddddd",
    $carro['cliente_id'],
    $carro['marca'],
    $carro['modelo'],
    $preco_venda,
    $preco_compra,
    $lucro,
    $comissao_vendedor,
    $comissao_parceiro,
    $comissao_rg
);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    /* ===============================
       MARCAR CARRO COMO INATIVO/VENDIDO
    =============================== */
    $stmt2 = mysqli_prepare($conexao, "
        UPDATE carros 
        SET status='vendido' 
        WHERE id=?
    ");

    mysqli_stmt_bind_param($stmt2, "i", $id);
    mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);

    redirect_to('app/modules/cars/listar_carros.php?success=1');

} else {
    die("Erro ao criar venda.");
}
