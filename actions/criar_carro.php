<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
require_admin();

if (!is_post()) {
    http_response_code(405);
    exit('Metodo invalido');
}

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('CSRF invalido');
}

$marca = clean($_POST['marca']);
$modelo = clean($_POST['modelo']);
$ano = (int)$_POST['ano'];
$preco = (float)$_POST['preco'];

if (!$marca || !$modelo || $ano <= 0 || $preco <= 0) {
    redirect("/views/error.php?msg=Dados inválidos");
}

$stmt = $conexao->prepare("
    INSERT INTO carros (marca, modelo, ano, preco, status, criado_em)
    VALUES (?, ?, ?, ?, 'disponivel', NOW())
");

$stmt->bind_param("sssd", $marca, $modelo, $ano, $preco);
$stmt->execute();

redirect("/views/success.php?msg=Carro criado com sucesso");
