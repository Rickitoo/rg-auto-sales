<?php

if (!function_exists('fotoCarroUrl')) {
    function fotoCarroUrl(array $carro): string
    {
        $imagem = trim((string)($carro['imagem_principal'] ?? $carro['imagem'] ?? ''));

        if ($imagem !== '') {
            if (preg_match('~^(https?://|/)~', $imagem)) {
                return $imagem;
            }

            if (str_starts_with($imagem, 'uploads/')) {
                return public_url($imagem);
            }

            return public_url('uploads/' . $imagem);
        }

        return asset('img/sem-foto.jpg');
    }
}

if (!function_exists('getCarros')) {
    function getCarros($conexao)
    {
        return mysqli_query($conexao, "SELECT * FROM carros ORDER BY id DESC");
    }
}

if (!function_exists('getCarroById')) {
    function getCarroById($conexao, $id)
    {
        $id = (int)$id;
        $res = mysqli_query($conexao, "SELECT * FROM carros WHERE id = $id LIMIT 1");

        return $res ? mysqli_fetch_assoc($res) : null;
    }
}
