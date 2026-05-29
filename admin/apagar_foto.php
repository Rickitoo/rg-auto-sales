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

$csrfToken = $_POST['csrf_token'] ?? '';
if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    http_response_code(403);
    exit('CSRF invÃ¡lido.');
}

$id = intval($_POST['id'] ?? 0);
$carro_id = intval($_POST['carro_id'] ?? 0);

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
