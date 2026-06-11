<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

$statuses = ['novo' => 'Novo', 'contactado' => 'Contactado', 'negociacao' => 'Negociacao', 'fechado' => 'Fechado', 'perdido' => 'Perdido'];

function partner_lead_badge(string $status): string
{
    return match ($status) {
        'fechado' => 'bg-success',
        'perdido' => 'bg-danger',
        'negociacao' => 'bg-warning text-dark',
        'contactado' => 'bg-primary',
        default => 'bg-info',
    };
}

function partner_lead_wa(?string $numero, string $nome): string
{
    $limpo = preg_replace('/\D+/', '', (string)$numero);
    if ($limpo === '') {
        return '';
    }
    if (!str_starts_with($limpo, '258')) {
        $limpo = '258' . ltrim($limpo, '0');
    }
    return 'https://wa.me/' . $limpo . '?text=' . rawurlencode('Ola ' . $nome . ', aqui e da RG Auto Sales.');
}

$statusFiltro = trim((string)($_GET['status'] ?? ''));
$parceiroFiltro = (int)($_GET['parceiro_id'] ?? 0);
$busca = trim((string)($_GET['q'] ?? ''));
if (!array_key_exists($statusFiltro, $statuses)) {
    $statusFiltro = '';
}

$parceiros = [];
$parceirosRes = mysqli_query($conexao, "SELECT id, nome FROM parceiros ORDER BY nome ASC");
while ($parceirosRes && ($row = mysqli_fetch_assoc($parceirosRes))) {
    $parceiros[] = $row;
}

$sql = "
    SELECT pl.*, p.nome AS parceiro_nome, p.tipo AS parceiro_tipo
    FROM parceiro_leads pl
    INNER JOIN parceiros p ON p.id = pl.parceiro_id
    WHERE 1=1
";
$params = [];
$types = '';

if ($statusFiltro !== '') {
    $sql .= " AND pl.status = ?";
    $params[] = $statusFiltro;
    $types .= 's';
}
if ($parceiroFiltro > 0) {
    $sql .= " AND pl.parceiro_id = ?";
    $params[] = $parceiroFiltro;
    $types .= 'i';
}
if ($busca !== '') {
    $sql .= " AND (pl.nome_lead LIKE ? OR pl.telefone_lead LIKE ? OR pl.modelo_interesse LIKE ? OR p.nome LIKE ?)";
    $like = '%' . $busca . '%';
    array_push($params, $like, $like, $like, $like);
    $types .= 'ssss';
}

$sql .= " ORDER BY pl.criado_em DESC, pl.id DESC LIMIT 300";
$stmt = mysqli_prepare($conexao, $sql);
if ($stmt && $params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
$leads = [];
if ($stmt) {
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($res && ($row = mysqli_fetch_assoc($res))) {
        $leads[] = $row;
    }
    mysqli_stmt_close($stmt);
}

$pageTitle = 'Leads de Parceiros';
$pageSubtitle = 'Registos manuais gerados pela rede de parceiros';
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
                        <h2>Leads de Parceiros</h2>
                        <p>Pipeline manual da rede, sem sincronizacao automatica com CRM ou vendas.</p>
                    </div>
                    <div class="rg-page-actions">
                        <a class="btn btn-light" href="<?= h(url('admin/parceiros/index.php')) ?>">Parceiros</a>
                        <a class="btn btn-light" href="<?= h(url('admin/parceiros/performance.php')) ?>">Performance</a>
                        <a class="btn btn-primary" href="<?= h(url('admin/parceiros/lead_adicionar.php')) ?>">Adicionar Lead</a>
                    </div>
                </div>

                <div class="rg-panel">
                    <div class="rg-panel-body">
                        <form method="GET" action="<?= h(url('admin/parceiros/leads.php')) ?>" class="rg-filter-grid" style="grid-template-columns:minmax(220px,2fr) minmax(180px,1fr) minmax(160px,1fr) auto auto;">
                            <input class="form-control" type="search" name="q" value="<?= h($busca) ?>" placeholder="Buscar lead, telefone, modelo ou parceiro">
                            <select class="form-select" name="parceiro_id">
                                <option value="0">Todos os parceiros</option>
                                <?php foreach ($parceiros as $parceiro): ?>
                                    <option value="<?= h((int)$parceiro['id']) ?>" <?= $parceiroFiltro === (int)$parceiro['id'] ? 'selected' : '' ?>><?= h($parceiro['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select class="form-select" name="status">
                                <option value="">Todos os status</option>
                                <?php foreach ($statuses as $value => $label): ?>
                                    <option value="<?= h($value) ?>" <?= $statusFiltro === $value ? 'selected' : '' ?>><?= h($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-primary" type="submit">Filtrar</button>
                            <a class="btn btn-light" href="<?= h(url('admin/parceiros/leads.php')) ?>">Limpar</a>
                        </form>
                    </div>
                </div>

                <div class="rg-table-wrap">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Parceiro</th>
                                <th>Lead</th>
                                <th>Telefone</th>
                                <th>Modelo</th>
                                <th>Status</th>
                                <th>CRM</th>
                                <th>Valor estimado</th>
                                <th>Comissao prevista</th>
                                <th>Data</th>
                                <th>Acoes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($leads): ?>
                                <?php foreach ($leads as $lead): ?>
                                    <?php $waUrl = partner_lead_wa($lead['telefone_lead'] ?? '', $lead['nome_lead'] ?? ''); ?>
                                    <tr>
                                        <td><strong><?= h($lead['parceiro_nome']) ?></strong><small class="d-block text-muted"><?= h($lead['parceiro_tipo']) ?></small></td>
                                        <td><?= h($lead['nome_lead'] ?: '-') ?></td>
                                        <td><?= h($lead['telefone_lead'] ?: '-') ?></td>
                                        <td><?= h($lead['modelo_interesse'] ?: '-') ?></td>
                                        <td><span class="badge <?= h(partner_lead_badge((string)$lead['status'])) ?>"><?= h($statuses[$lead['status']] ?? $lead['status']) ?></span></td>
                                        <td>
                                            <?php if ((int)($lead['sincronizado_crm'] ?? 0) === 1): ?>
                                                <span class="badge bg-success">Sincronizado</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Pendente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $lead['valor_estimado'] !== null ? h(number_format((float)$lead['valor_estimado'], 2, ',', '.')) : '-' ?></td>
                                        <td><?= $lead['comissao_prevista'] !== null ? h(number_format((float)$lead['comissao_prevista'], 2, ',', '.')) : '-' ?></td>
                                        <td><?= h(!empty($lead['criado_em']) ? date('d/m/Y H:i', strtotime($lead['criado_em'])) : '-') ?></td>
                                        <td>
                                            <div class="rg-row-actions">
                                                <a class="btn btn-sm btn-primary" href="<?= h(url('admin/parceiros/lead_detalhe.php?id=' . (int)$lead['id'])) ?>">Ver</a>
                                                <a class="btn btn-sm btn-warning" href="<?= h(url('admin/parceiros/lead_editar.php?id=' . (int)$lead['id'])) ?>">Editar</a>
                                                <?php if ((int)($lead['sincronizado_crm'] ?? 0) === 1 && (int)($lead['crm_lead_id'] ?? 0) > 0): ?>
                                                    <a class="btn btn-sm btn-info" href="<?= h(url('admin/leads/ver_lead.php?id=' . (int)$lead['crm_lead_id'])) ?>">Ver no CRM</a>
                                                <?php elseif ((int)($lead['sincronizado_crm'] ?? 0) !== 1): ?>
                                                    <form method="POST" action="<?= h(url('admin/parceiros/lead_sync.php')) ?>" class="d-inline">
                                                        <?= csrf_input() ?>
                                                        <input type="hidden" name="parceiro_lead_id" value="<?= h((int)$lead['id']) ?>">
                                                        <button class="btn btn-sm btn-dark" type="submit">Enviar para CRM</button>
                                                    </form>
                                                <?php endif; ?>
                                                <?php if ($waUrl !== ''): ?><a class="btn btn-sm btn-success" target="_blank" rel="noopener" href="<?= h($waUrl) ?>">WhatsApp</a><?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="10" class="text-center text-muted py-4">Nenhum lead de parceiro encontrado.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
<?php require BASE_PATH . '/app/views/layouts/admin_footer.php'; ?>
