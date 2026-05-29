<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
require_admin();

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('admin/carros/listar_carros.php?msg=metodo_invalido');
}

$csrf = $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    http_response_code(403);
    exit("CSRF invalido.");
}

$id = intval($_POST['id'] ?? 0);
$dir = $_POST['dir'] ?? '';
$carro_id = intval($_POST['carro_id'] ?? 0);

if ($id <= 0 || $carro_id <= 0 || !in_array($dir, ['up', 'down'], true)) {
    die("Parametros invalidos.");
}

$sqlAtual = "SELECT id, carro_id, ordem FROM carros_fotos WHERE id = $id LIMIT 1";
$resAtual = mysqli_query($conexao, $sqlAtual);

if (!$resAtual || mysqli_num_rows($resAtual) == 0) {
    die("Foto nao encontrada.");
}

$fotoAtual = mysqli_fetch_assoc($resAtual);
$ordemAtual = (int)$fotoAtual['ordem'];
$carroIdAtual = (int)$fotoAtual['carro_id'];

if ($dir === 'up') {
    $sqlVizinha = "
        SELECT id, ordem
        FROM carros_fotos
        WHERE carro_id = $carroIdAtual
        AND ordem < $ordemAtual
        ORDER BY ordem DESC
        LIMIT 1
    ";
} else {
    $sqlVizinha = "
        SELECT id, ordem
        FROM carros_fotos
        WHERE carro_id = $carroIdAtual
        AND ordem > $ordemAtual
        ORDER BY ordem ASC
        LIMIT 1
    ";
}

$resVizinha = mysqli_query($conexao, $sqlVizinha);

if ($resVizinha && mysqli_num_rows($resVizinha) > 0) {
    $fotoVizinha = mysqli_fetch_assoc($resVizinha);

    $idVizinha = (int)$fotoVizinha['id'];
    $ordemVizinha = (int)$fotoVizinha['ordem'];

    mysqli_query($conexao, "UPDATE carros_fotos SET ordem = $ordemVizinha WHERE id = $id");
    mysqli_query($conexao, "UPDATE carros_fotos SET ordem = $ordemAtual WHERE id = $idVizinha");
}

redirect_to('admin/editar_carro.php?id=' . $carro_id);
