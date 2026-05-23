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

function whatsapp_url(array $lead): string {
    $tel = preg_replace('/\D+/', '', (string)($lead['telefone'] ?? ''));
    if ($tel !== '' && !str_starts_with($tel, '258')) {
        $tel = '258' . ltrim($tel, '0');
    }

    $nome = $lead['nome'] ?? '';
    $carro = trim(($lead['marca'] ?? '') . ' ' . ($lead['modelo'] ?? ''));
    $msg = "Ola $nome, aqui e a RG Auto Sales.";
    if ($carro !== '') {
        $msg .= " Estou a dar seguimento ao seu interesse em $carro.";
    }

    return $tel !== '' ? 'https://wa.me/' . $tel . '?text=' . urlencode($msg) : '#';
}
?>
<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CRM Inbox | RG Auto Sales</title>
    <link rel="icon" type="image/png" href="<?= h(asset('ImagensRG/logo.png')) ?>">
    <style>
        *{box-sizing:border-box}
        body{margin:0;font-family:Arial,sans-serif;background:#eef2f6;color:#101828}
        a{text-decoration:none;color:inherit}
        .shell{height:100vh;display:grid;grid-template-columns:360px 1fr}
        .sidebar{background:#fff;border-right:1px solid #d9e0e8;display:flex;flex-direction:column;min-width:0}
        .side-head{padding:18px;border-bottom:1px solid #e5e7eb}
        .brand{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}
        .brand h1{font-size:20px;margin:0}
        .brand a{font-size:13px;font-weight:800;color:#0067b1}
        .filters{display:grid;grid-template-columns:1fr 130px;gap:8px}
        .filters input,.filters select{width:100%;border:1px solid #d0d5dd;border-radius:8px;padding:10px;background:#fff}
        .filters button{grid-column:1/-1;border:0;border-radius:8px;padding:10px;background:#01203f;color:#fff;font-weight:800;cursor:pointer}
        .lead-list{overflow:auto;min-height:0}
        .lead-item{display:grid;grid-template-columns:42px 1fr;gap:10px;padding:13px 16px;border-bottom:1px solid #eef2f6;position:relative}
        .lead-item:hover,.lead-item.active{background:#eaf6fb}
        .lead-item.attention{background:#fffaf0;border-left:4px solid #f79009}
        .lead-item.urgent{background:#fff1f0;border-left:4px solid #d92d20}
        .lead-item.active.attention{background:#fff6e5}.lead-item.active.urgent{background:#fee4e2}
        .avatar{width:42px;height:42px;border-radius:50%;background:#00aeef;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:900}
        .lead-main{min-width:0}
        .lead-row{display:flex;justify-content:space-between;gap:8px;align-items:center}
        .lead-name{font-weight:900;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .lead-time{font-size:11px;color:#667085;white-space:nowrap}
        .lead-meta{font-size:13px;color:#667085;margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .badge{display:inline-flex;align-items:center;border-radius:999px;padding:4px 8px;font-size:11px;font-weight:900;text-transform:uppercase}
        .s-novo{background:#e0f2fe;color:#075985}.s-contactado{background:#e0e7ff;color:#3730a3}.s-qualificado{background:#ecfdf3;color:#027a48}.s-agendado{background:#fef3c7;color:#92400e}.s-negociacao{background:#ffedd5;color:#9a3412}.s-fechado{background:#dcfce7;color:#166534}.s-perdido{background:#fee2e2;color:#991b1b}
        .smart{display:inline-flex;align-items:center;border-radius:999px;padding:4px 8px;font-size:11px;font-weight:900;text-transform:uppercase}
        .smart-novo{background:#e0f2fe;color:#075985}.smart-urgente{background:#fee2e2;color:#991b1b}.smart-sem-resposta{background:#fef3c7;color:#92400e}.smart-parado{background:#ffedd5;color:#9a3412}.smart-negociacao{background:#ede9fe;color:#5b21b6}.smart-normal{background:#f2f4f7;color:#344054}
        .lead-signals{display:flex;gap:6px;flex-wrap:wrap;margin-top:7px}.days{font-size:11px;color:#667085;margin-top:5px}
        .detail{display:flex;flex-direction:column;min-width:0}
        .topbar{height:72px;background:#fff;border-bottom:1px solid #d9e0e8;display:flex;align-items:center;justify-content:space-between;padding:0 22px;gap:16px}
        .title h2{margin:0;font-size:21px}.title p{margin:4px 0 0;color:#667085;font-size:13px}
        .actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
        .btn{border:0;border-radius:8px;padding:10px 12px;font-weight:900;cursor:pointer;display:inline-flex;align-items:center;justify-content:center}
        .btn-green{background:#12b76a;color:#fff}.btn-blue{background:#00aeef;color:#fff}.btn-dark{background:#01203f;color:#fff}.btn-light{background:#fff;border:1px solid #d0d5dd;color:#344054}
        .content{padding:22px;overflow:auto}
        .panel{background:#fff;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:16px}
        .panel-head{padding:15px 16px;border-bottom:1px solid #eef2f6;font-weight:900}
        .panel-body{padding:16px}
        .info-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
        .info{background:#f8fafc;border:1px solid #eef2f6;border-radius:8px;padding:12px}
        .info span{display:block;color:#667085;font-size:12px;margin-bottom:5px}.info strong{display:block;font-size:14px;word-break:break-word}
        .message{background:#dcf8c6;border-radius:8px;padding:14px;line-height:1.5;white-space:pre-wrap}
        .status-form{display:flex;gap:8px;flex-wrap:wrap}.status-form select{border:1px solid #d0d5dd;border-radius:8px;padding:10px;min-width:180px}
        .note-form{display:grid;gap:10px}.note-form textarea{width:100%;min-height:92px;resize:vertical;border:1px solid #d0d5dd;border-radius:8px;padding:12px;font:inherit;line-height:1.45}.note-form-row{display:flex;gap:8px;align-items:center;justify-content:space-between;flex-wrap:wrap}.note-form select{border:1px solid #d0d5dd;border-radius:8px;padding:10px;min-width:180px;background:#fff}
        .timeline{display:grid;gap:12px}.timeline-item{display:grid;grid-template-columns:38px 1fr;gap:10px;align-items:start}.timeline-dot{width:38px;height:38px;border-radius:50%;background:#01203f;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:13px}.timeline-card{background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:12px}.timeline-top{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:8px}.timeline-user{font-weight:900}.timeline-date{font-size:12px;color:#667085;white-space:nowrap}.timeline-text{white-space:pre-wrap;line-height:1.5;color:#1d2939}.timeline-empty{border:1px dashed #d0d5dd;border-radius:8px;padding:16px;color:#667085;background:#f8fafc}
        .empty{height:100%;display:flex;align-items:center;justify-content:center;color:#667085;text-align:center;padding:30px}
        @media(max-width:900px){.shell{height:auto;min-height:100vh;grid-template-columns:1fr}.sidebar{max-height:46vh}.topbar{height:auto;align-items:flex-start;flex-direction:column;padding:16px}.actions{justify-content:flex-start}.info-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="side-head">
            <div class="brand">
                <h1>CRM Inbox</h1>
                <a href="<?= h(url('admin/dashboard.php')) ?>">Dashboard</a>
            </div>
            <form class="filters" method="GET" action="<?= h(url('admin/crm/inbox.php')) ?>">
                <input type="text" name="q" value="<?= h($busca) ?>" placeholder="Pesquisar lead">
                <select name="status">
                    <option value="">Todos</option>
                    <?php foreach ($statuses as $key => $label): ?>
                        <option value="<?= h($key) ?>" <?= $statusFiltro === $key ? 'selected' : '' ?>><?= h($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Filtrar</button>
            </form>
        </div>

        <div class="lead-list">
            <?php foreach ($leads as $lead): ?>
                <?php
                $active = (int)$lead['id'] === $leadSelecionadoId;
                $attention = $lead['_crm_attention'];
                $attentionClass = $attention['urgente'] ? 'urgent' : ($attention['esquecido'] ? 'attention' : '');
                $carro = trim(($lead['marca'] ?? '') . ' ' . ($lead['modelo'] ?? '') . ' ' . ($lead['ano'] ?? ''));
                $iniciais = mb_strtoupper(mb_substr((string)$lead['nome'], 0, 1));
                ?>
                <a class="lead-item <?= $active ? 'active' : '' ?> <?= h($attentionClass) ?>" href="<?= h(url('admin/crm/inbox.php?id=' . (int)$lead['id'] . ($busca !== '' ? '&q=' . urlencode($busca) : '') . ($statusFiltro !== '' ? '&status=' . urlencode($statusFiltro) : ''))) ?>">
                    <div class="avatar"><?= h($iniciais ?: 'L') ?></div>
                    <div class="lead-main">
                        <div class="lead-row">
                            <div class="lead-name"><?= h($lead['nome']) ?></div>
                            <div class="lead-time"><?= h(date('d/m', strtotime($lead['ultima_atividade'] ?? $lead['criado_em']))) ?></div>
                        </div>
                        <div class="lead-meta"><?= h($lead['telefone']) ?><?= $carro !== '' ? ' · ' . h($carro) : '' ?></div>
                        <div class="lead-signals">
                            <span class="badge s-<?= h($lead['status']) ?>"><?= h(status_label($statuses, $lead['status'])) ?></span>
                            <span class="smart <?= h($attention['badge']['class']) ?>"><?= h($attention['badge']['label']) ?></span>
                        </div>
                        <div class="days">
                            <?= $attention['dias_sem_contacto'] !== null ? h($attention['dias_sem_contacto'] . ' dia(s) sem contacto') : 'Sem historico' ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </aside>

    <main class="detail">
        <?php if ($leadSelecionado): ?>
            <?php
            $carroSelecionado = trim(($leadSelecionado['marca'] ?? '') . ' ' . ($leadSelecionado['modelo'] ?? '') . ' ' . ($leadSelecionado['ano'] ?? ''));
            $fecharVendaUrl = 'admin/vendas/marcar_venda.php?lead_id=' . (int)$leadSelecionado['id'];
            if (!empty($leadSelecionado['carro_id'])) {
                $fecharVendaUrl .= '&carro_id=' . (int)$leadSelecionado['carro_id'];
            }
            ?>
            <div class="topbar">
                <div class="title">
                    <h2><?= h($leadSelecionado['nome']) ?></h2>
                    <p><?= h($leadSelecionado['telefone']) ?><?= $leadSelecionado['email'] ? ' · ' . h($leadSelecionado['email']) : '' ?></p>
                </div>
                <div class="actions">
                    <a class="btn btn-green" href="<?= h(whatsapp_url($leadSelecionado)) ?>" target="_blank" rel="noopener">WhatsApp</a>
                    <a class="btn btn-blue" href="<?= h(url($fecharVendaUrl)) ?>">Fechar venda</a>
                    <a class="btn btn-light" href="<?= h(url('admin/leads/leads.php')) ?>">Lista de leads</a>
                </div>
            </div>

            <div class="content">
                <div class="panel">
                    <div class="panel-head">Resumo do lead</div>
                    <div class="panel-body">
                        <div class="info-grid">
                            <div class="info"><span>Status</span><strong><span class="badge s-<?= h($leadSelecionado['status']) ?>"><?= h(status_label($statuses, $leadSelecionado['status'])) ?></span></strong></div>
                            <div class="info"><span>Origem</span><strong><?= h($leadSelecionado['origem'] ?? '-') ?></strong></div>
                            <div class="info"><span>Tipo</span><strong><?= h($leadSelecionado['tipo'] ?? '-') ?></strong></div>
                            <div class="info"><span>Carro</span><strong><?= h($carroSelecionado !== '' ? $carroSelecionado : '-') ?></strong></div>
                            <div class="info"><span>Criado em</span><strong><?= h(date('d/m/Y H:i', strtotime($leadSelecionado['criado_em']))) ?></strong></div>
                            <div class="info"><span>Proximo contacto</span><strong><?= h(!empty($leadSelecionado['proximo_evento']) ? date('d/m/Y H:i', strtotime($leadSelecionado['proximo_evento'])) : '-') ?></strong></div>
                            <div class="info"><span>Ultimo follow-up</span><strong><?= h(!empty($leadSelecionado['ultimo_followup']) ? date('d/m/Y H:i', strtotime($leadSelecionado['ultimo_followup'])) : 'Sem follow-up') ?></strong></div>
                            <div class="info"><span>Dias sem contacto</span><strong><?= h($leadAttention && $leadAttention['dias_sem_contacto'] !== null ? $leadAttention['dias_sem_contacto'] . ' dia(s)' : '-') ?></strong></div>
                            <div class="info"><span>Prioridade CRM</span><strong><?php if ($leadAttention): ?><span class="smart <?= h($leadAttention['badge']['class']) ?>"><?= h($leadAttention['badge']['label']) ?></span><?php else: ?>-<?php endif; ?></strong></div>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-head">Acoes rapidas</div>
                    <div class="panel-body">
                        <form class="status-form" method="POST" action="<?= h(url('admin/crm/inbox.php')) ?>">
                            <input type="hidden" name="acao" value="status">
                            <input type="hidden" name="lead_id" value="<?= (int)$leadSelecionado['id'] ?>">
                            <select name="status">
                                <?php foreach ($statuses as $key => $label): ?>
                                    <option value="<?= h($key) ?>" <?= $leadSelecionado['status'] === $key ? 'selected' : '' ?>><?= h($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-dark" type="submit">Alterar status</button>
                            <a class="btn btn-light" href="<?= h(url('admin/leads/ver_lead.php?id=' . (int)$leadSelecionado['id'])) ?>">Abrir detalhe classico</a>
                        </form>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-head">Mensagem inicial</div>
                    <div class="panel-body">
                        <div class="message"><?= h($leadSelecionado['mensagem'] ?: 'Sem mensagem registada.') ?></div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-head">Adicionar follow-up</div>
                    <div class="panel-body">
                        <form class="note-form" method="POST" action="<?= h(url('admin/crm/inbox.php')) ?>">
                            <input type="hidden" name="acao" value="followup">
                            <input type="hidden" name="lead_id" value="<?= (int)$leadSelecionado['id'] ?>">
                            <textarea name="mensagem" placeholder="Registar nota, chamada, resposta do cliente ou proximo passo..." required></textarea>
                            <div class="note-form-row">
                                <select name="status">
                                    <option value="">Status desta nota</option>
                                    <?php foreach ($statuses as $key => $label): ?>
                                        <option value="<?= h($key) ?>" <?= $leadSelecionado['status'] === $key ? 'selected' : '' ?>><?= h($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-dark" type="submit">Guardar nota</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-head">Historico de acompanhamento</div>
                    <div class="panel-body">
                        <?php if ($followups): ?>
                            <div class="timeline">
                                <?php foreach ($followups as $item): ?>
                                    <?php
                                    $autor = $item['admin_nome'] ?: 'Admin';
                                    $inicial = mb_strtoupper(mb_substr((string)$autor, 0, 1));
                                    ?>
                                    <div class="timeline-item">
                                        <div class="timeline-dot"><?= h($inicial ?: 'A') ?></div>
                                        <div class="timeline-card">
                                            <div class="timeline-top">
                                                <div>
                                                    <div class="timeline-user"><?= h($autor) ?></div>
                                                    <?php if (!empty($item['status'])): ?>
                                                        <span class="badge s-<?= h($item['status']) ?>"><?= h(status_label($statuses, $item['status'])) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="timeline-date"><?= h(date('d/m/Y H:i', strtotime($item['criado_em']))) ?></div>
                                            </div>
                                            <div class="timeline-text"><?= h($item['mensagem']) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="timeline-empty">Ainda nao ha follow-ups registados para este lead.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-head">Notas internas</div>
                    <div class="panel-body">
                        <div class="message" style="background:#f8fafc"><?= h($leadSelecionado['notas'] ?: 'Sem notas internas.') ?></div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="empty">
                <div>
                    <h2>Nenhum lead encontrado</h2>
                    <p>Quando existirem leads, eles aparecem nesta caixa de entrada.</p>
                    <a class="btn btn-dark" href="<?= h(url('admin/leads/leads.php')) ?>">Voltar para leads</a>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
