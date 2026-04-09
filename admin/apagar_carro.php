<?php
// admin/apagar_carro.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include("../auth.php");
include("../conexao.php");
include("auth_check.php");
include("admin/includes/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = intval($_GET['id'] ?? 0);
$csrf = $_GET['csrf_token'] ?? '';

if ($id <= 0) {
    die("ID inválido.");
}

if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    die("CSRF inválido.");
}

// Buscar imagem principal do carro
$resCarro = mysqli_query($conexao, "SELECT imagem FROM carros WHERE id = $id LIMIT 1");
if ($resCarro && mysqli_num_rows($resCarro) > 0) {
    $carro = mysqli_fetch_assoc($resCarro);

    if (!empty($carro['imagem'])) {
        $caminho = "../uploads/" . $carro['imagem'];
        if (file_exists($caminho)) {
            unlink($caminho);
        }
    }
}

// Buscar fotos da galeria
$resFotos = mysqli_query($conexao, "SELECT foto FROM carros_fotos WHERE carro_id = $id");
if ($resFotos && mysqli_num_rows($resFotos) > 0) {
    while ($foto = mysqli_fetch_assoc($resFotos)) {
        if (!empty($foto['foto'])) {
            $caminho = "../uploads/" . $foto['foto'];
            if (file_exists($caminho)) {
                unlink($caminho);
            }
        }
    }
}

// Apagar fotos da tabela carros_fotos
mysqli_query($conexao, "DELETE FROM carros_fotos WHERE carro_id = $id");

// Apagar carro da tabela carros
mysqli_query($conexao, "DELETE FROM carros WHERE id = $id");

header("Location: listar_carros.php");
exit;