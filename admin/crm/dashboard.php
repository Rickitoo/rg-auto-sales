<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

function crm_dash_col_exists(mysqli $con, string $table, string $col): bool {
    $table = mysqli_real_escape_string($con, $table);
    $col = mysqli_real_escape_string($con, $col);
    $q = mysqli_query($con, "SHOW COLUMNS FROM `$table` LIKE '$col'");
    return $q && mysqli_num_rows($q) > 0;
}

function crm_dash_table_exists(mysqli $con, string $table): bool {
    if (function_exists('db_table_exists')) {
        return db_table_exists($con, $table);
    }

    $table = mysqli_real_escape_string($con, $table);
    $q = mysqli_query($con, "SHOW TABLES LIKE '$table'");
    return $q && mysqli_num_rows($q) > 0;
}

function crm_dash_count(mysqli $con, string $sql): int {
    $res = mysqli_query($con, $sql);
    if (!$res) {
        return 0;
    }

    $row = mysqli_fetch_row($res);
    return (int)($row[0] ?? 0);
}

function crm_dash_days_since(?string $date): ?int {
    if (!$date) {
        return null;
    }

    $timestamp = strtotime($date);
    if (!$timestamp) {
        return null;
    }

    return max(0, (int)floor((time() - $timestamp) / 86400));
}

function crm_dash_attention(array $lead): array {
    $status = (string)($lead['status'] ?? '');
    $ultimoFollowup = $lead['ultimo_followup'] ?? null;
    $ultimaAtividade = $ultimoFollowup ?: ($lead['ultima_atividade'] ?? $lead['criado_em'] ?? null);
    $dias = crm_dash_days_since($ultimaAtividade);
    $semFollowup = empty($ultimoFollowup);
    $fechado = in_array($status, ['fechado', 'perdido'], true);
    $label = $status === 'negociacao' ? 'Em negociacao' : ($status === 'novo' ? 'Novo' : ucfirst($status));
    $class = $status === 'negociacao' ? 'pill-neg' : ($status === 'novo' ? 'pill-new' : 'pill-soft');
    $rank = $status === 'negociacao' ? 45 : ($status === 'novo' ? 35 : 10);

    if (!$fechado && $dias !== null && $dias >= 7) {
        $label = 'Urgente';
        $class = 'pill-urgent';
        $rank = 90;
    } elseif (!$fechado && $dias !== null && $dias >= 3) {
        $label = $semFollowup ? 'Sem resposta' : 'Parado';
        $class = $semFollowup ? 'pill-wait' : 'pill-stop';
        $rank = 70;
    }

    return [
        'label' => $label,
        'class' => $class,
        'rank' => $rank,
        'dias' => $dias,
        'urgente' => !$fechado && $dias !== null && $dias >= 7,
        'pendente' => !$fechado && $dias !== null && $dias >= 3,
    ];
}

function crm_dash_car(array $lead): string {
    return trim(($lead['marca'] ?? '') . ' ' . ($lead['modelo'] ?? '') . ' ' . ($lead['ano'] ?? ''));
}

$statuses = [
    'novo' => 'Novo',
    'contactado' => 'Contactado',
    'qualificado' => 'Qualificado',
    'agendado' => 'Agendado',
    'negociacao' => 'Negociacao',
    'fechado' => 'Fechado',
    'perdido' => 'Perdido',
];

$hasFollowups = crm_dash_table_exists($conexao, 'lead_followups');
$hasVendas = crm_dash_table_exists($conexao, 'vendas');
$hasAtualizadoEm = crm_dash_col_exists($conexao, 'leads', 'atualizado_em');
$selectUpdated = $hasAtualizadoEm ? 'atualizado_em' : 'criado_em';
$selectLastFollowup = $hasFollowups
    ? '(SELECT MAX(lf.criado_em) FROM lead_followups lf WHERE lf.lead_id = leads.id)'
    : 'NULL';

$totalLeads = crm_dash_count($conexao, "SELECT COUNT(*) FROM leads");
$leadsNovos = crm_dash_count($conexao, "SELECT COUNT(*) FROM leads WHERE status='novo'");
$leadsNegociacao = crm_dash_count($conexao, "SELECT COUNT(*) FROM leads WHERE status='negociacao'");
$vendasFechadas = $hasVendas ? crm_dash_count($conexao, "SELECT COUNT(*) FROM vendas WHERE status IN ('PAGO','PENDENTE')") : 0;

$sqlLeads = "
    SELECT id, nome, telefone, email, marca, modelo, ano, status, origem, criado_em,
           $selectUpdated AS ultima_atividade,
           $selectLastFollowup AS ultimo_followup
    FROM leads
    ORDER BY id DESC
    LIMIT 300
";

$resLeads = mysqli_query($conexao, $sqlLeads);
$leads = [];
while ($resLeads && $row = mysqli_fetch_assoc($resLeads)) {
    $row['_attention'] = crm_dash_attention($row);
    $leads[] = $row;
}

$leadsUrgentes = array_values(array_filter($leads, fn($lead) => $lead['_attention']['urgente']));
$followupsPendentes = array_values(array_filter($leads, fn($lead) => $lead['_attention']['pendente']));

usort($leadsUrgentes, fn($a, $b) => ($b['_attention']['rank'] <=> $a['_attention']['rank']) ?: ((int)$b['id'] <=> (int)$a['id']));
usort($followupsPendentes, fn($a, $b) => ($b['_attention']['rank'] <=> $a['_attention']['rank']) ?: ((int)$b['id'] <=> (int)$a['id']));

$recentLeads = array_slice($leads, 0, 6);

$funil = [];
foreach ($statuses as $key => $label) {
    $funil[$key] = ['label' => $label, 'total' => 0];
}
foreach ($leads as $lead) {
    $status = $lead['status'] ?? '';
    if (isset($funil[$status])) {
        $funil[$status]['total']++;
    }
}
$maxFunil = max([
    1,
    $funil['novo']['total'] ?? 0,
    $funil['contactado']['total'] ?? 0,
    $funil['qualificado']['total'] ?? 0,
    $funil['agendado']['total'] ?? 0,
    $funil['negociacao']['total'] ?? 0,
    $funil['fechado']['total'] ?? 0,
    $funil['perdido']['total'] ?? 0,
]);

$recentFollowups = [];
if ($hasFollowups) {
    $res = mysqli_query($conexao, "
        SELECT lf.id, lf.lead_id, lf.mensagem, lf.status, lf.admin_nome, lf.criado_em,
               l.nome, l.telefone
        FROM lead_followups lf
        LEFT JOIN leads l ON l.id = lf.lead_id
        ORDER BY lf.criado_em DESC, lf.id DESC
        LIMIT 6
    ");
    while ($res && $row = mysqli_fetch_assoc($res)) {
        $recentFollowups[] = $row;
    }
}

$recentVendas = [];
if ($hasVendas) {
    $res = mysqli_query($conexao, "
        SELECT id, marca, modelo, ano, status, data_venda, criado_em, valor_venda, lucro
        FROM vendas
        ORDER BY criado_em DESC, id DESC
        LIMIT 6
    ");
    while ($res && $row = mysqli_fetch_assoc($res)) {
        $recentVendas[] = $row;
    }
}

$pageTitle = 'CRM Dashboard';
$pageSubtitle = 'Visão central da operação comercial';
$contentFile = BASE_PATH . '/app/views/admin/crm/dashboard_content.php';

require BASE_PATH . '/app/views/layouts/admin_layout.php';
