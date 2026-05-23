<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

$lead_id = $_POST['lead_id'];
$new_stage = $_POST['stage'];

mysqli_query($conexao, "
    UPDATE leads 
    SET stage='$new_stage'
    WHERE id=$lead_id
");