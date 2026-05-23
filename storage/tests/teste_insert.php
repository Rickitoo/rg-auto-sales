<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';

$sql = "INSERT INTO clientes (nome, telefone) VALUES ('Cliente Teste', '841234567')";

if (mysqli_query($conexao, $sql)) {
    echo "OK - Cliente inserido";
} else {
    echo "Erro: " . mysqli_error($conexao);
}