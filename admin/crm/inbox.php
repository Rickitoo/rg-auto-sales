<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

function crm_col_exists(mysqli $con, string $table, string $col): bool {
    $table = mysqli_real_escape_string($con, $table);
    $col = mysqli_real_escape_string($con, $col);
    $q = mysqli_query($con, "SHOW COLUMNS FROM `$table` LIKE '$col'");
    return $q && mysqli_num_rows($q) > 0;
}

function crm_ensure_followups_table(mysqli $con): void {
    mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS lead_followups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lead_id INT NOT NULL,
            mensagem TEXT NOT NULL,
            status VARCHAR(50) NULL,
            admin_id INT NULL,
            admin_nome VARCHAR(150) NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_lead_followups_lead_id (lead_id),
            INDEX idx_lead_followups_criado_em (criado_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
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

$hasProximoContacto = crm_col_exists($conexao, 'leads', 'proximo_contacto');
$hasProximoFollowup = crm_col_exists($conexao, 'leads', 'proximo_followup');
$hasAtualizadoEm = crm_col_exists($conexao, 'leads', 'atualizado_em');

crm_ensure_followups_table($conexao);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'status') {
    $leadId = (int)($_POST['lead_id'] ?? 0);
    $novoStatus = $_POST['status'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $csrfToken)
    ) {
        http_response_code(403);
        exit('CSRF inválido.');
    }

    if ($leadId > 0 && isset($statuses[$novoStatus])) {
        $stmt = mysqli_prepare($conexao, "UPDATE leads SET status=? WHERE id=? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "si", $novoStatus, $leadId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    redirect_to('admin/crm/inbox.php?id=' . $leadId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'followup') {
    $leadId = (int)($_POST['lead_id'] ?? 0);
    $mensagem = trim((string)($_POST['mensagem'] ?? ''));
    $statusNota = $_POST['status'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $csrfToken)
    ) {
        http_response_code(403);
        exit('CSRF invalido.');
    }

    $user = current_user() ?? [];
    $adminId = isset($user['id']) ? (int)$user['id'] : null;
    $adminNome = $user['nome'] ?? $user['email'] ?? 'Admin';

    if ($leadId > 0 && $mensagem !== '') {
        $statusNota = isset($statuses[$statusNota]) ? $statusNota : null;
        $stmt = mysqli_prepare($conexao, "
            INSERT INTO lead_followups (lead_id, mensagem, status, admin_id, admin_nome)
            VALUES (?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmt, "issis", $leadId, $mensagem, $statusNota, $adminId, $adminNome);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($hasAtualizadoEm) {
            $stmt = mysqli_prepare($conexao, "UPDATE leads SET atualizado_em=NOW() WHERE id=? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "i", $leadId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    redirect_to('admin/crm/inbox.php?id=' . $leadId);
}

$busca = trim($_GET['q'] ?? '');
$statusFiltro = $_GET['status'] ?? '';
$leadSelecionadoId = (int)($_GET['id'] ?? 0);

$selectNext = $hasProximoContacto ? 'proximo_contacto' : ($hasProximoFollowup ? 'proximo_followup' : 'NULL');
$selectUpdated = $hasAtualizadoEm ? 'atualizado_em' : 'criado_em';

$where = [];
$types = '';
$params = [];

if ($busca !== '') {
    $where[] = "(nome LIKE ? OR telefone LIKE ? OR email LIKE ? OR marca LIKE ? OR modelo LIKE ?)";
    $like = '%' . $busca . '%';
    $types .= 'sssss';
    array_push($params, $like, $like, $like, $like, $like);
}

if ($statusFiltro !== '' && isset($statuses[$statusFiltro])) {
    $where[] = "status = ?";
    $types .= 's';
    $params[] = $statusFiltro;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sqlLeads = "
    SELECT id, tipo, nome, telefone, email, mensagem, marca, modelo, ano, carro_id, origem, status,
           criado_em, notas, $selectNext AS proximo_evento, $selectUpdated AS ultima_atividade,
           (SELECT MAX(lf.criado_em) FROM lead_followups lf WHERE lf.lead_id = leads.id) AS ultimo_followup
    FROM leads
    $whereSql
    ORDER BY
        CASE status
            WHEN 'novo' THEN 1
            WHEN 'contactado' THEN 2
            WHEN 'qualificado' THEN 3
            WHEN 'agendado' THEN 4
            WHEN 'negociacao' THEN 5
            WHEN 'fechado' THEN 6
            WHEN 'perdido' THEN 7
            ELSE 8
        END,
        id DESC
    LIMIT 200
";

$stmt = mysqli_prepare($conexao, $sqlLeads);
if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$resLeads = mysqli_stmt_get_result($stmt);

$leads = [];
while ($row = mysqli_fetch_assoc($resLeads)) {
    $leads[] = $row;
}
mysqli_stmt_close($stmt);

function crm_days_since(?string $date): ?int {
    if (!$date) {
        return null;
    }

    $timestamp = strtotime($date);
    if (!$timestamp) {
        return null;
    }

    return max(0, (int)floor((time() - $timestamp) / 86400));
}

function crm_attention(array $lead): array {
    $status = (string)($lead['status'] ?? '');
    $ultimoFollowup = $lead['ultimo_followup'] ?? null;
    $ultimaAtividade = $ultimoFollowup ?: ($lead['ultima_atividade'] ?? $lead['criado_em'] ?? null);
    $dias = crm_days_since($ultimaAtividade);
    $semFollowup = empty($ultimoFollowup);
    $fechado = in_array($status, ['fechado', 'perdido'], true);

    if ($status === 'negociacao') {
        $badge = ['label' => 'Em negociacao', 'class' => 'smart-negociacao', 'rank' => 45];
    } elseif ($status === 'novo') {
        $badge = ['label' => 'Novo', 'class' => 'smart-novo', 'rank' => 35];
    } else {
        $badge = ['label' => status_label([], $status) ?: 'Lead', 'class' => 'smart-normal', 'rank' => 10];
    }

    if (!$fechado && $dias !== null && $dias >= 7) {
        $badge = ['label' => 'Urgente', 'class' => 'smart-urgente', 'rank' => 90];
    } elseif (!$fechado && $dias !== null && $dias >= 3) {
        $badge = [
            'label' => $semFollowup ? 'Sem resposta' : 'Parado',
            'class' => $semFollowup ? 'smart-sem-resposta' : 'smart-parado',
            'rank' => 70,
        ];
    }

    return [
        'badge' => $badge,
        'dias_sem_contacto' => $dias,
        'ultimo_followup' => $ultimoFollowup,
        'ultima_referencia' => $ultimaAtividade,
        'sem_followup' => $semFollowup,
        'esquecido' => !$fechado && $dias !== null && $dias >= 3,
        'urgente' => !$fechado && $dias !== null && $dias >= 7,
    ];
}

foreach ($leads as $index => $lead) {
    $leads[$index]['_crm_attention'] = crm_attention($lead);
}

usort($leads, function (array $a, array $b): int {
    $rankA = (int)($a['_crm_attention']['badge']['rank'] ?? 0);
    $rankB = (int)($b['_crm_attention']['badge']['rank'] ?? 0);

    if ($rankA !== $rankB) {
        return $rankB <=> $rankA;
    }

    return ((int)$b['id']) <=> ((int)$a['id']);
});

if ($leadSelecionadoId <= 0 && $leads) {
    $leadSelecionadoId = (int)$leads[0]['id'];
}

$leadSelecionado = null;
if ($leadSelecionadoId > 0) {
    $stmt = mysqli_prepare($conexao, "
        SELECT id, tipo, nome, telefone, email, mensagem, marca, modelo, ano, carro_id, origem, status,
               criado_em, notas, $selectNext AS proximo_evento, $selectUpdated AS ultima_atividade,
               (SELECT MAX(lf.criado_em) FROM lead_followups lf WHERE lf.lead_id = leads.id) AS ultimo_followup
        FROM leads
        WHERE id=?
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, "i", $leadSelecionadoId);
    mysqli_stmt_execute($stmt);
    $leadSelecionado = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}

$leadAttention = $leadSelecionado ? crm_attention($leadSelecionado) : null;

$followups = [];
if ($leadSelecionado) {
    $stmt = mysqli_prepare($conexao, "
        SELECT id, lead_id, mensagem, status, admin_id, admin_nome, criado_em
        FROM lead_followups
        WHERE lead_id=?
        ORDER BY criado_em DESC, id DESC
        LIMIT 80
    ");
    mysqli_stmt_bind_param($stmt, "i", $leadSelecionadoId);
    mysqli_stmt_execute($stmt);
    $resFollowups = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($resFollowups)) {
        $followups[] = $row;
    }
    mysqli_stmt_close($stmt);
}

function status_label(array $statuses, ?string $status): string {
    return $statuses[$status ?? ''] ?? ucfirst((string)$status);
}

function crm_lead_phone(array $lead): string {
    $tel = preg_replace('/\D+/', '', (string)($lead['telefone'] ?? ''));
    if ($tel !== '' && !str_starts_with($tel, '258')) {
        $tel = '258' . ltrim($tel, '0');
    }

    return $tel;
}

function crm_car_label(array $lead): string {
    return trim(($lead['marca'] ?? '') . ' ' . ($lead['modelo'] ?? '') . ' ' . ($lead['ano'] ?? ''));
}

function smart_whatsapp_message(array $lead, ?array $attention = null): array {
    $nome = $lead['nome'] ?? '';
    $primeiroNome = trim(explode(' ', trim($nome))[0] ?? '');
    $cliente = $primeiroNome !== '' ? $primeiroNome : 'tudo bem';
    $carro = crm_car_label($lead);
    $carroTexto = $carro !== '' ? " sobre o $carro" : '';
    $status = (string)($lead['status'] ?? '');
    $badge = $attention['badge']['label'] ?? '';

    if ($status === 'fechado') {
        return [
            'tipo' => 'pos-venda',
            'label' => 'Pos-venda',
            'texto' => "Ola $cliente, aqui e a RG Auto Sales. Obrigado pela confianca na sua compra$carroTexto. Estou a acompanhar para garantir que ficou tudo bem e ajudar no que precisar.",
        ];
    }

    if ($badge === 'Urgente') {
        return [
            'tipo' => 'urgente',
            'label' => 'Urgente',
            'texto' => "Ola $cliente, aqui e a RG Auto Sales. Estou a tentar fechar o acompanhamento$carroTexto e queria saber se ainda tem interesse. Posso ajudar com detalhes, condicoes ou marcar uma visita hoje?",
        ];
    }

    if (in_array($badge, ['Sem resposta', 'Parado'], true)) {
        return [
            'tipo' => 'sem-resposta',
            'label' => 'Sem resposta',
            'texto' => "Ola $cliente, aqui e a RG Auto Sales. Ficou pendente o nosso contacto$carroTexto. Ainda faz sentido avancarmos? Posso enviar mais informacoes ou sugerir uma alternativa dentro do seu objetivo.",
        ];
    }

    if ($status === 'negociacao') {
        return [
            'tipo' => 'negociacao',
            'label' => 'Negociacao',
            'texto' => "Ola $cliente, aqui e a RG Auto Sales. Sobre a negociacao$carroTexto, posso ajudar a alinhar os proximos passos e confirmar as condicoes para avancarmos?",
        ];
    }

    return [
        'tipo' => 'novo',
        'label' => 'Novo lead',
        'texto' => "Ola $cliente, aqui e a RG Auto Sales. Recebemos o seu pedido$carroTexto e quero ajudar. Pode confirmar qual e o melhor horario para falarmos?",
    ];
}

function whatsapp_url(array $lead, ?array $attention = null): string {
    $tel = crm_lead_phone($lead);
    $message = smart_whatsapp_message($lead, $attention);

    return $tel !== '' ? 'https://wa.me/' . $tel . '?text=' . urlencode($message['texto']) : '#';
}

$pageTitle = 'CRM Inbox';
$pageSubtitle = 'Leads, follow-ups e mensagens comerciais';
$contentFile = BASE_PATH . '/app/views/admin/crm/inbox_content.php';

require BASE_PATH . '/app/views/layouts/admin_layout.php';
