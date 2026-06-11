<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

$tipos = [
    'captador' => 'Captador',
    'revendedor' => 'Revendedor',
    'importacao' => 'Importacao',
    'marketing' => 'Marketing',
    'fornecedor' => 'Fornecedor',
    'outro' => 'Outro',
];
$estados = ['ativo' => 'Ativo', 'inativo' => 'Inativo', 'pendente' => 'Pendente'];
$niveis = ['principal' => 'Principal', 'regular' => 'Regular', 'comunidade' => 'Comunidade'];

function parceiro_badge_class(string $value): string
{
    return match ($value) {
        'ativo', 'principal' => 'bg-success',
        'pendente', 'regular' => 'bg-warning text-dark',
        'inativo' => 'bg-secondary',
        'captador', 'importacao' => 'bg-info',
        'revendedor' => 'bg-primary',
        'marketing' => 'bg-dark',
        default => 'bg-secondary',
    };
}

function parceiro_whatsapp_url(?string $numero, string $nome = ''): string
{
    $limpo = preg_replace('/\D+/', '', (string)$numero);
    if ($limpo === '') {
        return '';
    }

    if (!str_starts_with($limpo, '258')) {
        $limpo = '258' . ltrim($limpo, '0');
    }

    $msg = rawurlencode('Ola ' . $nome . ', aqui e da RG Auto Sales.');
    return 'https://wa.me/' . $limpo . '?text=' . $msg;
}

$filtroTipo = trim((string)($_GET['tipo'] ?? ''));
$filtroEstado = trim((string)($_GET['estado'] ?? ''));
$filtroNivel = trim((string)($_GET['nivel'] ?? ''));
$filtroCidade = trim((string)($_GET['cidade'] ?? ''));
$busca = trim((string)($_GET['q'] ?? ''));

if ($filtroTipo !== '' && !array_key_exists($filtroTipo, $tipos)) {
    $filtroTipo = '';
}
if ($filtroEstado !== '' && !array_key_exists($filtroEstado, $estados)) {
    $filtroEstado = '';
}
if ($filtroNivel !== '' && !array_key_exists($filtroNivel, $niveis)) {
    $filtroNivel = '';
}

$stats = [
    'total' => 0,
    'ativos' => 0,
    'principais' => 0,
    'captadores' => 0,
    'revendedores' => 0,
    'importacao' => 0,
];
$statsSql = "
    SELECT
        COUNT(*) AS total,
        SUM(estado = 'ativo') AS ativos,
        SUM(nivel = 'principal') AS principais,
        SUM(tipo = 'captador') AS captadores,
        SUM(tipo = 'revendedor') AS revendedores,
        SUM(tipo = 'importacao') AS importacao
    FROM parceiros
";
$statsRes = mysqli_query($conexao, $statsSql);
if ($statsRes) {
    $stats = array_merge($stats, mysqli_fetch_assoc($statsRes) ?: []);
}

$cidades = [];
$cidadeRes = mysqli_query($conexao, "SELECT DISTINCT cidade FROM parceiros WHERE cidade IS NOT NULL AND cidade <> '' ORDER BY cidade ASC");
if ($cidadeRes) {
    while ($row = mysqli_fetch_assoc($cidadeRes)) {
        $cidades[] = (string)$row['cidade'];
    }
}

$sql = "SELECT * FROM parceiros WHERE 1=1";
$params = [];
$types = '';

if ($filtroTipo !== '') {
    $sql .= " AND tipo = ?";
    $params[] = $filtroTipo;
    $types .= 's';
}
if ($filtroEstado !== '') {
    $sql .= " AND estado = ?";
    $params[] = $filtroEstado;
    $types .= 's';
}
if ($filtroNivel !== '') {
    $sql .= " AND nivel = ?";
    $params[] = $filtroNivel;
    $types .= 's';
}
if ($filtroCidade !== '') {
    $sql .= " AND cidade = ?";
    $params[] = $filtroCidade;
    $types .= 's';
}
if ($busca !== '') {
    $sql .= " AND (nome LIKE ? OR telefone LIKE ? OR whatsapp LIKE ? OR email LIKE ?)";
    $like = '%' . $busca . '%';
    array_push($params, $like, $like, $like, $like);
    $types .= 'ssss';
}

$sql .= " ORDER BY estado = 'ativo' DESC, nivel = 'principal' DESC, criado_em DESC, id DESC LIMIT 300";
$stmt = mysqli_prepare($conexao, $sql);
if ($stmt && $params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
$parceiros = [];
if ($stmt) {
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $parceiros[] = $row;
    }
    mysqli_stmt_close($stmt);
}

$pageTitle = 'Parceiros';
$pageSubtitle = 'Rede de captacao, revenda, importacao e contactos estrategicos';
$alerts = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);

require BASE_PATH . '/app/views/layouts/admin_header.php';
?>
<div class="rg-admin-shell">
    <?php require BASE_PATH . '/app/views/layouts/admin_sidebar.php'; ?>
    <main class="rg-admin-main">
        <?php require BASE_PATH . '/app/views/layouts/admin_topbar.php'; ?>
        <section class="rg-admin-content">
            <?php if (!empty($alerts)): ?>
                <div class="rg-admin-alerts">
                    <?php foreach ((array)$alerts as $alert): ?>
                        <div class="rg-admin-alert rg-admin-alert--<?= h($alert['type'] ?? 'info') ?>"><?= h($alert['message'] ?? '') ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="ops-page">
                <div class="rg-page-hero">
                    <div>
                        <h2>RG Partner Network v1</h2>
                        <p>Gestao de parceiros de captacao, revenda, importacao, marketing e fornecedores.</p>
                    </div>
                    <div class="rg-page-actions">
                        <a class="btn btn-light" href="<?= h(url('admin/parceiros/leads.php')) ?>">Leads de Parceiros</a>
                        <a class="btn btn-light" href="<?= h(url('admin/parceiros/performance.php')) ?>">Performance</a>
                        <a class="btn btn-light" href="<?= h(url('admin/parceiros/adicionar.php')) ?>">Novo Parceiro</a>
                    </div>
                </div>

                <section class="rg-kpi-grid">
                    <div class="rg-kpi-card is-info"><strong><?= h((int)($stats['total'] ?? 0)) ?></strong><span>Total de parceiros</span></div>
                    <div class="rg-kpi-card is-success"><strong><?= h((int)($stats['ativos'] ?? 0)) ?></strong><span>Parceiros ativos</span></div>
                    <div class="rg-kpi-card is-success"><strong><?= h((int)($stats['principais'] ?? 0)) ?></strong><span>Principais</span></div>
                    <div class="rg-kpi-card is-info"><strong><?= h((int)($stats['captadores'] ?? 0)) ?></strong><span>Captadores</span></div>
                    <div class="rg-kpi-card is-warning"><strong><?= h((int)($stats['revendedores'] ?? 0)) ?></strong><span>Revendedores</span></div>
                    <div class="rg-kpi-card is-info"><strong><?= h((int)($stats['importacao'] ?? 0)) ?></strong><span>Importacao</span></div>
                </section>

                <div class="rg-panel">
                    <div class="rg-panel-body">
                        <form method="GET" action="<?= h(url('admin/parceiros/index.php')) ?>" class="rg-filter-grid" style="grid-template-columns:minmax(220px,2fr) repeat(4,minmax(140px,1fr)) auto auto;">
                            <input class="form-control" type="search" name="q" value="<?= h($busca) ?>" placeholder="Buscar por nome, telefone, WhatsApp ou email">
                            <select class="form-select" name="tipo">
                                <option value="">Todos os tipos</option>
                                <?php foreach ($tipos as $valor => $label): ?>
                                    <option value="<?= h($valor) ?>" <?= $filtroTipo === $valor ? 'selected' : '' ?>><?= h($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select class="form-select" name="estado">
                                <option value="">Todos os estados</option>
                                <?php foreach ($estados as $valor => $label): ?>
                                    <option value="<?= h($valor) ?>" <?= $filtroEstado === $valor ? 'selected' : '' ?>><?= h($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select class="form-select" name="nivel">
                                <option value="">Todos os niveis</option>
                                <?php foreach ($niveis as $valor => $label): ?>
                                    <option value="<?= h($valor) ?>" <?= $filtroNivel === $valor ? 'selected' : '' ?>><?= h($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select class="form-select" name="cidade">
                                <option value="">Todas as cidades</option>
                                <?php foreach ($cidades as $cidade): ?>
                                    <option value="<?= h($cidade) ?>" <?= $filtroCidade === $cidade ? 'selected' : '' ?>><?= h($cidade) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-primary" type="submit">Filtrar</button>
                            <a class="btn btn-light" href="<?= h(url('admin/parceiros/index.php')) ?>">Limpar</a>
                        </form>
                    </div>
                </div>

                <div class="rg-table-wrap">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Contactos</th>
                                <th>Cidade</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Nivel</th>
                                <th>Comissao</th>
                                <th>Acoes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($parceiros): ?>
                                <?php foreach ($parceiros as $parceiro): ?>
                                    <?php $waUrl = parceiro_whatsapp_url($parceiro['whatsapp'] ?: $parceiro['telefone'], $parceiro['nome'] ?? ''); ?>
                                    <tr>
                                        <td>
                                            <strong><?= h($parceiro['nome']) ?></strong>
                                            <small class="d-block text-muted"><?= h($parceiro['origem'] ?: '-') ?></small>
                                        </td>
                                        <td>
                                            <?= h($parceiro['telefone'] ?: '-') ?>
                                            <small class="d-block text-muted"><?= h($parceiro['email'] ?: ($parceiro['whatsapp'] ?: '-')) ?></small>
                                        </td>
                                        <td><?= h($parceiro['cidade'] ?: '-') ?></td>
                                        <td><span class="badge <?= h(parceiro_badge_class((string)$parceiro['tipo'])) ?>"><?= h($tipos[$parceiro['tipo']] ?? $parceiro['tipo']) ?></span></td>
                                        <td><span class="badge <?= h(parceiro_badge_class((string)$parceiro['estado'])) ?>"><?= h($estados[$parceiro['estado']] ?? $parceiro['estado']) ?></span></td>
                                        <td><span class="badge <?= h(parceiro_badge_class((string)$parceiro['nivel'])) ?>"><?= h($niveis[$parceiro['nivel']] ?? $parceiro['nivel']) ?></span></td>
                                        <td><?= $parceiro['comissao_padrao'] !== null ? h(number_format((float)$parceiro['comissao_padrao'], 2, ',', '.')) : '-' ?></td>
                                        <td>
                                            <div class="rg-row-actions">
                                                <a class="btn btn-sm btn-primary" href="<?= h(url('admin/parceiros/detalhe.php?id=' . (int)$parceiro['id'])) ?>">Ver</a>
                                                <a class="btn btn-sm btn-warning" href="<?= h(url('admin/parceiros/editar.php?id=' . (int)$parceiro['id'])) ?>">Editar</a>
                                                <?php if ($waUrl !== ''): ?>
                                                    <a class="btn btn-sm btn-success" target="_blank" rel="noopener" href="<?= h($waUrl) ?>">WhatsApp</a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="text-center text-muted py-4">Nenhum parceiro encontrado.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
<?php require BASE_PATH . '/app/views/layouts/admin_footer.php'; ?>
