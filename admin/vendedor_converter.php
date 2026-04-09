<?php
include("../auth.php");
include("../conexao.php");
include("auth_check.php");


$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die("ID inválido.");

// Buscar pedido
$stmt = mysqli_prepare($conexao, "SELECT * FROM vendedores WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$pedido = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$pedido) die("Pedido não encontrado.");
if ($pedido['status'] !== 'Aprovado') die("Pedido precisa estar Aprovado.");

// 1️⃣ Criar cliente
$stmt = mysqli_prepare($conexao, "
  INSERT INTO clientes (nome, telefone, email, status)
  VALUES (?, ?, ?, 'NOVO')
");
mysqli_stmt_bind_param($stmt, "sss",
  $pedido['nome'],
  $pedido['telefone'],
  $pedido['email']
);
mysqli_stmt_execute($stmt);
$cliente_id = mysqli_insert_id($conexao);
mysqli_stmt_close($stmt);

// 2️⃣ Criar venda
$stmt = mysqli_prepare($conexao, "
  INSERT INTO vendas (
    cliente_id,
    marca,
    modelo,
    ano,
    valor_venda,
    valor_proprietario,
    total_custos,
    lucro,
    status,
    data_venda,
    criado_em,
    perc_vendedor,
    perc_rg,
    lucro_minimo,
    precisa_aprovacao
  )
  VALUES (?, ?, ?, ?, 0, ?, 0, 0, 'PENDENTE', NOW(), NOW(), 15, 85, 0, 0)
");

mysqli_stmt_bind_param($stmt, "issid",
  $cliente_id,
  $pedido['marca'],
  $pedido['modelo'],
  $pedido['ano'],
  $pedido['preco']
);

mysqli_stmt_execute($stmt);
$venda_id = mysqli_insert_id($conexao);
mysqli_stmt_close($stmt);

// 3️⃣ Atualizar pedido
mysqli_query($conexao, "UPDATE vendedores SET status='Publicado' WHERE id=$id");

header("Location: venda_detalhe.php?id=" . $venda_id . "&msg=criada");
exit;