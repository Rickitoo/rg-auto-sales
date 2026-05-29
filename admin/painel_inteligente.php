<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
require_admin();

function pi_money($v): string { return number_format((float)$v, 2, ',', '.') . ' MT'; }

function pi_table_exists(mysqli $con, string $table): bool {
    return function_exists('db_table_exists') ? db_table_exists($con, $table) : false;
}

function pi_col_exists(mysqli $con, string $table, string $col): bool {
    $table = mysqli_real_escape_string($con, $table);
    $col = mysqli_real_escape_string($con, $col);
    $q = mysqli_query($con, "SHOW COLUMNS FROM `$table` LIKE '$col'");
    return $q && mysqli_num_rows($q) > 0;
}

function pi_days_since(?string $date): ?int {
    if (!$date) return null;
    $ts = strtotime($date);
    return $ts ? max(0, (int)floor((time() - $ts) / 86400)) : null;
}

function pi_phone(?string $phone): string {
    $tel = preg_replace('/\D+/', '', (string)$phone);
    return $tel !== '' && !str_starts_with($tel, '258') ? '258' . ltrim($tel, '0') : $tel;
}

$hasFollowups = pi_table_exists($conexao, 'lead_followups');
$hasAtualizadoEm = pi_col_exists($conexao, 'leads', 'atualizado_em');
$selectUpdated = $hasAtualizadoEm ? 'atualizado_em' : 'criado_em';
$selectLastFollowup = $hasFollowups
    ? '(SELECT MAX(lf.criado_em) FROM lead_followups lf WHERE lf.lead_id = leads.id)'
    : 'NULL';

$resLeads = mysqli_query($conexao, "
    SELECT id, nome, telefone, marca, modelo, ano, status, criado_em,
           $selectUpdated AS ultima_atividade,
           $selectLastFollowup AS ultimo_followup
    FROM leads
    WHERE status NOT IN ('fechado','perdido')
    ORDER BY id DESC
    LIMIT 300
");

$leadsUrgentes = [];
$leadsParados = [];
$followupsPendentes = [];

while ($resLeads && $lead = mysqli_fetch_assoc($resLeads)) {
    $ref = $lead['ultimo_followup'] ?: ($lead['ultima_atividade'] ?? $lead['criado_em']);
    $dias = pi_days_since($ref);
    $lead['_dias'] = $dias;
    $lead['_sem_followup'] = empty($lead['ultimo_followup']);

    if ($dias !== null && $dias >= 7) {
        $leadsUrgentes[] = $lead;
    } elseif ($dias !== null && $dias >= 3) {
        $leadsParados[] = $lead;
    }

    if ($dias !== null && $dias >= 3) {
        $followupsPendentes[] = $lead;
    }
}

$vendasPendentes = [];
$res = mysqli_query($conexao, "
    SELECT id, marca, modelo, ano, status, data_venda, valor_venda, lucro
    FROM vendas
    WHERE status='PENDENTE'
    ORDER BY data_venda ASC, id ASC
    LIMIT 10
");
while ($res && $row = mysqli_fetch_assoc($res)) {
    $vendasPendentes[] = $row;
}

$pagamentosPendentes = [];
$res = mysqli_query($conexao, "
    SELECT id, marca, modelo, ano, status, data_venda, valor_venda, lucro
    FROM vendas
    WHERE status='PENDENTE'
    ORDER BY data_venda ASC, id ASC
    LIMIT 10
");
while ($res && $row = mysqli_fetch_assoc($res)) {
    $pagamentosPendentes[] = $row;
}

$missoes = [];
if ($pagamentosPendentes) $missoes[] = ['tipo' => 'financeiro', 'texto' => 'Regularizar ' . count($pagamentosPendentes) . ' pagamento(s) pendente(s).', 'url' => url('admin/vendas/vendas.php')];
if ($leadsUrgentes) $missoes[] = ['tipo' => 'urgente', 'texto' => 'Contactar ' . count($leadsUrgentes) . ' lead(s) urgente(s) hoje.', 'url' => url('admin/crm/inbox.php')];
if ($leadsParados) $missoes[] = ['tipo' => 'parado', 'texto' => 'Reativar ' . count($leadsParados) . ' lead(s) parado(s).', 'url' => url('admin/crm/inbox.php')];
if ($vendasPendentes) $missoes[] = ['tipo' => 'venda', 'texto' => 'Acompanhar ' . count($vendasPendentes) . ' venda(s) pendente(s).', 'url' => url('admin/vendas/vendas.php')];

require_once __DIR__ . '/../includes/layout_top.php';
?>

<div class="page-card">
    <h2>Painel Inteligente</h2>
    <p style="color:#667085;margin-top:4px">Proximas acoes comerciais, financeiras e de follow-up.</p>
</div>

<div class="rg-kpi-grid">
    <div class="rg-kpi-card"><strong><?= h(count($leadsUrgentes)) ?></strong><span>Leads urgentes</span></div>
    <div class="rg-kpi-card"><strong><?= h(count($leadsParados)) ?></strong><span>Leads parados</span></div>
    <div class="rg-kpi-card"><strong><?= h(count($followupsPendentes)) ?></strong><span>Follow-ups pendentes</span></div>
    <div class="rg-kpi-card"><strong><?= h(count($vendasPendentes)) ?></strong><span>Vendas pendentes</span></div>
    <div class="rg-kpi-card"><strong><?= h(count($pagamentosPendentes)) ?></strong><span>Pagamentos pendentes</span></div>
</div>

<div class="page-card">
    <h3>Sugestoes de proxima acao</h3>
    <?php if (!$missoes): ?>
        <p style="background:#dcfce7;color:#166534;padding:12px;border-radius:8px">Tudo em dia. Monitorar novos leads e manter follow-up ativo.</p>
    <?php endif; ?>
    <?php foreach ($missoes as $missao): ?>
        <a href="<?= h($missao['url']) ?>" class="rg-action-card">
            <?= h($missao['texto']) ?>
        </a>
    <?php endforeach; ?>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:18px">
    <div class="page-card">
        <h3>Leads urgentes</h3>
        <?php foreach (array_slice($leadsUrgentes, 0, 8) as $lead): ?>
            <?php $tel = pi_phone($lead['telefone']); $carro = trim(($lead['marca'] ?? '') . ' ' . ($lead['modelo'] ?? '') . ' ' . ($lead['ano'] ?? '')); ?>
            <div style="border-bottom:1px solid #e5e7eb;padding:10px 0">
                <strong><?= h($lead['nome']) ?></strong>
                <div style="color:#667085"><?= h($carro ?: 'Sem carro') ?> | <?= h($lead['_dias']) ?> dia(s) sem contacto</div>
                <a href="<?= h(url('admin/crm/inbox.php?id=' . (int)$lead['id'])) ?>">Abrir CRM</a>
                <?php if ($tel): ?> | <a target="_blank" rel="noopener" href="https://wa.me/<?= h($tel) ?>?text=<?= h(rawurlencode('Ola ' . $lead['nome'] . ', aqui e a RG Auto Sales. Quero dar seguimento ao seu interesse.')) ?>">WhatsApp</a><?php endif; ?>
                | <form class="d-inline" method="POST" action="<?= h(url('admin/vendas/marcar_venda.php')) ?>">
                    <?= csrf_input() ?>
                    <input type="hidden" name="lead_id" value="<?= (int)$lead['id'] ?>">
                    <button type="submit">Fechar venda</button>
                </form>
            </div>
        <?php endforeach; ?>
        <?php if (!$leadsUrgentes): ?><p>Sem leads urgentes.</p><?php endif; ?>
    </div>

    <div class="page-card">
        <h3>Leads parados / follow-ups pendentes</h3>
        <?php foreach (array_slice($followupsPendentes, 0, 8) as $lead): ?>
            <div style="border-bottom:1px solid #e5e7eb;padding:10px 0">
                <strong><?= h($lead['nome']) ?></strong>
                <div style="color:#667085"><?= h($lead['_sem_followup'] ? 'Sem resposta' : 'Parado') ?> | <?= h($lead['_dias']) ?> dia(s)</div>
                <a href="<?= h(url('admin/crm/inbox.php?id=' . (int)$lead['id'])) ?>">Adicionar follow-up</a>
            </div>
        <?php endforeach; ?>
        <?php if (!$followupsPendentes): ?><p>Sem follow-ups pendentes.</p><?php endif; ?>
    </div>
</div>

<div class="page-card">
    <h3>Vendas e pagamentos pendentes</h3>
    <?php if (!$vendasPendentes): ?>
        <p>Sem vendas pendentes.</p>
    <?php else: ?>
        <table>
            <tr><th>Venda</th><th>Data</th><th>Valor</th><th>Lucro</th><th>Acao</th></tr>
            <?php foreach ($vendasPendentes as $venda): ?>
                <tr>
                    <td><?= h(trim($venda['marca'] . ' ' . $venda['modelo'] . ' ' . $venda['ano'])) ?></td>
                    <td><?= h($venda['data_venda']) ?></td>
                    <td><?= h(pi_money($venda['valor_venda'])) ?></td>
                    <td><?= h(pi_money($venda['lucro'])) ?></td>
                    <td>
                        <a href="<?= h(url('admin/vendas/venda_detalhe.php?id=' . (int)$venda['id'])) ?>">Ver</a>
                        |
                        <form class="d-inline" method="POST" action="<?= h(url('admin/vendas/pagar_venda.php')) ?>">
                            <?= csrf_input() ?>
                            <input type="hidden" name="id" value="<?= (int)$venda['id'] ?>">
                            <button type="submit" onclick="return confirm('Marcar esta venda como paga?');">Pagar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/layout_bottom.php'; ?>
