<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

$statuses = ['novo' => 'Novo', 'contactado' => 'Contactado', 'negociacao' => 'Negociacao', 'fechado' => 'Fechado', 'perdido' => 'Perdido'];

function partner_lead_detail_badge(string $status): string
{
    return match ($status) {
        'fechado' => 'bg-success',
        'perdido' => 'bg-danger',
        'negociacao' => 'bg-warning text-dark',
        'contactado' => 'bg-primary',
        default => 'bg-info',
    };
}

function partner_lead_detail_wa(?string $numero, string $nome): string
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
    redirect_to('admin/parceiros/leads.php');
}

$stmt = mysqli_prepare($conexao, "
    SELECT pl.*, p.nome AS parceiro_nome, p.telefone AS parceiro_telefone, p.whatsapp AS parceiro_whatsapp, p.email AS parceiro_email, p.tipo, p.nivel, p.cidade
    FROM parceiro_leads pl
    INNER JOIN parceiros p ON p.id = pl.parceiro_id
    WHERE pl.id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$lead = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$lead) {
    $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Lead de parceiro nao encontrado.'];
    redirect_to('admin/parceiros/leads.php');
}

$waUrl = partner_lead_detail_wa($lead['telefone_lead'] ?? '', $lead['nome_lead'] ?? '');
$pageTitle = 'Detalhe do Lead de Parceiro';
$pageSubtitle = 'Oportunidade manual da rede RG Partner Network';
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
                        <div><h2><?= h($lead['nome_lead'] ?: 'Lead #' . (int)$lead['id']) ?></h2><p>Parceiro: <?= h($lead['parceiro_nome']) ?></p></div>
                        <div class="rg-page-actions">
                            <a class="btn btn-light" href="<?= h(url('admin/parceiros/leads.php')) ?>">Leads de Parceiros</a>
                            <?php if ($waUrl !== ''): ?><a class="btn btn-success" target="_blank" rel="noopener" href="<?= h($waUrl) ?>">WhatsApp</a><?php endif; ?>
                            <?php if ((int)($lead['sincronizado_crm'] ?? 0) === 1 && (int)($lead['crm_lead_id'] ?? 0) > 0): ?>
                                <a class="btn btn-info" href="<?= h(url('admin/leads/ver_lead.php?id=' . (int)$lead['crm_lead_id'])) ?>">Ver Lead no CRM</a>
                            <?php elseif ((int)($lead['sincronizado_crm'] ?? 0) !== 1): ?>
                                <form method="POST" action="<?= h(url('admin/parceiros/lead_sync.php')) ?>" class="d-inline">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="parceiro_lead_id" value="<?= h((int)$lead['id']) ?>">
                                    <button class="btn btn-dark" type="submit">Enviar para CRM</button>
                                </form>
                            <?php endif; ?>
                            <a class="btn btn-primary" href="<?= h(url('admin/parceiros/lead_editar.php?id=' . (int)$lead['id'])) ?>">Editar</a>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-lg-5">
                        <div class="rg-panel">
                            <div class="rg-panel-body">
                                <h5 class="fw-bold mb-3">Dados do parceiro</h5>
                                <div class="rg-detail-grid">
                                    <div class="rg-detail-item rg-field-full"><span class="label">Parceiro</span><span class="value"><a href="<?= h(url('admin/parceiros/detalhe.php?id=' . (int)$lead['parceiro_id'])) ?>"><?= h($lead['parceiro_nome']) ?></a></span></div>
                                    <div class="rg-detail-item"><span class="label">Tipo</span><span class="value"><?= h($lead['tipo'] ?: '-') ?></span></div>
                                    <div class="rg-detail-item"><span class="label">Nivel</span><span class="value"><?= h($lead['nivel'] ?: '-') ?></span></div>
                                    <div class="rg-detail-item"><span class="label">Cidade</span><span class="value"><?= h($lead['cidade'] ?: '-') ?></span></div>
                                    <div class="rg-detail-item"><span class="label">Contacto</span><span class="value"><?= h($lead['parceiro_whatsapp'] ?: ($lead['parceiro_telefone'] ?: '-')) ?></span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="rg-panel">
                            <div class="rg-panel-body">
                                <h5 class="fw-bold mb-3">Dados do lead</h5>
                                <div class="rg-detail-grid">
                                    <div class="rg-detail-item"><span class="label">Telefone</span><span class="value"><?= h($lead['telefone_lead'] ?: '-') ?></span></div>
                                    <div class="rg-detail-item"><span class="label">Modelo</span><span class="value"><?= h($lead['modelo_interesse'] ?: '-') ?></span></div>
                                    <div class="rg-detail-item"><span class="label">Status</span><span class="value"><span class="badge <?= h(partner_lead_detail_badge((string)$lead['status'])) ?>"><?= h($statuses[$lead['status']] ?? $lead['status']) ?></span></span></div>
                                    <div class="rg-detail-item"><span class="label">Sincronizacao CRM</span><span class="value"><?php if ((int)($lead['sincronizado_crm'] ?? 0) === 1): ?><span class="badge bg-success">Sincronizado</span><?php else: ?><span class="badge bg-warning text-dark">Pendente</span><?php endif; ?></span></div>
                                    <div class="rg-detail-item"><span class="label">Origem</span><span class="value"><?= h($lead['origem'] ?: '-') ?></span></div>
                                    <div class="rg-detail-item"><span class="label">Lead CRM</span><span class="value"><?= (int)($lead['crm_lead_id'] ?? 0) > 0 ? h('#' . (int)$lead['crm_lead_id']) : '-' ?></span></div>
                                    <div class="rg-detail-item"><span class="label">Valor estimado</span><span class="value"><?= $lead['valor_estimado'] !== null ? h(number_format((float)$lead['valor_estimado'], 2, ',', '.')) : '-' ?></span></div>
                                    <div class="rg-detail-item"><span class="label">Comissao prevista</span><span class="value"><?= $lead['comissao_prevista'] !== null ? h(number_format((float)$lead['comissao_prevista'], 2, ',', '.')) : '-' ?></span></div>
                                    <div class="rg-detail-item"><span class="label">Sincronizado em</span><span class="value"><?= !empty($lead['sincronizado_em']) ? h(date('d/m/Y H:i', strtotime($lead['sincronizado_em']))) : '-' ?></span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rg-panel"><div class="rg-panel-body"><h5 class="fw-bold mb-3">Observacoes</h5><div class="rg-alert rg-alert-success mb-0"><?= nl2br(h($lead['observacoes'] ?: '-')) ?></div></div></div>
            </div>
        </section>
<?php require BASE_PATH . '/app/views/layouts/admin_footer.php'; ?>
