<div class="lead-detail-page">
    <section class="rg-panel">
        <div class="rg-panel-body">
            <div class="rg-section-head">
                <div>
                    <h2><?= h($lead['nome']) ?></h2>
                    <p>Lead #<?= h($lead_id) ?> em acompanhamento comercial.</p>
                </div>
                <div class="rg-page-actions">
                    <?php if (($lead['origem'] ?? '') === 'importacao'): ?>
                        <span class="lead-origin-badge">Importacao</span>
                    <?php endif; ?>
                    <span class="lead-status-badge lead-status-<?= h($statusClass) ?>"><?= h($lead['status']) ?></span>
                    <a class="btn btn-light" href="<?= h(url('admin/leads/leads.php')) ?>">Voltar</a>
                </div>
            </div>

            <div class="lead-detail-grid">
                <div class="rg-detail-item">
                    <span class="label">Telefone</span>
                    <span class="value"><?= h($lead['telefone']) ?></span>
                </div>
                <div class="rg-detail-item">
                    <span class="label">Carro</span>
                    <span class="value"><?= h(trim(($lead['marca'] ?? '') . ' ' . ($lead['modelo'] ?? '')) ?: '-') ?></span>
                </div>
                <div class="rg-detail-item">
                    <span class="label">Ultima interacao</span>
                    <span class="value"><?= h($lead['ultima_interacao'] ?? 'Sem interacao') ?></span>
                </div>
                <div class="rg-detail-item">
                    <span class="label">Status</span>
                    <span class="value"><?= h($lead['status']) ?></span>
                </div>
            </div>

            <div class="rg-inline-actions">
                <a href="https://wa.me/<?= h($telefoneWhatsapp) ?>" target="_blank" rel="noopener" class="btn btn-success">WhatsApp</a>
                <form class="d-inline" method="POST" action="<?= h(url('admin/vendas/marcar_venda.php')) ?>">
                    <?= csrf_input() ?>
                    <input type="hidden" name="lead_id" value="<?= (int)$lead_id ?>">
                    <button class="btn btn-primary" type="submit">Fechar Venda</button>
                </form>
                <a href="<?= h(url('admin/crm/inbox.php?id=' . (int)$lead_id)) ?>" class="btn btn-warning">CRM / Follow-up</a>
            </div>
        </div>
    </section>

    <section class="lead-detail-layout">
        <div class="lead-chat" id="chat">
            <?php while ($m = mysqli_fetch_assoc($mensagens)): ?>
                <div class="lead-message lead-message-<?= h($m['tipo']) ?>">
                    <?= nl2br(h($m['mensagem'])) ?>
                    <small><?= h(date('d/m/Y H:i', strtotime($m['criado_em']))) ?></small>
                </div>
            <?php endwhile; ?>
        </div>

        <div class="rg-panel">
            <div class="rg-panel-body">
                <h2 class="crm-section-title">Enviar mensagem</h2>

                <form method="POST" class="lead-message-form">
                    <?= csrf_input() ?>
                    <textarea name="mensagem" placeholder="Digite uma mensagem..." required></textarea>
                    <button type="submit" class="btn btn-primary">Enviar Mensagem</button>
                </form>

                <div class="lead-quick-buttons">
                    <button type="button" data-fast-msg="Olá, ainda está interessado no veículo?">Follow-up</button>
                    <button type="button" data-fast-msg="Tenho uma proposta especial para si hoje.">Oferta</button>
                    <button type="button" data-fast-msg="Posso reservar o carro para si ainda hoje?">Reserva</button>
                    <button type="button" data-fast-msg="Quando gostaria de agendar a visita ou test drive?">Test Drive</button>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
document.querySelectorAll('[data-fast-msg]').forEach(function(button) {
    button.addEventListener('click', function() {
        var textarea = document.querySelector('textarea[name="mensagem"]');
        if (textarea) {
            textarea.value = button.getAttribute('data-fast-msg') || '';
            textarea.focus();
        }
    });
});

window.addEventListener('load', function() {
    var chat = document.getElementById('chat');
    if (chat) {
        chat.scrollTop = chat.scrollHeight;
    }
});
</script>
