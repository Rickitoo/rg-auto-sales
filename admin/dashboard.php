<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
require_admin();

// =========================
// HELPERS
// =========================
function money($v){ return number_format((float)$v, 2, ',', '.') . " MT"; }
function n($v){ return (int)($v ?? 0); }

// =========================
// DATAS
// =========================
$inicioMes = date('Y-m-01');
$fimMes    = date('Y-m-t'); 

// =========================
// KPIs
// =========================
$totalLeads = n(mysqli_fetch_assoc(mysqli_query($conexao,
    "SELECT COUNT(*) as total FROM leads"
))['total']);

$leadsFechados = n(mysqli_fetch_assoc(mysqli_query($conexao,
    "SELECT COUNT(*) as total FROM leads WHERE status='fechado'"
))['total']);

$taxaConversao = $totalLeads > 0 ? ($leadsFechados / $totalLeads) * 100 : 0;

// =========================
// VENDAS DO MÃŠS
// =========================
$stmt = mysqli_prepare($conexao, "
    SELECT COUNT(*) as total,
           SUM(CASE WHEN status='PAGO' THEN comissao_rg ELSE 0 END) as lucro
    FROM vendas
    WHERE DATE(data_venda) BETWEEN ? AND ?
");

mysqli_stmt_bind_param($stmt, "ss", $inicioMes, $fimMes);
mysqli_stmt_execute($stmt);
$r = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$vendasMes = n($r['total']);
$lucroMes  = (float)($r['lucro'] ?? 0);

// =========================
// FOLLOW-UP REAL (ORDENADO POR URGÃŠNCIA)
// =========================
$leadsFollow = [];
$resFollow = mysqli_query($conexao, "
    SELECT *,
    proximo_contacto AS proximo_followup,
    CASE
        WHEN proximo_contacto IS NULL THEN 1
        WHEN proximo_contacto <= NOW() THEN 2
        ELSE 0
    END as prioridade
    FROM leads
    WHERE status NOT IN ('fechado','perdido')
    ORDER BY prioridade DESC, proximo_contacto ASC, id DESC
    LIMIT 20
");

if ($resFollow) {
    while($row = mysqli_fetch_assoc($resFollow)) {
        $leadsFollow[] = $row;
    }
}

// =========================
// TOP CARROS
// =========================
$topCarros = [];
$resTop = mysqli_query($conexao, "
    SELECT marca, modelo, COUNT(*) as total
    FROM vendas
    GROUP BY marca, modelo
    ORDER BY total DESC
    LIMIT 5
");

while($r = mysqli_fetch_assoc($resTop)){
    $topCarros[] = $r;
}

// =========================
// ALERTAS
// =========================
$alertas = [];

if ($taxaConversao < 10) $alertas[] = "Taxa de conversÃ£o baixa";

$pendentes = n(mysqli_fetch_assoc(mysqli_query($conexao,
    "SELECT COUNT(*) as total FROM vendas WHERE status='PENDENTE'"
))['total']);

if ($pendentes > 5) $alertas[] = "Muitas vendas pendentes";

if (count($leadsFollow) > 5) $alertas[] = "Muitos leads sem resposta";

// =========================
// ÃšLTIMAS VENDAS
// =========================
$vendas = [];
$resV = mysqli_query($conexao, "
    SELECT v.id, v.marca, v.modelo, v.comissao_rg, v.status, c.nome as cliente
    FROM vendas v
    JOIN clientes c ON c.id = v.cliente_id
    ORDER BY v.id DESC
    LIMIT 5
");

while($row = mysqli_fetch_assoc($resV)){
    $vendas[] = $row;
}

$pageTitle = 'Dashboard';
$pageSubtitle = 'Visão geral da operação RG Auto Sales';
$contentFile = BASE_PATH . '/app/views/admin/dashboard/dashboard_content.php';

require BASE_PATH . '/app/views/layouts/admin_layout.php';