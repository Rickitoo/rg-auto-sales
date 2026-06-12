<?php
require_once __DIR__ . '/../app/core/bootstrap.php';

if (!function_exists('public_car_placeholder_url')) {
    function public_car_placeholder_url(): string
    {
        return asset('ImagensRG/logo.png');
    }
}

if (!function_exists('public_car_image_url')) {
    function public_car_image_url(array $carro): string
    {
        $imagem = trim((string)($carro['imagem_principal'] ?? $carro['imagem'] ?? ''));
        if ($imagem === '') {
            return public_car_placeholder_url();
        }

        if (preg_match('~^https?://~', $imagem)) {
            return $imagem;
        }

        if (str_starts_with($imagem, '/')) {
            return $imagem;
        }

        $rel = str_starts_with($imagem, 'uploads/') ? $imagem : 'uploads/' . $imagem;
        $abs = BASE_PATH . '/public/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel);

        return is_file($abs) ? public_url($rel) : public_car_placeholder_url();
    }
}

if (!function_exists('public_car_img_fallback_attr')) {
    function public_car_img_fallback_attr(): string
    {
        return "this.onerror=null;this.src='" . h(public_car_placeholder_url()) . "';";
    }
}
