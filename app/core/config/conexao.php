<?php
$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "rg_auto_sales"; // ✅ nome do banco phpMyAdmin

$conexao = mysqli_connect($servidor, $usuario, $senha, $banco);

if (!$conexao) {
    die("Erro de conexão: " . mysqli_connect_error());
}
?>
