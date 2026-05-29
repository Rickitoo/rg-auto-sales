<div class="leads-list-page">
    <div class="rg-panel">
        <div class="rg-panel-body rg-section-head">
            <div>
                <h2>CRM Leads - RG Auto Sales</h2>
                <p>Lista legada de oportunidades com ações rápidas de status, WhatsApp e venda.</p>
            </div>
            <div class="rg-page-actions">
                <a class="btn btn-light" href="<?= h(url('admin/leads/leads.php')) ?>">Lista principal</a>
                <a class="btn btn-primary" href="<?= h(url('admin/crm/inbox.php')) ?>">Abrir CRM</a>
            </div>
        </div>
    </div>

    <div class="rg-panel">
        <div class="rg-panel-body">
            <div class="leads-list-search">
                <input
                    type="text"
                    id="searchInput"
                    placeholder="Pesquisar por nome, telefone ou carro..."
                    onkeyup="searchTable()"
                >
            </div>
        </div>
    </div>

    <div class="rg-table-wrap leads-list-table-wrap">
        <table id="leadsTable" class="table table-hover align-middle m-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Telefone</th>
                    <th>Carro</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while($lead = mysqli_fetch_assoc($result)): ?>
                    <?php
                    $status = strtolower(trim($lead['status'] ?? 'novo'));

                    $telefone = preg_replace('/\D/', '', $lead['telefone']);

                    $carro = trim(
                        ($lead['marca'] ?? '-') . ' ' .
                        ($lead['modelo'] ?? '')
                    );
                    ?>

                    <tr>
                        <td>#<?= (int)$lead['id'] ?></td>
                        <td><strong><?= h($lead['nome']) ?></strong></td>
                        <td><?= h($lead['telefone']) ?></td>
                        <td><?= h($carro) ?></td>
                        <td>
                            <span class="legacy-lead-status legacy-lead-status-<?= h($status) ?>">
                                <?= ucfirst(h($status)) ?>
                            </span>
                        </td>
                        <td>
                            <?= h(date('d/m/Y H:i', strtotime($lead['created_at'] ?? 'now'))) ?>
                        </td>
                        <td>
                            <div class="legacy-lead-actions">
                                <a
                                    class="legacy-lead-btn legacy-lead-btn-view"
                                    href="ver_lead.php?id=<?= (int)$lead['id'] ?>"
                                >
                                    Ver
                                </a>

                                <a
                                    class="legacy-lead-btn legacy-lead-btn-whatsapp"
                                    href="https://wa.me/258<?= h($telefone) ?>"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    WhatsApp
                                </a>
                                <form class="d-inline" method="POST" action="leads_status.php">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="lead_id" value="<?= (int)$lead['id'] ?>">
                                    <input type="hidden" name="status" value="contactado">
                                    <button class="legacy-lead-btn legacy-lead-btn-status" type="submit">Contactado</button>
                                </form>
                                <form class="d-inline" method="POST" action="leads_status.php">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="lead_id" value="<?= (int)$lead['id'] ?>">
                                    <input type="hidden" name="status" value="negociacao">
                                    <button class="legacy-lead-btn legacy-lead-btn-status" type="submit">Negocia��o</button>
                                </form>
                                <form class="d-inline" method="POST" action="leads_status.php">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="lead_id" value="<?= (int)$lead['id'] ?>">
                                    <input type="hidden" name="status" value="fechado">
                                    <button class="legacy-lead-btn legacy-lead-btn-status" type="submit">Fechado</button>
                                </form>
                                <form class="d-inline" method="POST" action="leads_status.php">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="lead_id" value="<?= (int)$lead['id'] ?>">
                                    <input type="hidden" name="status" value="perdido">
                                    <button class="legacy-lead-btn legacy-lead-btn-status" type="submit">Perdido</button>
                                </form>

                                <form class="d-inline" method="POST" action="<?= h(url('admin/vendas/marcar_venda.php')) ?>">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="id" value="<?= (int)$lead['id'] ?>">
                                    <button class="legacy-lead-btn legacy-lead-btn-venda" type="submit">Vender</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function searchTable(){
    var input = document.getElementById("searchInput");
    var filter = input.value.toLowerCase();
    var table = document.getElementById("leadsTable");
    var tr = table.getElementsByTagName("tr");

    for(var i = 1; i < tr.length; i++){
        var rowText = tr[i].textContent.toLowerCase();

        if(rowText.includes(filter)){
            tr[i].style.display = "";
        } else {
            tr[i].style.display = "none";
        }
    }
}
</script>
