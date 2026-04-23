<?php

$conexao = mysqli_connect("localhost", "root", "", "rg_auto_sales");

if (!$conexao) {
    die("Erro na conexão com a base de dados: " . mysqli_connect_error());
}