<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metodo invalido.');
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    http_response_code(403);
    exit('CSRF invalido.');
}

$lead_id = $_POST['lead_id'];
$new_stage = $_POST['stage'];

mysqli_query($conexao, "
    UPDATE leads 
    SET stage='$new_stage'
    WHERE id=$lead_id
");
