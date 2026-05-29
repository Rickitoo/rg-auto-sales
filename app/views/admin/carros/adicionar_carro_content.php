<div class="inventory-page">
    <div class="rg-panel">
        <div class="rg-panel-body rg-section-head">
            <div>
                <h2>Adicionar Carro</h2>
                <p>Novo veiculo para stock e pagina publica.</p>
            </div>
            <div class="rg-page-actions">
                <a class="btn btn-light" href="<?= h(url('admin/carros/listar_carros.php')) ?>">Carros</a>
                <a class="btn btn-primary" href="<?= h(url('admin/dashboard.php')) ?>">Dashboard</a>
            </div>
        </div>
    </div>

    <?php if ($msg !== ''): ?>
        <div class="rg-alert <?= str_contains($msg, 'sucesso') ? 'rg-alert-success' : 'rg-alert-danger' ?>">
            <?= h($msg) ?>
        </div>
    <?php endif; ?>

    <div class="rg-panel">
        <div class="rg-panel-body">
            <form method="POST" enctype="multipart/form-data" class="rg-form-grid">
                <?= csrf_input() ?>

                <div>
                    <label for="marca">Marca</label>
                    <input type="text" id="marca" name="marca" class="form-control" placeholder="Ex: Toyota" value="<?= h($formData['marca'] ?? '') ?>" required>
                </div>

                <div>
                    <label for="modelo">Modelo</label>
                    <input type="text" id="modelo" name="modelo" class="form-control" placeholder="Ex: Hilux" value="<?= h($formData['modelo'] ?? '') ?>" required>
                </div>

                <div>
                    <label for="ano">Ano</label>
                    <input type="number" id="ano" name="ano" class="form-control" placeholder="Ex: 2020" value="<?= h($formData['ano'] ?? '') ?>" required>
                </div>

                <div>
                    <label for="preco">Preco (MT)</label>
                    <input type="number" id="preco" step="0.01" name="preco" class="form-control" placeholder="Ex: 850000" value="<?= h($formData['preco'] ?? '') ?>" required>
                </div>

                <div class="rg-field-full">
                    <label for="descricao">Descricao</label>
                    <textarea id="descricao" name="descricao" class="form-control" placeholder="Detalhes do carro..."><?= h($formData['descricao'] ?? '') ?></textarea>
                </div>

                <div class="rg-field-full rg-form-actions">
                    <a href="<?= h(url('admin/carros/listar_carros.php')) ?>" class="btn btn-light">Voltar a lista</a>
                    <button type="submit" class="btn btn-primary">Guardar Carro</button>
                </div>
            </form>
        </div>
    </div>
</div>
