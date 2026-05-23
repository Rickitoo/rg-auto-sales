<?php
require_once __DIR__ . '/../../core/bootstrap.php';
require_admin();

// admin/apagar_carro.php

if (!isset($_SESSION['admin'])) {
    redirect_to('auth/login.php');
    exit();
}


if (session_status() === PHP_SESSION_NONE) {
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

redirect_to('app/modules/cars/listar_carros.php');
exit;