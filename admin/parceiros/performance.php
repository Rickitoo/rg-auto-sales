<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

$tipos = ['captador' => 'Captador', 'revendedor' => 'Revendedor', 'importacao' => 'Importacao', 'marketing' => 'Marketing', 'fornecedor' => 'Fornecedor', 'outro' => 'Outro'];
$niveis = ['principal' => 'Principal', 'regular' => 'Regular', 'comunidade' => 'Comunidade'];
$statuses = ['novo' => 'Novo', 'contactado' => 'Contactado', 'negociacao' => 'Negociacao', 'fechado' => 'Fechado', 'perdido' => 'Perdido'];

function partner_perf_money($value): string
{
    return number_format((float)$value, 2, ',', '.');
}

function partner_perf_badge(string $status): string
{
    return match ($status) {
        'fechado' => 'bg-success',
        'perdido' => 'bg-danger',
        'negociacao' => 'bg-warning text-dark',
        'contactado' => 'bg-primary',
        default => 'bg-info',
    };
}

$periodo = trim((string)($_GET['periodo'] ?? ''));
$tipo = trim((string)($_GET['tipo'] ?? ''));
$nivel = trim((string)($_GET['nivel'] ?? ''));
$cidade = trim((string)($_GET['cidade'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));

if (!array_key_exists($tipo, $tipos)) {
    $tipo = '';
}
if (!array_key_exists($nivel, $niveis)) {
    $nivel = '';
}
if (!array_key_exists($status, $statuses)) {
    $status = '';
}

$dateFrom = '';
if ($periodo === '7') {
    $dateFrom = date('Y-m-d 00:00:00', strtotime('-7 days'));
} elseif ($periodo === '30') {
    $dateFrom = date('Y-m-d 00:00:00', strtotime('-30 days'));
} elseif ($periodo === '90') {
    $dateFrom = date('Y-m-d 00:00:00', strtotime('-90 days'));
} else {
    $periodo = '';
}

$cidades = [];
$cidadeRes = mysqli_query($conexao, "SELECT DISTINCT cidade FROM parceiros WHERE cidade IS NOT NULL AND cidade <> '' ORDER BY cidade ASC");
while ($cidadeRes && ($row = mysqli_fetch_assoc($cidadeRes))) {
    $cidades[] = (string)$row['cidade'];
}

$where = [];
$params = [];
$types = '';

if ($dateFrom !== '') {
    $where[] = 'pl.criado_em >= ?';
    $params[] = $dateFrom;
    $types .= 's';
}
if ($tipo !== '') {
    $where[] = 'p.tipo = ?';
    $params[] = $tipo;
    $types .= 's';
}
if ($nivel !== '') {
    $where[] = 'p.nivel = ?';
    $params[] = $nivel;
    $types .= 's';
}
if ($cidade !== '') {
    $where[] = 'p.cidade = ?';
    $params[] = $cidade;
    $types .= 's';
}
if ($status !== '') {
    $where[] = 'pl.status = ?';
    $params[] = $status;
    $types .= 's';
}

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

function partner_perf_fetch_one(mysqli $conexao, string $sql, string $types = '', array $params = []): array
{
    $stmt = mysqli_prepare($conexao, $sql);
    if (!$stmt) {
        return [];
    }
    if ($params) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: [];
    mysqli_stmt_close($stmt);
    return $row;
}

function partner_perf_fetch_all(mysqli $conexao, string $sql, string $types = '', array $params = []): array
{
    $stmt = mysqli_prepare($conexao, $sql);
    if (!$stmt) {
        return [];
    }
    if ($params) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($res && ($row = mysqli_fetch_assoc($res))) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $rows;
}

$ativosRow = partner_perf_fetch_one($conexao, "SELECT COUNT(*) AS total FROM parceiros WHERE estado = 'ativo'");
$stats = partner_perf_fetch_one($conexao, "
    SELECT
        COUNT(pl.id) AS total_leads,
        SUM(pl.status = 'fechado') AS fechados,
        SUM(pl.status = 'perdido') AS perdidos,
        SUM(pl.sincronizado_crm = 1) AS sincronizados,
        SUM(pl.sincronizado_crm = 0) AS pendentes_sync,
        COALESCE(SUM(pl.comissao_prevista), 0) AS comissao_total
    FROM parceiro_leads pl
    INNER JOIN parceiros p ON p.id = pl.parceiro_id
    $whereSql
", $types, $params);

$totalLeads = (int)($stats['total_leads'] ?? 0);
$fechados = (int)($stats['fechados'] ?? 0);
$taxaConversao = $totalLeads > 0 ? ($fechados / $totalLeads) * 100 : 0;

$rankingLeads = partner_perf_fetch_all($conexao, "
    SELECT p.id, p.nome, p.tipo, COUNT(pl.id) AS total, SUM(pl.status = 'fechado') AS fechados, COALESCE(SUM(pl.comissao_prevista), 0) AS comissao
    FROM parceiro_leads pl
    INNER JOIN parceiros p ON p.id = pl.parceiro_id
    $whereSql
    GROUP BY p.id, p.nome, p.tipo
    ORDER BY total DESC, p.nome ASC
    LIMIT 10
", $types, $params);

$rankingFechados = partner_perf_fetch_all($conexao, "
    SELECT p.id, p.nome, SUM(pl.status = 'fechado') AS total
    FROM parceiro_leads pl
    INNER JOIN parceiros p ON p.id = pl.parceiro_id
    $whereSql
    GROUP BY p.id, p.nome
    ORDER BY SUM(pl.status = 'fechado') DESC, total DESC, p.nome ASC
    LIMIT 10
", $types, $params);

$rankingComissao = partner_perf_fetch_all($conexao, "
    SELECT p.id, p.nome, COALESCE(SUM(pl.comissao_prevista), 0) AS total
    FROM parceiro_leads pl
    INNER JOIN parceiros p ON p.id = pl.parceiro_id
    $whereSql
    GROUP BY p.id, p.nome
    ORDER BY total DESC, p.nome ASC
    LIMIT 10
", $types, $params);

$pageTitle = 'Performance de Parceiros';
$pageSubtitle = 'Indicadores comerciais manuais da rede RG Partner Network';
$alerts = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);

require BASE_PATH . '/app/views/layouts/admin_header.php';
?>
<div class="rg-admin-shell">
    <?php require BASE_PATH . '/app/views/layouts/admin_sidebar.php'; ?>
    <main class="rg-admin-main">
        <?php require BASE_PATH . '/app/views/layouts/admin_topbar.php'; ?>
        <section class="rg-admin-content">
            <?php if (!empty($alerts)): ?><div class="rg-admin-alerts"><?php foreach ((array)$alerts as $alert): ?><div class="rg-admin-alert rg-admin-alert--<?= h($alert['type'] ?? 'info') ?>"><?= h($alert['message'] ?? '') ?></div><?php endforeach; ?></div><?php endif; ?>
            <div class="ops-page">
                <div class="rg-page-hero">
                    <div>
                        <h2>Partner Performance v1</h2>
                        <p>Leads, conversao e comissao prevista sem impacto em vendas ou financeiro.</p>
                    </div>
                    <div class="rg-page-actions">
                        <a class="btn btn-light" href="<?= h(url('admin/parceiros/index.php')) ?>">Parceiros</a>
                        <a class="btn btn-light" href="<?= h(url('admin/parceiros/leads.php')) ?>">Leads de Parceiros</a>
                        <a class="btn btn-primary" href="<?= h(url('admin/parceiros/lead_adicionar.php')) ?>">Adicionar Lead</a>
                    </div>
                </div>

                <section class="rg-kpi-grid">
                    <div class="rg-kpi-card is-success"><strong><?= h((int)($ativosRow['total'] ?? 0)) ?></strong><span>Parceiros ativos</span></div>
                    <div class="rg-kpi-card is-info"><strong><?= h($totalLeads) ?></strong><span>Total de leads</span></div>
                    <div class="rg-kpi-card is-success"><strong><?= h($fechados) ?></strong><span>Leads fechados</span></div>
                    <div class="rg-kpi-card is-danger"><strong><?= h((int)($stats['perdidos'] ?? 0)) ?></strong><span>Leads perdidos</span></div>
                    <div class="rg-kpi-card is-warning"><strong><?= h(number_format($taxaConversao, 1, ',', '.')) ?>%</strong><span>Taxa de conversao</span></div>
                    <div class="rg-kpi-card is-info"><strong><?= h(partner_perf_money($stats['comissao_total'] ?? 0)) ?></strong><span>Comissao prevista</span></div>
                    <div class="rg-kpi-card is-success"><strong><?= h((int)($stats['sincronizados'] ?? 0)) ?></strong><span>Leads sincronizados no CRM</span></div>
                    <div class="rg-kpi-card is-warning"><strong><?= h((int)($stats['pendentes_sync'] ?? 0)) ?></strong><span>Leads pendentes de sync</span></div>
                </section>

                <div class="rg-panel">
                    <div class="rg-panel-body">
                        <form method="GET" action="<?= h(url('admin/parceiros/performance.php')) ?>" class="rg-filter-grid" style="grid-template-columns:repeat(5,minmax(140px,1fr)) auto auto;">
                            <select class="form-select" name="periodo">
                                <option value="">Todo periodo</option>
                                <option value="7" <?= $periodo === '7' ? 'selected' : '' ?>>Ultimos 7 dias</option>
                                <option value="30" <?= $periodo === '30' ? 'selected' : '' ?>>Ultimos 30 dias</option>
                                <option value="90" <?= $periodo === '90' ? 'selected' : '' ?>>Ultimos 90 dias</option>
                            </select>
                            <select class="form-select" name="tipo"><option value="">Tipo</option><?php foreach ($tipos as $v => $l): ?><option value="<?= h($v) ?>" <?= $tipo === $v ? 'selected' : '' ?>><?= h($l) ?></option><?php endforeach; ?></select>
                            <select class="form-select" name="nivel"><option value="">Nivel</option><?php foreach ($niveis as $v => $l): ?><option value="<?= h($v) ?>" <?= $nivel === $v ? 'selected' : '' ?>><?= h($l) ?></option><?php endforeach; ?></select>
                            <select class="form-select" name="cidade"><option value="">Cidade</option><?php foreach ($cidades as $c): ?><option value="<?= h($c) ?>" <?= $cidade === $c ? 'selected' : '' ?>><?= h($c) ?></option><?php endforeach; ?></select>
                            <select class="form-select" name="status"><option value="">Status</option><?php foreach ($statuses as $v => $l): ?><option value="<?= h($v) ?>" <?= $status === $v ? 'selected' : '' ?>><?= h($l) ?></option><?php endforeach; ?></select>
                            <button class="btn btn-primary" type="submit">Filtrar</button>
                            <a class="btn btn-light" href="<?= h(url('admin/parceiros/performance.php')) ?>">Limpar</a>
                        </form>
                    </div>
                </div>

                <div class="row g-3">
                    <?php foreach ([['Ranking por leads', $rankingLeads, 'total'], ['Ranking por leads fechados', $rankingFechados, 'total'], ['Ranking por comissao prevista', $rankingComissao, 'total']] as $box): ?>
                        <div class="col-lg-4">
                            <div class="rg-panel h-100">
                                <div class="rg-panel-body">
                                    <h5 class="fw-bold mb-3"><?= h($box[0]) ?></h5>
                                    <div class="rg-stack">
                                        <?php if ($box[1]): ?>
                                            <?php foreach ($box[1] as $row): ?>
                                                <a class="rg-list-row text-decoration-none" href="<?= h(url('admin/parceiros/detalhe.php?id=' . (int)$row['id'])) ?>">
                                                    <span><strong><?= h($row['nome']) ?></strong><small><?= h($box[0] === 'Ranking por comissao prevista' ? partner_perf_money($row[$box[2]]) : (int)$row[$box[2]]) ?></small></span>
                                                    <?php if (isset($row['fechados'])): ?><span class="badge <?= h(partner_perf_badge('fechado')) ?>"><?= h((int)$row['fechados']) ?> fechados</span><?php endif; ?>
                                                </a>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="empty">Sem dados para este filtro.</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
<?php require BASE_PATH . '/app/views/layouts/admin_footer.php'; ?>
