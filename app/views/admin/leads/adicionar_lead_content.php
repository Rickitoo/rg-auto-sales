<div class="lead-form-page">
    <section class="rg-panel">
        <div class="rg-panel-body rg-section-head">
            <div>
                <h2>Novo Lead</h2>
                <p>Registe uma nova oportunidade e associe uma viatura quando existir interesse específico.</p>
            </div>
            <div class="rg-page-actions">
                <a class="btn btn-light" href="<?= h(url('admin/leads/listar_leads.php')) ?>">Listar leads</a>
                <a class="btn btn-primary" href="<?= h(url('admin/crm/inbox.php')) ?>">Abrir CRM</a>
            </div>
        </div>
    </section>

    <?php if ($erro): ?>
        <div class="rg-alert rg-alert-danger"><?= h($erro) ?></div>
    <?php endif; ?>

    <section class="rg-panel">
        <div class="rg-panel-body">
            <form method="POST" class="rg-form-grid">
                <?= csrf_input() ?>
                <div>
                    <label for="nome">Nome</label>
                    <input class="form-control" type="text" id="nome" name="nome" required>
                </div>

                <div>
                    <label for="telefone">Telefone</label>
                    <input class="form-control" type="text" id="telefone" name="telefone" required>
                </div>

                <div class="rg-field-full">
                    <label for="carro_id">Carro (opcional)</label>
                    <select class="form-select" id="carro_id" name="carro_id">
                        <option value="0">-- Nenhum --</option>
                        <?php while($c = mysqli_fetch_assoc($carros)): ?>
                            <option value="<?= (int)$c['id'] ?>">
                                <?= h($c['marca']) ?> <?= h($c['modelo']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="rg-form-actions rg-field-full">
                    <a class="btn btn-light" href="<?= h(url('admin/leads/listar_leads.php')) ?>">Cancelar</a>
                    <button class="btn btn-primary" type="submit">Criar Lead</button>
                </div>
            </form>
        </div>
    </section>
</div>
