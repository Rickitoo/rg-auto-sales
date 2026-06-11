<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

$tipos = ['captador' => 'Captador', 'revendedor' => 'Revendedor', 'importacao' => 'Importacao', 'marketing' => 'Marketing', 'fornecedor' => 'Fornecedor', 'outro' => 'Outro'];
$estados = ['ativo' => 'Ativo', 'inativo' => 'Inativo', 'pendente' => 'Pendente'];
$niveis = ['principal' => 'Principal', 'regular' => 'Regular', 'comunidade' => 'Comunidade'];

function parceiro_badge_class_detail(string $value): string
{
    return match ($value) {
        'ativo', 'principal', 'fechado' => 'bg-success',
        'pendente', 'regular', 'negociacao' => 'bg-warning text-dark',
        'inativo', 'perdido' => 'bg-secondary',
        'captador', 'importacao', 'novo' => 'bg-info',
        'revendedor' => 'bg-primary',
        'marketing', 'contactado' => 'bg-dark',
        default => 'bg-secondary',
    };
}

function parceiro_wa_detail(?string $numero, string $nome): string
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

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect_to('admin/parceiros/index.php');
}

$stmt = mysqli_prepare($conexao, "SELECT * FROM parceiros WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$parceiro = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$parceiro) {
    $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Parceiro nao encontrado.'];
    redirect_to('admin/parceiros/index.php');
}

$waUrl = parceiro_wa_detail($parceiro['whatsapp'] ?: $parceiro['telefone'], $parceiro['nome']);

$perf = ['total_leads' => 0, 'fechados' => 0, 'perdidos' => 0, 'comissao_total' => 0];
$stmt = mysqli_prepare($conexao, "
    SELECT
        COUNT(*) AS total_leads,
        SUM(status = 'fechado') AS fechados,
        SUM(status = 'perdido') AS perdidos,
        COALESCE(SUM(comissao_prevista), 0) AS comissao_total
    FROM parceiro_leads
    WHERE parceiro_id = ?
");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $perf = array_merge($perf, mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: []);
    mysqli_stmt_close($stmt);
}

$ultimosLeads = [];
$stmt = mysqli_prepare($conexao, "
    SELECT id, nome_lead, telefone_lead, modelo_interesse, status, valor_estimado, comissao_prevista, criado_em
    FROM parceiro_leads
    WHERE parceiro_id = ?
    ORDER BY criado_em DESC, id DESC
    LIMIT 5
");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($res && ($row = mysqli_fetch_assoc($res))) {
        $ultimosLeads[] = $row;
    }
    mysqli_stmt_close($stmt);
}

$totalPerfLeads = (int)($perf['total_leads'] ?? 0);
$fechadosPerf = (int)($perf['fechados'] ?? 0);
$taxaPerf = $totalPerfLeads > 0 ? ($fechadosPerf / $totalPerfLeads) * 100 : 0;
$pageTitle = 'Detalhe do Parceiro';
$pageSubtitle = 'Dados, contactos e classificacao da parceria';
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
                <div class="rg-panel">
                    <div class="rg-panel-body rg-section-head">
                        <div>
                            <h2><?= h($parceiro['nome']) ?></h2>
                            <p>Parceiro #<?= h((int)$parceiro['id']) ?></p>
                        </div>
                        <div class="rg-page-actions">
                            <a class="btn btn-light" href="<?= h(url('admin/parceiros/index.php')) ?>">Parceiros</a>
                            <?php if ($waUrl !== ''): ?><a class="btn btn-success" target="_blank" rel="noopener" href="<?= h($waUrl) ?>">WhatsApp</a><?php endif; ?>
                            <a class="btn btn-primary" href="<?= h(url('admin/parceiros/editar.php?id=' . (int)$parceiro['id'])) ?>">Editar</a>
                            <?php if (($parceiro['estado'] ?? '') !== 'inativo'): ?>
                                <form method="POST" action="<?= h(url('admin/parceiros/apagar.php')) ?>" class="d-inline">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="id" value="<?= h((int)$parceiro['id']) ?>">
                                    <button class="btn btn-danger" type="submit">Inativar</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-lg-8">
                        <div class="rg-panel">
                            <div class="rg-panel-body">
                                <h5 class="fw-bold mb-3">Dados do parceiro</h5>
                                <div class="rg-detail-grid">
                                    <div class="rg-detail-item"><span class="label">Tipo</span><span class="value"><span class="badge <?= h(parceiro_badge_class_detail($parceiro['tipo'])) ?>"><?= h($tipos[$parceiro['tipo']] ?? $parceiro['tipo']) ?></span></span></div>
                                    <div class="rg-detail-item"><span class="label">Estado</span><span class="value"><span class="badge <?= h(parceiro_badge_class_detail($parceiro['estado'])) ?>"><?= h($estados[$parceiro['estado']] ?? $parceiro['estado']) ?></span></span></div>
                                    <div class="rg-detail-item"><span class="label">Nivel</span><span class="value"><span class="badge <?= h(parceiro_badge_class_detail($parceiro['nivel'])) ?>"><?= h($niveis[$parceiro['nivel']] ?? $parceiro['nivel']) ?></span></span></div>
                                    <div class="rg-detail-item"><span class="label">Cidade</span><span class="value"><?= h($parceiro['cidade'] ?: '-') ?></span></div>
                                    <div class="rg-detail-item"><span class="label">Origem</span><span class="value"><?= h($parceiro['origem'] ?: '-') ?></span></div>
                                    <div class="rg-detail-item"><span class="label">Comissao padrao</span><span class="value"><?= $parceiro['comissao_padrao'] !== null ? h(number_format((float)$parceiro['comissao_padrao'], 2, ',', '.')) : '-' ?></span></div>
                                    <div class="rg-detail-item"><span class="label">Criado em</span><span class="value"><?= h(!empty($parceiro['criado_em']) ? date('d/m/Y H:i', strtotime($parceiro['criado_em'])) : '-') ?></span></div>
                                    <div class="rg-detail-item"><span class="label">Atualizado em</span><span class="value"><?= h(!empty($parceiro['atualizado_em']) ? date('d/m/Y H:i', strtotime($parceiro['atualizado_em'])) : '-') ?></span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="rg-panel">
                            <div class="rg-panel-body">
                                <h5 class="fw-bold mb-3">Contactos</h5>
                                <div class="rg-detail-grid">
                                    <div class="rg-detail-item"><span class="label">Telefone</span><span class="value"><?= h($parceiro['telefone'] ?: '-') ?></span></div>
                                    <div class="rg-detail-item"><span class="label">WhatsApp</span><span class="value"><?= h($parceiro['whatsapp'] ?: '-') ?></span></div>
                                    <div class="rg-detail-item rg-field-full"><span class="label">Email</span><span class="value"><?= h($parceiro['email'] ?: '-') ?></span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rg-panel">
                    <div class="rg-panel-body rg-section-head">
                        <div>
                            <h5 class="fw-bold mb-1">Performance do Parceiro</h5>
                            <p>Indicadores manuais de leads associados a este parceiro.</p>
                        </div>
                        <div class="rg-page-actions">
                            <a class="btn btn-light" href="<?= h(url('admin/parceiros/lead_adicionar.php?parceiro_id=' . (int)$parceiro['id'])) ?>">Adicionar Lead</a>
                            <a class="btn btn-light" href="<?= h(url('admin/parceiros/leads.php?parceiro_id=' . (int)$parceiro['id'])) ?>">Ver Leads do Parceiro</a>
                            <a class="btn btn-primary" href="<?= h(url('admin/parceiros/performance.php')) ?>">Ver Performance</a>
                        </div>
                    </div>
                    <div class="rg-panel-body pt-0">
                        <section class="rg-kpi-grid">
                            <div class="rg-kpi-card is-info"><strong><?= h($totalPerfLeads) ?></strong><span>Total de leads</span></div>
                            <div class="rg-kpi-card is-success"><strong><?= h($fechadosPerf) ?></strong><span>Leads fechados</span></div>
                            <div class="rg-kpi-card is-danger"><strong><?= h((int)($perf['perdidos'] ?? 0)) ?></strong><span>Leads perdidos</span></div>
                            <div class="rg-kpi-card is-warning"><strong><?= h(number_format($taxaPerf, 1, ',', '.')) ?>%</strong><span>Taxa de conversao</span></div>
                            <div class="rg-kpi-card is-info"><strong><?= h(number_format((float)($perf['comissao_total'] ?? 0), 2, ',', '.')) ?></strong><span>Comissao prevista</span></div>
                        </section>

                        <div class="rg-table-wrap">
                            <table class="table table-hover align-middle mb-0">
                                <thead><tr><th>Lead</th><th>Modelo</th><th>Status</th><th>Valor</th><th>Comissao</th><th>Data</th><th>Acoes</th></tr></thead>
                                <tbody>
                                    <?php if ($ultimosLeads): ?>
                                        <?php foreach ($ultimosLeads as $lead): ?>
                                            <tr>
                                                <td><strong><?= h($lead['nome_lead'] ?: '-') ?></strong><small class="d-block text-muted"><?= h($lead['telefone_lead'] ?: '-') ?></small></td>
                                                <td><?= h($lead['modelo_interesse'] ?: '-') ?></td>
                                                <td><span class="badge <?= h(parceiro_badge_class_detail((string)$lead['status'])) ?>"><?= h($lead['status']) ?></span></td>
                                                <td><?= $lead['valor_estimado'] !== null ? h(number_format((float)$lead['valor_estimado'], 2, ',', '.')) : '-' ?></td>
                                                <td><?= $lead['comissao_prevista'] !== null ? h(number_format((float)$lead['comissao_prevista'], 2, ',', '.')) : '-' ?></td>
                                                <td><?= h(!empty($lead['criado_em']) ? date('d/m/Y H:i', strtotime($lead['criado_em'])) : '-') ?></td>
                                                <td><a class="btn btn-sm btn-primary" href="<?= h(url('admin/parceiros/lead_detalhe.php?id=' . (int)$lead['id'])) ?>">Ver</a></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="7" class="text-center text-muted py-4">Nenhum lead registado para este parceiro.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="rg-panel">
                    <div class="rg-panel-body">
                        <h5 class="fw-bold mb-3">Notas</h5>
                        <div class="rg-alert rg-alert-success mb-0"><?= nl2br(h($parceiro['notas'] ?: '-')) ?></div>
                    </div>
                </div>
            </div>
        </section>
<?php require BASE_PATH . '/app/views/layouts/admin_footer.php'; ?>
