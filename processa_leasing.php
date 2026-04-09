<?php

include(__DIR__ . "/includes/header_public.php");
include(__DIR__ . "/includes/db.php");

$carro_id = (int)($_POST['carro_id'] ?? 0);
$nome = $_POST['nome'] ?? '';
$telefone = $_POST['telefone'] ?? '';
$mensagem = $_POST['mensagem'] ?? '';
$preco = (float)($_POST['preco'] ?? 0);
$entrada = (float)($_POST['entrada'] ?? 0);
$meses = (int)($_POST['meses'] ?? 0);
$prestacao = (float)($_POST['prestacao'] ?? 0);

$sql = "INSERT INTO pedidos_leasing 
(carro_id, nome, telefone, mensagem, preco, entrada, meses, prestacao)
VALUES 
('$carro_id','$nome','$telefone','$mensagem','$preco','$entrada','$meses','$prestacao')";

if(mysqli_query($conn, $sql)){
    echo "ok";
}else{
    echo "erro";
}