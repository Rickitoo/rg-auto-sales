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

function gerarMensagem($lead) {
    $nome = $lead['nome'];

    switch($lead['status']) {
        case 'novo':
            return "Olá $nome, viu o carro que enviámos?";
        case 'contactado':
            return "Queria saber se ainda tem interesse.";
        case 'negociacao':
            return "Tenho alguém interessado hoje, quer garantir?";
        default:
            return "Posso ajudar em algo?";
    }
}

function lead_status_badge(string $status): string {
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

function lead_status_label(string $status): string {
    $labels = [
        'novo' => 'Novo',
        'contactado' => 'Contactado',
        'qualificado' => 'Qualificado',
        'agendado' => 'Agendado',
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

function lead_col_exists(mysqli $con, string $col): bool {
    $col = mysqli_real_escape_string($con, $col);
    $q = mysqli_query($con, "SHOW COLUMNS FROM leads LIKE '$col'");
    return $q && mysqli_num_rows($q) > 0;
}

$hasProximoFollowup = lead_col_exists($conexao, 'proximo_followup');
$hasProximoContacto = lead_col_exists($conexao, 'proximo_contacto');
$followupExpr = $hasProximoFollowup ? 'proximo_followup' : ($hasProximoContacto ? 'proximo_contacto' : 'NULL');
$followupField = $hasProximoFollowup ? 'proximo_followup' : ($hasProximoContacto ? 'proximo_contacto' : null);

// filtros
$filtro = $_GET['status'] ?? '';
$origemFiltro = $_GET['origem'] ?? '';
$q = $_GET['q'] ?? '';

// contadores
$countRes = mysqli_query($conexao, "
    SELECT 
        COUNT(*) as total,
        SUM(status='novo') as novo,
        SUM(status='contactado') as contactado,
        SUM(status='negociacao') as negociacao,
        SUM(status='fechado') as fechado,
        SUM(status='perdido') as perdido
    FROM leads
");
$count = mysqli_fetch_assoc($countRes);

// query principal
$sql = "
SELECT *,
(
    CASE status
        WHEN 'novo' THEN 10
        WHEN 'contactado' THEN 20
        WHEN 'negociacao' THEN 50
        ELSE 0
    END
    +
    CASE 
        WHEN $followupExpr <= NOW() THEN 50
        ELSE 0
    END
    +
    CASE
        WHEN TIMESTAMPDIFF(HOUR, criado_em, NOW()) <= 24 THEN 20
        ELSE 0
    END
) AS lead_score
FROM leads
WHERE 1
";

if ($filtro) {
    $f = mysqli_real_escape_string($conexao, $filtro);
    $sql .= " AND status='$f'";
}

if ($origemFiltro !== '') {
    $origem = mysqli_real_escape_string($conexao, $origemFiltro);
    $sql .= " AND origem='$origem'";
}

if ($q) {
    $s = mysqli_real_escape_string($conexao, $q);
    $sql .= " AND (nome LIKE '%$s%' OR telefone LIKE '%$s%')";
}

$sql .= " ORDER BY lead_score DESC, id DESC LIMIT 200";

$res = mysqli_query($conexao, $sql);

// follow-up alert
$follow = mysqli_query($conexao, "
    SELECT COUNT(*) as total
    FROM leads
    WHERE status NOT IN ('fechado','perdido')
    AND ($followupExpr IS NULL OR $followupExpr <= NOW())
");
$followCount = $follow ? mysqli_fetch_assoc($follow)['total'] : 0;

$pageTitle = 'Leads';
$pageSubtitle = 'Gestao de oportunidades, follow-ups e conversao comercial';
$contentFile = BASE_PATH . '/app/views/admin/leads/leads_content.php';

require BASE_PATH . '/app/views/layouts/admin_layout.php';
