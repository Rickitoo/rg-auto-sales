<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
require_admin();

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}

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

redirect_to('admin/editar_carro.php?id=' . $carro_id);