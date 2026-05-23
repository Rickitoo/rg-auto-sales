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
?>
<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard CRM | RG Auto Sales</title>
    <link rel="icon" type="image/png" href="<?= h(asset('ImagensRG/logo.png')) ?>">
    <style>
        *{box-sizing:border-box}
        body{margin:0;font-family:Arial,sans-serif;background:#eef2f6;color:#101828}
        a{text-decoration:none;color:inherit}
        .page{min-height:100vh}
        .top{background:#fff;border-bottom:1px solid #d9e0e8;padding:18px 24px;display:flex;align-items:center;justify-content:space-between;gap:16px}
        .brand{display:flex;align-items:center;gap:12px}
        .brand img{width:42px;height:42px;object-fit:contain}
        .brand h1{margin:0;font-size:23px}.brand p{margin:3px 0 0;color:#667085;font-size:13px}
        .quick{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
        .btn{border:0;border-radius:8px;padding:10px 12px;font-weight:900;display:inline-flex;align-items:center;justify-content:center;cursor:pointer}
        .btn-dark{background:#01203f;color:#fff}.btn-blue{background:#00aeef;color:#fff}.btn-light{background:#fff;border:1px solid #d0d5dd;color:#344054}.btn-green{background:#12b76a;color:#fff}
        .wrap{padding:22px;display:grid;gap:18px}
        .kpis{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px}
        .kpi{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:15px;min-width:0}
        .kpi span{display:block;color:#667085;font-size:12px;font-weight:800;text-transform:uppercase}.kpi strong{display:block;margin-top:8px;font-size:28px;color:#01203f}
        .grid{display:grid;grid-template-columns:1.2fr .8fr;gap:18px;align-items:start}
        .panel{background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden}
        .panel-head{padding:15px 16px;border-bottom:1px solid #eef2f6;display:flex;align-items:center;justify-content:space-between;gap:12px}
        .panel-head h2{margin:0;font-size:16px}.panel-head a{font-size:13px;font-weight:900;color:#0067b1}
        .panel-body{padding:14px 16px}
        .list{display:grid;gap:10px}
        .item{display:grid;grid-template-columns:1fr auto;gap:10px;align-items:center;border:1px solid #eef2f6;background:#f8fafc;border-radius:8px;padding:11px}
        .item-main{min-width:0}.item-title{font-weight:900;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.item-meta{margin-top:4px;color:#667085;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .pill{display:inline-flex;align-items:center;border-radius:999px;padding:4px 8px;font-size:11px;font-weight:900;text-transform:uppercase;white-space:nowrap}
        .pill-new{background:#e0f2fe;color:#075985}.pill-urgent{background:#fee2e2;color:#991b1b}.pill-wait{background:#fef3c7;color:#92400e}.pill-stop{background:#ffedd5;color:#9a3412}.pill-neg{background:#ede9fe;color:#5b21b6}.pill-soft{background:#f2f4f7;color:#344054}
        .funnel{display:grid;gap:11px}.funnel-row{display:grid;grid-template-columns:120px 1fr 42px;gap:10px;align-items:center}.bar{height:10px;background:#e5e7eb;border-radius:999px;overflow:hidden}.bar span{display:block;height:100%;background:#00aeef;border-radius:999px}.funnel-label{font-weight:800;font-size:13px}.funnel-total{text-align:right;font-weight:900}
        .activity{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
        .empty{padding:18px;border:1px dashed #d0d5dd;border-radius:8px;background:#f8fafc;color:#667085;text-align:center}
        @media(max-width:1180px){.kpis{grid-template-columns:repeat(3,1fr)}.grid,.activity{grid-template-columns:1fr}}
        @media(max-width:720px){.top{align-items:flex-start;flex-direction:column}.quick{justify-content:flex-start}.wrap{padding:14px}.kpis{grid-template-columns:1fr 1fr}.item{grid-template-columns:1fr}}
    </style>
    <link rel="stylesheet" href="<?= h(asset('css/admin-modern.css')) ?>">
</head>
<body>
<div class="page">
    <header class="top">
        <div class="brand">
            <img src="<?= h(asset('ImagensRG/logo.png')) ?>" alt="RG Auto Sales">
            <div>
                <h1>Dashboard CRM</h1>
                <p>Visao central da operacao comercial</p>
            </div>
        </div>
        <nav class="quick">
            <a class="btn btn-blue" href="<?= h(url('admin/crm/inbox.php')) ?>">Abrir Inbox</a>
            <a class="btn btn-light" href="<?= h(url('admin/leads/leads.php')) ?>">Leads</a>
            <a class="btn btn-green" href="<?= h(url('admin/vendas/nova_venda.php')) ?>">Nova venda</a>
            <a class="btn btn-dark" href="<?= h(url('admin/financeiro/dashboard_financeiro.php')) ?>">Financeiro</a>
        </nav>
    </header>

    <main class="wrap">
        <section class="kpis">
            <div class="kpi"><span>Total de leads</span><strong><?= h($totalLeads) ?></strong></div>
            <div class="kpi"><span>Leads novos</span><strong><?= h($leadsNovos) ?></strong></div>
            <div class="kpi"><span>Urgentes</span><strong><?= h(count($leadsUrgentes)) ?></strong></div>
            <div class="kpi"><span>Negociacao</span><strong><?= h($leadsNegociacao) ?></strong></div>
            <div class="kpi"><span>Vendas fechadas</span><strong><?= h($vendasFechadas) ?></strong></div>
            <div class="kpi"><span>Follow-ups pendentes</span><strong><?= h(count($followupsPendentes)) ?></strong></div>
        </section>

        <section class="grid">
            <div class="panel">
                <div class="panel-head">
                    <h2>Leads urgentes</h2>
                    <a href="<?= h(url('admin/crm/inbox.php')) ?>">Ver na Inbox</a>
                </div>
                <div class="panel-body">
                    <?php if ($leadsUrgentes): ?>
                        <div class="list">
                            <?php foreach (array_slice($leadsUrgentes, 0, 7) as $lead): ?>
                                <a class="item" href="<?= h(url('admin/crm/inbox.php?id=' . (int)$lead['id'])) ?>">
                                    <div class="item-main">
                                        <div class="item-title"><?= h($lead['nome']) ?></div>
                                        <div class="item-meta"><?= h($lead['telefone']) ?><?= crm_dash_car($lead) !== '' ? ' - ' . h(crm_dash_car($lead)) : '' ?></div>
                                    </div>
                                    <span class="pill <?= h($lead['_attention']['class']) ?>"><?= h($lead['_attention']['dias']) ?> dia(s)</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty">Nenhum lead urgente neste momento.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel">
                <div class="panel-head">
                    <h2>Funil por status</h2>
                    <a href="<?= h(url('admin/leads/leads.php')) ?>">Listar leads</a>
                </div>
                <div class="panel-body">
                    <div class="funnel">
                        <?php foreach ($funil as $key => $item): ?>
                            <div class="funnel-row">
                                <div class="funnel-label"><?= h($item['label']) ?></div>
                                <div class="bar"><span style="width:<?= h((string)max(4, round(($item['total'] / $maxFunil) * 100))) ?>%"></span></div>
                                <div class="funnel-total"><?= h($item['total']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid">
            <div class="panel">
                <div class="panel-head">
                    <h2>Follow-ups pendentes</h2>
                    <a href="<?= h(url('admin/crm/inbox.php')) ?>">Abrir fila</a>
                </div>
                <div class="panel-body">
                    <?php if ($followupsPendentes): ?>
                        <div class="list">
                            <?php foreach (array_slice($followupsPendentes, 0, 8) as $lead): ?>
                                <a class="item" href="<?= h(url('admin/crm/inbox.php?id=' . (int)$lead['id'])) ?>">
                                    <div class="item-main">
                                        <div class="item-title"><?= h($lead['nome']) ?></div>
                                        <div class="item-meta">Ultimo contacto: <?= h($lead['_attention']['dias'] ?? '-') ?> dia(s) atras</div>
                                    </div>
                                    <span class="pill <?= h($lead['_attention']['class']) ?>"><?= h($lead['_attention']['label']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty">Sem follow-ups pendentes.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel">
                <div class="panel-head">
                    <h2>Links rapidos</h2>
                </div>
                <div class="panel-body">
                    <div class="list">
                        <a class="item" href="<?= h(url('admin/crm/inbox.php')) ?>"><div class="item-main"><div class="item-title">Inbox comercial</div><div class="item-meta">Follow-up, WhatsApp e timeline</div></div><span class="pill pill-new">CRM</span></a>
                        <a class="item" href="<?= h(url('admin/leads/leads.php')) ?>"><div class="item-main"><div class="item-title">Lista de leads</div><div class="item-meta">Tabela operacional completa</div></div><span class="pill pill-soft">Leads</span></a>
                        <a class="item" href="<?= h(url('admin/vendas/nova_venda.php')) ?>"><div class="item-main"><div class="item-title">Nova venda</div><div class="item-meta">Criar venda manualmente</div></div><span class="pill pill-neg">Vendas</span></a>
                        <a class="item" href="<?= h(url('admin/financeiro/dashboard_financeiro.php')) ?>"><div class="item-main"><div class="item-title">Financeiro</div><div class="item-meta">Lucro, comissoes e pagamentos</div></div><span class="pill pill-stop">Fin</span></a>
                    </div>
                </div>
            </div>
        </section>

        <section class="activity">
            <div class="panel">
                <div class="panel-head"><h2>Ultimos leads</h2></div>
                <div class="panel-body">
                    <?php if ($recentLeads): ?>
                        <div class="list">
                            <?php foreach ($recentLeads as $lead): ?>
                                <a class="item" href="<?= h(url('admin/crm/inbox.php?id=' . (int)$lead['id'])) ?>">
                                    <div class="item-main"><div class="item-title"><?= h($lead['nome']) ?></div><div class="item-meta"><?= h(date('d/m/Y H:i', strtotime($lead['criado_em']))) ?></div></div>
                                    <span class="pill <?= h($lead['_attention']['class']) ?>"><?= h($lead['_attention']['label']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty">Nenhum lead registado.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel">
                <div class="panel-head"><h2>Ultimos follow-ups</h2></div>
                <div class="panel-body">
                    <?php if ($recentFollowups): ?>
                        <div class="list">
                            <?php foreach ($recentFollowups as $item): ?>
                                <a class="item" href="<?= h(url('admin/crm/inbox.php?id=' . (int)$item['lead_id'])) ?>">
                                    <div class="item-main"><div class="item-title"><?= h($item['nome'] ?: 'Lead #' . $item['lead_id']) ?></div><div class="item-meta"><?= h(mb_strimwidth($item['mensagem'], 0, 70, '...')) ?></div></div>
                                    <span class="pill pill-soft"><?= h(date('d/m', strtotime($item['criado_em']))) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty">Sem follow-ups registados.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel">
                <div class="panel-head"><h2>Ultimas vendas</h2></div>
                <div class="panel-body">
                    <?php if ($recentVendas): ?>
                        <div class="list">
                            <?php foreach ($recentVendas as $venda): ?>
                                <a class="item" href="<?= h(url('admin/vendas/venda_detalhe.php?id=' . (int)$venda['id'])) ?>">
                                    <div class="item-main"><div class="item-title"><?= h(trim($venda['marca'] . ' ' . $venda['modelo'] . ' ' . $venda['ano'])) ?></div><div class="item-meta"><?= h(date('d/m/Y', strtotime($venda['data_venda'] ?: $venda['criado_em']))) ?></div></div>
                                    <span class="pill pill-neg"><?= h($venda['status']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty">Sem vendas recentes.</div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
</div>
</body>
</html>
