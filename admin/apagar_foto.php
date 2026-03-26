<?php
include("../auth.php");
include("../conexao.php");

$id = intval($_GET['id'] ?? 0);
$carro_id = intval($_GET['carro_id'] ?? 0);

if ($id <= 0) {
    die("Foto inválida.");
}

$sql = "SELECT foto FROM carros_fotos WHERE id = $id LIMIT 1";
$res = mysqli_query($conexao, $sql);

if ($res && mysqli_num_rows($res) > 0) {
    $row = mysqli_fetch_assoc($res);
    $caminho = "../uploads/" . $row['foto'];

    if (!empty($row['foto']) && file_exists($caminho)) {
        unlink($caminho);
    }

    mysqli_query($conexao, "DELETE FROM carros_fotos WHERE id = $id");
}

header("Location: editar_carro.php?id=" . $carro_id);
exit;