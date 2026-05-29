<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}

if (!function_exists('h')) {
function h($v){
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
}

// =============================
// BUSCAR LEADS
// =============================
$origemFiltro = trim((string)($_GET['origem'] ?? ''));
$where = '';

if ($origemFiltro !== '') {
    $origem = mysqli_real_escape_string($conexao, $origemFiltro);
    $where = "WHERE l.origem = '$origem'";
}

$sql = "
    SELECT 
        l.*,
        c.marca,
        c.modelo
    FROM leads l
    LEFT JOIN carros c 
        ON l.carro_id = c.id
    $where
    ORDER BY l.id DESC
";

$result = mysqli_query($conexao, $sql);

if (!$result) {
    die("Erro ao buscar leads: " . mysqli_error($conexao));
}

$pageTitle = 'Listar Leads';
$pageSubtitle = 'Gestão e acompanhamento de oportunidades comerciais';
$contentFile = BASE_PATH . '/app/views/admin/leads/listar_leads_content.php';

require BASE_PATH . '/app/views/layouts/admin_layout.php';
