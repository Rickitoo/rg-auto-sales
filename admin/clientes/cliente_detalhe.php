<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

$clienteId = (int)($_GET['id'] ?? 0);
if ($clienteId <= 0) {
    redirect_to('admin/clientes/clientes.php');
}

$stmt = mysqli_prepare($conexao, "SELECT * FROM clientes WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $clienteId);
mysqli_stmt_execute($stmt);
$cliente = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$cliente) {
    redirect_to('admin/clientes/clientes.php');
}

$leads = [];
$stmt = mysqli_prepare($conexao, "
    SELECT id, nome, telefone, email, marca, modelo, ano, status, criado_em
    FROM leads
    WHERE telefone=? OR email=?
    ORDER BY criado_em DESC, id DESC
    LIMIT 20
");
$email = (string)($cliente['email'] ?? '');
mysqli_stmt_bind_param($stmt, "ss", $cliente['telefone'], $email);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    $leads[] = $row;
}
mysqli_stmt_close($stmt);

$telefone = preg_replace('/\D+/', '', (string)$cliente['telefone']);
if ($telefone !== '' && !str_starts_with($telefone, '258')) {
    $telefone = '258' . ltrim($telefone, '0');
}

$carro = trim(($cliente['marca'] ?? '') . ' ' . ($cliente['modelo'] ?? '') . ' ' . ($cliente['ano'] ?? ''));
$msg = rawurlencode("Ola {$cliente['nome']}, aqui e a RG Auto Sales. Estamos a dar seguimento ao seu test-drive para $carro.");

$pageTitle = 'Detalhe do Cliente';
$pageSubtitle = 'Histórico, dados e acompanhamento comercial';
$contentFile = BASE_PATH . '/app/views/admin/clientes/cliente_detalhe_content.php';

require BASE_PATH . '/app/views/layouts/admin_layout.php';
