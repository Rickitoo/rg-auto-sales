<?php
$conexao = new mysqli("localhost", "root", "", "rg_auto_sales");

if ($conexao->connect_error) {
    die("Erro DB: " . $conexao->connect_error);
}

$conexao->set_charset("utf8mb4");