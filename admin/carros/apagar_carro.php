<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

// admin/apagar_carro.php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('admin/carros/listar_carros.php?msg=metodo_invalido');
}

$csrf = $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    http_response_code(403);
    exit("CSRF invalido.");
}

$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    die("ID invalido.");
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

redirect_to('admin/carros/listar_carros.php');
exit;
