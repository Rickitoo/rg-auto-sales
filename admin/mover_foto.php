<?php
include("../auth.php");
include("../conexao.php");

$id = intval($_GET['id'] ?? 0);
$dir = $_GET['dir'] ?? '';
$carro_id = intval($_GET['carro_id'] ?? 0);

if ($id <= 0 || !in_array($dir, ['up', 'down'])) {
    die("Parâmetros inválidos.");
}

$sqlAtual = "SELECT id, carro_id, ordem FROM carros_fotos WHERE id = $id LIMIT 1";
$resAtual = mysqli_query($conexao, $sqlAtual);

if (!$resAtual || mysqli_num_rows($resAtual) == 0) {
    die("Foto não encontrada.");
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

header("Location: editar_carro.php?id=" . $carro_id);
exit;