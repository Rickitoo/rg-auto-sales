<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

$conexao = mysqli_connect("localhost", "root", "", "rg_auto_sales");

if (!$conexao) {
    die("Erro na conexão com a base de dados: " . mysqli_connect_error());
}