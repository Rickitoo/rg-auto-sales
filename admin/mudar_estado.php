<?php
include("../conexao.php");

$id = intval($_GET['id']);
$estado = $_GET['estado'];

mysqli_query($conexao, "
UPDATE clientes SET estado='$estado' WHERE id=$id
");

header("Location: leads.php");