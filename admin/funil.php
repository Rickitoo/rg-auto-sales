<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
require_admin();

$stages = [
    'novo' => 'Novo',
    'contactado' => 'Contactado',
    'qualificado' => 'Qualificado',
    'agendado' => 'Agendado',
    'negociacao' => 'Negociacao',
    'fechado' => 'Fechado',
    'perdido' => 'Perdido',
];

$byStage = array_fill_keys(array_keys($stages), []);
$res = mysqli_query($conexao, "
    SELECT id, tipo, nome, telefone, email, mensagem, marca, modelo, ano, origem, status, criado_em, notas, proximo_contacto
    FROM leads
    ORDER BY atualizado_em DESC, id DESC
    LIMIT 500
");

while ($res && ($row = mysqli_fetch_assoc($res))) {
    $status = $row['status'] ?? 'novo';
    if (!isset($byStage[$status])) {
        $status = 'novo';
    }
    $byStage[$status][] = $row;
}

$stageKeys = array_keys($stages);

function crm_next_stage(array $keys, string $current): ?string {
    $i = array_search($current, $keys, true);
    return $i !== false && $i < count($keys) - 2 ? $keys[$i + 1] : null;
}

require_once __DIR__ . '/../includes/layout_top.php';
?>

<style>
    .crm-board{display:grid;grid-template-columns:320px 1fr;gap:18px;min-height:calc(100vh - 210px)}
    .crm-inbox{background:#fff;border-radius:12px;box-shadow:0 4px 18px rgba(16,24,40,.08);overflow:hidden}
    .crm-inbox-head{padding:16px;border-bottom:1px solid #e5e7eb}
    .crm-search{width:100%;border:1px solid #d0d5dd;border-radius:9px;padding:10px 12px;margin-top:10px}
    .crm-columns{display:flex;gap:14px;overflow-x:auto;padding-bottom:8px}
    .crm-col{min-width:290px;max-width:290px;background:#eef2f7;border-radius:12px;padding:10px}
    .crm-col h3{font-size:15px;margin:4px 4px 10px;display:flex;justify-content:space-between;color:#344054}
    .lead-card{background:#fff;border-radius:10px;padding:12px;margin-bottom:10px;box-shadow:0 1px 4px rgba(16,24,40,.08)}
    .lead-card strong{display:block;color:#111827;margin-bottom:4px}
    .lead-meta{color:#667085;font-size:12px;line-height:1.5}
    .lead-actions{display:flex;gap:7px;flex-wrap:wrap;margin-top:10px}
    .lead-actions a,.lead-actions button{border:0;border-radius:8px;padding:7px 9px;font-weight:800;font-size:12px;cursor:pointer}
    .wa{background:#12b76a;color:#fff}
    .detail{background:#e5e7eb;color:#111827}
    .advance{background:#00aeef;color:#fff}
    .crm-tip{background:#ecfdf3;border:1px solid #abefc6;color:#027a48;border-radius:10px;padding:12px;margin-bottom:14px}
    @media(max-width:980px){.crm-board{grid-template-columns:1fr}.crm-columns{display:grid;grid-template-columns:1fr}}
</style>

<div class="crm-tip">
    CRM estilo WhatsApp: priorize responder, mover o lead de etapa e fechar a venda. Quando marcar como fechado, o sistema abre o fluxo de nova venda.
</div>

<div class="crm-board">
    <aside class="crm-inbox">
        <div class="crm-inbox-head">
            <strong>Caixa comercial</strong>
            <input class="crm-search" id="leadSearch" placeholder="Pesquisar nome, telefone ou carro">
        </div>
        <div style="padding:12px">
            <?php foreach ($stages as $key => $label): ?>
                <div style="display:flex;justify-content:space-between;padding:9px 4px;border-bottom:1px solid #eef2f7">
                    <span><?= h($label) ?></span>
                    <strong><?= count($byStage[$key]) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </aside>

    <section class="crm-columns">
        <?php foreach ($stages as $stage => $label): ?>
            <div class="crm-col" data-stage="<?= h($stage) ?>">
                <h3><span><?= h($label) ?></span><span><?= count($byStage[$stage]) ?></span></h3>

                <?php if (count($byStage[$stage]) === 0): ?>
                    <div class="lead-meta" style="padding:14px 6px">Sem leads nesta etapa.</div>
                <?php endif; ?>

                <?php foreach ($byStage[$stage] as $lead): ?>
                    <?php
                    $telefone = preg_replace('/\D+/', '', $lead['telefone'] ?? '');
                    if ($telefone !== '' && !str_starts_with($telefone, '258')) {
                        $telefone = '258' . ltrim($telefone, '0');
                    }
                    $carro = trim(($lead['marca'] ?? '') . ' ' . ($lead['modelo'] ?? '') . ' ' . ($lead['ano'] ?? ''));
                    $waText = urlencode('Ola ' . ($lead['nome'] ?? '') . ', fala a RG Auto Sales. Estou a dar seguimento ao seu interesse em ' . ($carro ?: 'uma viatura') . '.');
                    $next = crm_next_stage($stageKeys, $stage);
                    ?>
                    <article class="lead-card" data-search="<?= h(strtolower(($lead['nome'] ?? '') . ' ' . ($lead['telefone'] ?? '') . ' ' . $carro)) ?>">
                        <strong><?= h($lead['nome']) ?></strong>
                        <div class="lead-meta">
                            <?= h($telefone ?: $lead['telefone']) ?><br>
                            <?= h($carro ?: ucfirst($lead['tipo'] ?? 'lead')) ?><br>
                            Origem: <?= h($lead['origem'] ?? 'site') ?> | #<?= (int)$lead['id'] ?>
                        </div>
                        <div class="lead-actions">
                            <?php if ($telefone): ?>
                                <a class="wa" target="_blank" rel="noopener" href="https://wa.me/<?= h($telefone) ?>?text=<?= h($waText) ?>">WhatsApp</a>
                            <?php endif; ?>
                            <a class="detail" href="<?= h(url('admin/leads/ver_lead.php?id=' . (int)$lead['id'])) ?>">Detalhe</a>
                            <?php if ($next): ?>
                                <button class="advance" type="button" data-id="<?= (int)$lead['id'] ?>" data-status="<?= h($next) ?>">Avancar</button>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </section>
</div>

<script>
document.querySelectorAll('.advance').forEach((button) => {
    button.addEventListener('click', async () => {
        const form = new FormData();
        form.append('lead_id', button.dataset.id);
        form.append('status', button.dataset.status);
        form.append('csrf_token', '<?= h(csrf_token()) ?>');

        const res = await fetch('<?= h(url('admin/leads/lead_move.php')) ?>', {method: 'POST', body: form});
        const data = await res.json().catch(() => ({ok:false, error:'Resposta invalida'}));

        if (data.redirect) {
            const target = new URL(data.redirect, window.location.href);
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = target.pathname;

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = 'csrf_token';
            csrf.value = '<?= h(csrf_token()) ?>';
            form.appendChild(csrf);

            const leadId = target.searchParams.get('lead_id') || data.lead_id;
            if (leadId) {
                const leadInput = document.createElement('input');
                leadInput.type = 'hidden';
                leadInput.name = 'lead_id';
                leadInput.value = leadId;
                form.appendChild(leadInput);
            }

            document.body.appendChild(form);
            form.submit();
            return;
        }

        if (data.ok) {
            window.location.reload();
            return;
        }

        alert(data.error || 'Nao foi possivel mover o lead.');
    });
});

document.getElementById('leadSearch').addEventListener('input', (event) => {
    const q = event.target.value.trim().toLowerCase();
    document.querySelectorAll('.lead-card').forEach((card) => {
        card.style.display = !q || card.dataset.search.includes(q) ? '' : 'none';
    });
});
</script>

<?php require_once __DIR__ . '/../includes/layout_bottom.php'; ?>
