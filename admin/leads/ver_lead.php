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

$lead_id = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (
        !is_string($csrfToken) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $csrfToken)
    ) {
        http_response_code(403);
        exit('CSRF invalido.');
    }
}

if ($lead_id <= 0) {
    die("Lead inválido");
}

// ======================
// BUSCAR LEAD
// ======================
$stmt = mysqli_prepare($conexao, "
    SELECT * 
    FROM leads 
    WHERE id=?
");

mysqli_stmt_bind_param($stmt, "i", $lead_id);
mysqli_stmt_execute($stmt);

$lead = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

mysqli_stmt_close($stmt);

if (!$lead) {
    die("Lead não encontrado");
}

// ======================
// STATUS COLORIDO
// ======================
$statusClass = match($lead['status']) {

    'Novo Lead' => 'novo',
    'Contactado' => 'contactado',
    'Interessado' => 'interessado',
    'Negociação' => 'negociacao',
    'Aguardando Pagamento' => 'pagamento',
    'Fechado' => 'fechado',
    'Perdido' => 'perdido',

    default => 'padrao'
};

$statusClass = match($lead['status']) {
    'novo', 'Novo Lead' => 'novo',
    'contactado', 'Contactado' => 'contactado',
    'negociacao', 'NegociaÃ§Ã£o' => 'negociacao',
    'orcamento' => 'orcamento',
    'aguardando_opcoes' => 'aguardando-opcoes',
    'pagamento', 'Aguardando Pagamento' => 'pagamento',
    'embarcado' => 'embarcado',
    'em_transito' => 'em-transito',
    'desalfandegamento' => 'desalfandegamento',
    'entregue' => 'entregue',
    'fechado', 'Fechado' => 'fechado',
    'perdido', 'Perdido' => 'perdido',
    default => $statusClass,
};

// ======================
// ENVIAR MENSAGEM
// ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (
        !is_string($csrfToken) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $csrfToken)
    ) {
        http_response_code(403);
        exit('CSRF invalido.');
    }

    $mensagem = trim($_POST['mensagem'] ?? '');

    if ($mensagem !== '') {

        // guardar mensagem
        $stmt = mysqli_prepare($conexao, "
            INSERT INTO mensagens (
                lead_id,
                mensagem,
                tipo
            )
            VALUES (?, ?, 'enviada')
        ");

        mysqli_stmt_bind_param(
            $stmt,
            "is",
            $lead_id,
            $mensagem
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // atualizar lead
        $stmt = mysqli_prepare($conexao, "
            UPDATE leads
            SET 
                ultima_interacao = NOW(),
                proximo_followup = DATE_ADD(NOW(), INTERVAL 1 DAY)
            WHERE id=?
        ");

        mysqli_stmt_bind_param($stmt, "i", $lead_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        redirect_to('admin/leads/ver_lead.php?id=' . $lead_id);
        exit();
    }
}

// ======================
// MENSAGENS
// ======================
$stmt = mysqli_prepare($conexao, "
    SELECT *
    FROM mensagens
    WHERE lead_id=?
    ORDER BY id ASC
");

mysqli_stmt_bind_param($stmt, "i", $lead_id);
mysqli_stmt_execute($stmt);

$mensagens = mysqli_stmt_get_result($stmt);

mysqli_stmt_close($stmt);

// ======================
// WHATSAPP
// ======================
$telefoneWhatsapp = preg_replace(
    '/[^0-9]/',
    '',
    $lead['telefone']
);

$pageTitle = 'Detalhe do Lead';
$pageSubtitle = 'Mensagens, follow-ups e acompanhamento comercial';
$contentFile = BASE_PATH . '/app/views/admin/leads/ver_lead_content.php';

require BASE_PATH . '/app/views/layouts/admin_layout.php';
