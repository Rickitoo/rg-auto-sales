<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

function importacao_status_label(string $status): string
{
    $labels = [
        'novo' => 'Novo',
        'contactado' => 'Contactado',
        'orcamento' => 'Orcamento',
        'aguardando_opcoes' => 'Aguardando opcoes',
        'negociacao' => 'Negociacao',
        'pagamento' => 'Pagamento',
        'embarcado' => 'Embarcado',
        'em_transito' => 'Em transito',
        'desalfandegamento' => 'Desalfandegamento',
        'entregue' => 'Entregue',
        'fechado' => 'Fechado',
        'perdido' => 'Perdido',
    ];

    return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function importacao_status_badge(string $status): string
{
    return match ($status) {
        'novo' => 'secondary',
        'contactado' => 'primary',
        'orcamento', 'aguardando_opcoes', 'negociacao' => 'warning',
        'pagamento', 'embarcado', 'em_transito', 'desalfandegamento' => 'info',
        'entregue', 'fechado' => 'success',
        'perdido' => 'danger',
        default => 'dark',
    };
}

$statusFiltro = trim((string)($_GET['status'] ?? ''));
$allowedStatuses = [
    'novo',
    'contactado',
    'orcamento',
    'aguardando_opcoes',
    'negociacao',
    'pagamento',
    'embarcado',
    'em_transito',
    'desalfandegamento',
    'entregue',
    'perdido',
];

if ($statusFiltro !== '' && !in_array($statusFiltro, $allowedStatuses, true)) {
    $statusFiltro = '';
}

$stats = [
    'total' => 0,
    'novos' => 0,
    'negociacao' => 0,
    'transito' => 0,
];

$statsSql = "
    SELECT
        COUNT(*) AS total,
        SUM(status = 'novo') AS novos,
        SUM(status IN ('negociacao','orcamento','aguardando_opcoes')) AS negociacao,
        SUM(status IN ('embarcado','em_transito')) AS transito
    FROM leads
    WHERE origem = 'importacao'
";

$statsRes = mysqli_query($conexao, $statsSql);
if ($statsRes) {
    $stats = array_merge($stats, mysqli_fetch_assoc($statsRes) ?: []);
}

$sql = "
    SELECT id, tipo, nome, telefone, email, mensagem, marca, modelo, ano, origem, status, criado_em, atualizado_em
    FROM leads
    WHERE origem = 'importacao'
";
$params = [];
$types = '';

if ($statusFiltro !== '') {
    $sql .= " AND status = ?";
    $params[] = $statusFiltro;
    $types .= 's';
}

$sql .= " ORDER BY criado_em DESC, id DESC LIMIT 200";

$stmt = mysqli_prepare($conexao, $sql);
if ($stmt && $params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

if ($stmt) {
    mysqli_stmt_execute($stmt);
    $leadsImportacao = mysqli_stmt_get_result($stmt);
} else {
    $leadsImportacao = false;
}

$pageTitle = 'Importacoes';
$pageSubtitle = 'Pedidos de importacao integrados ao CRM de leads';
$contentFile = BASE_PATH . '/app/views/admin/importacoes/index_content.php';

require BASE_PATH . '/app/views/layouts/admin_layout.php';
