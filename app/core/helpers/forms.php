<?php

if (!function_exists('clean')) {
    function clean($v)
    {
        return trim((string)$v);
    }
}

if (!function_exists('redirect')) {
    function redirect($url)
    {
        redirect_to($url);
    }
}

if (!function_exists('is_post')) {
    function is_post()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
}
