<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

$sql = "
    SELECT *
    FROM clientes
    ORDER BY data_registo DESC, id DESC
";

$res = mysqli_query($conexao, $sql);

if (!$res) {
    die("Erro ao buscar clientes: " . mysqli_error($conexao));
}

$clientes = [];
$clientesConcluidos = 0;

while ($row = mysqli_fetch_assoc($res)) {
    if (($row['status'] ?? '') === 'CONCLUIDO') {
        $clientesConcluidos++;
    }

    $clientes[] = $row;
}

$totalClientes = count($clientes);
$clientesPendentes = $totalClientes - $clientesConcluidos;

$pageTitle = 'Clientes';
$pageSubtitle = 'Pedidos publicos, test drives e acompanhamento comercial';
$contentFile = BASE_PATH . '/app/views/admin/clientes/clientes_content.php';

require BASE_PATH . '/app/views/layouts/admin_layout.php';
