<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}

if (!function_exists('h')) { function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("ID inválido.");

$stmt = mysqli_prepare($conexao, "SELECT * FROM leads WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
if(!$row) die("Lead não encontrado.");

$isImportacao = ($row['origem'] ?? '') === 'importacao';

$pageTitle = 'Detalhe do Lead';
$pageSubtitle = 'Acompanhamento completo da oportunidade';
$contentFile = BASE_PATH . '/app/views/admin/leads/lead_detalhe_content.php';

require BASE_PATH . '/app/views/layouts/admin_layout.php';
