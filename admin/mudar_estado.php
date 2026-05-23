<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
require_admin();

$id = intval($_GET['id']);
$estado = $_GET['estado'];

mysqli_query($conexao, "
UPDATE clientes SET estado='$estado' WHERE id=$id
");

redirect_to('admin/leads/leads.php');