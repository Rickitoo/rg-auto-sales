<?php

if (!function_exists('h')) {
    function h($v) {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('fotoCarroUrl')) {
    function fotoCarroUrl(array $carro): string {
        $imagem = trim((string)($carro['imagem_principal'] ?? $carro['imagem'] ?? ''));

        if ($imagem !== '') {
            return "uploads/" . $imagem;
        }

        return "assets/img/sem-foto.jpg";
    }
}

function getCarros($conexao) {
    $res = mysqli_query($conexao, "SELECT * FROM carros ORDER BY id DESC");
    return $res;
}

function getCarroById($conexao, $id) {
    $id = (int)$id;
    $res = mysqli_query($conexao, "SELECT * FROM carros WHERE id = $id LIMIT 1");
    return $res ? mysqli_fetch_assoc($res) : null;
}