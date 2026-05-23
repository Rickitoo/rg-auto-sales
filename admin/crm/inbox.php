<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

function crm_col_exists(mysqli $con, string $table, string $col): bool {
    $table = mysqli_real_escape_string($con, $table);
    $col = mysqli_real_escape_string($con, $col);
    $q = mysqli_query($con, "SHOW COLUMNS FROM `$table` LIKE '$col'");
    return $q && mysqli_num_rows($q) > 0;
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
           criado_em, notas, $selectNext AS proximo_evento, $selectUpdated AS ultima_atividade
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

if ($leadSelecionadoId <= 0 && $leads) {
    $leadSelecionadoId = (int)$leads[0]['id'];
}

$leadSelecionado = null;
if ($leadSelecionadoId > 0) {
    $stmt = mysqli_prepare($conexao, "
        SELECT id, tipo, nome, telefone, email, mensagem, marca, modelo, ano, carro_id, origem, status,
               criado_em, notas, $selectNext AS proximo_evento, $selectUpdated AS ultima_atividade
        FROM leads
        WHERE id=?
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, "i", $leadSelecionadoId);
    mysqli_stmt_execute($stmt);
    $leadSelecionado = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
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
        .lead-item{display:grid;grid-template-columns:42px 1fr;gap:10px;padding:13px 16px;border-bottom:1px solid #eef2f6}
        .lead-item:hover,.lead-item.active{background:#eaf6fb}
        .avatar{width:42px;height:42px;border-radius:50%;background:#00aeef;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:900}
        .lead-main{min-width:0}
        .lead-row{display:flex;justify-content:space-between;gap:8px;align-items:center}
        .lead-name{font-weight:900;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .lead-time{font-size:11px;color:#667085;white-space:nowrap}
        .lead-meta{font-size:13px;color:#667085;margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .badge{display:inline-flex;align-items:center;border-radius:999px;padding:4px 8px;font-size:11px;font-weight:900;text-transform:uppercase}
        .s-novo{background:#e0f2fe;color:#075985}.s-contactado{background:#e0e7ff;color:#3730a3}.s-qualificado{background:#ecfdf3;color:#027a48}.s-agendado{background:#fef3c7;color:#92400e}.s-negociacao{background:#ffedd5;color:#9a3412}.s-fechado{background:#dcfce7;color:#166534}.s-perdido{background:#fee2e2;color:#991b1b}
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
                $carro = trim(($lead['marca'] ?? '') . ' ' . ($lead['modelo'] ?? '') . ' ' . ($lead['ano'] ?? ''));
                $iniciais = mb_strtoupper(mb_substr((string)$lead['nome'], 0, 1));
                ?>
                <a class="lead-item <?= $active ? 'active' : '' ?>" href="<?= h(url('admin/crm/inbox.php?id=' . (int)$lead['id'] . ($busca !== '' ? '&q=' . urlencode($busca) : '') . ($statusFiltro !== '' ? '&status=' . urlencode($statusFiltro) : ''))) ?>">
                    <div class="avatar"><?= h($iniciais ?: 'L') ?></div>
                    <div class="lead-main">
                        <div class="lead-row">
                            <div class="lead-name"><?= h($lead['nome']) ?></div>
                            <div class="lead-time"><?= h(date('d/m', strtotime($lead['ultima_atividade'] ?? $lead['criado_em']))) ?></div>
                        </div>
                        <div class="lead-meta"><?= h($lead['telefone']) ?><?= $carro !== '' ? ' · ' . h($carro) : '' ?></div>
                        <div style="margin-top:7px">
                            <span class="badge s-<?= h($lead['status']) ?>"><?= h(status_label($statuses, $lead['status'])) ?></span>
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
