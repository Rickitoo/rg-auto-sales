<div class="inventory-page">
    <div class="rg-panel">
        <div class="rg-panel-body rg-section-head">
            <div>
                <h2>Editar Carro</h2>
                <p>ID: <?= (int)$carro['id'] ?> - <?= h($carro['marca']) ?> <?= h($carro['modelo']) ?></p>
            </div>

            <div class="rg-page-actions">
                <a href="<?= h(url('admin/gerir_fotos.php?id=' . $id)) ?>" class="btn btn-dark">Gerir Fotos</a>
                <a href="<?= h(url('admin/carros/listar_carros.php')) ?>" class="btn btn-light">Voltar a Lista</a>
            </div>
        </div>
    </div>

    <?php if ($mensagem): ?>
        <div class="rg-alert rg-alert-success"><?= h($mensagem) ?></div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="rg-alert rg-alert-danger"><?= h($erro) ?></div>
    <?php endif; ?>

    <div class="rg-panel">
        <div class="rg-panel-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="acao" value="salvar_carro">

                <div class="rg-car-form-grid">
                    <div>
                        <label>Marca</label>
                        <input type="text" name="marca" class="form-control" value="<?= h($carro['marca']) ?>" required>
                    </div>

                    <div>
                        <label>Modelo</label>
                        <input type="text" name="modelo" class="form-control" value="<?= h($carro['modelo']) ?>" required>
                    </div>

                    <div>
                        <label>Ano</label>
                        <input type="number" name="ano" class="form-control" value="<?= h($carro['ano']) ?>" required>
                    </div>

                    <div>
                        <label>Preco</label>
                        <input type="number" step="0.01" name="preco" class="form-control" value="<?= h($carro['preco']) ?>" required>
                    </div>

                    <div>
                        <label>Status</label>
                        <select name="status" class="form-select">
                            <option value="disponivel" <?= $carro['status'] === 'disponivel' ? 'selected' : '' ?>>Disponivel</option>
                            <option value="vendido" <?= $carro['status'] === 'vendido' ? 'selected' : '' ?>>Vendido</option>
                        </select>
                    </div>

                    <div>
                        <label>Preco de Venda</label>
                        <input type="number" step="0.01" name="preco_venda" class="form-control" value="<?= h($carro['preco_venda']) ?>">
                    </div>

                    <div>
                        <label>Comissao</label>
                        <input type="number" step="0.01" name="comissao" class="form-control" value="<?= h($carro['comissao']) ?>">
                    </div>

                    <div>
                        <label>Data da Venda</label>
                        <input
                            type="datetime-local"
                            name="data_venda"
                            class="form-control"
                            value="<?= !empty($carro['data_venda']) ? date('Y-m-d\TH:i', strtotime($carro['data_venda'])) : '' ?>"
                        >
                    </div>

                    <div class="rg-field-full">
                        <label>Descricao</label>
                        <textarea name="descricao" class="form-control"><?= h($carro['descricao']) ?></textarea>
                    </div>

                    <div class="rg-field-full rg-form-actions">
                        <a href="<?= h(url('admin/carros/listar_carros.php')) ?>" class="btn btn-light">Voltar a Lista</a>
                        <button type="submit" class="btn btn-primary">Guardar Alteracoes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="rg-panel">
        <div class="rg-panel-body">
            <div class="rg-section-head">
                <div>
                    <h2>Resumo da Galeria</h2>
                    <p>Pre-visualizacao rapida das fotos deste carro</p>
                </div>
            </div>

            <div class="rg-gallery-wrap">
                <div>
                    <?php if ($imgCapa !== ''): ?>
                        <img src="<?= h($imgCapa) ?>" alt="Foto principal" class="cover-img">
                    <?php else: ?>
                        <div class="sem-foto">Sem foto principal</div>
                    <?php endif; ?>
                </div>

                <div>
                    <div class="rg-stats-grid">
                        <div class="stat-box">
                            <div class="stat-label">Total de fotos</div>
                            <div class="stat-value"><?= h($totalFotos) ?></div>
                        </div>

                        <div class="stat-box">
                            <div class="stat-label">Imagem principal</div>
                            <div class="stat-value"><?= $carro['imagem'] ? 'Definida' : 'Automatica / vazia' ?></div>
                        </div>

                        <div class="stat-box">
                            <div class="stat-label">Preco atual</div>
                            <div class="stat-value"><?= h(money($carro['preco'])) ?></div>
                        </div>

                        <div class="stat-box">
                            <div class="stat-label">Status</div>
                            <div class="stat-value"><?= h(ucfirst((string)$carro['status'])) ?></div>
                        </div>
                    </div>

                    <div class="rg-inline-actions">
                        <a href="<?= h(url('admin/gerir_fotos.php?id=' . $id)) ?>" class="btn btn-dark">Abrir gestor de fotos</a>
                    </div>

                    <div class="upload-box">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="acao" value="upload_fotos">

                            <label for="novas_fotos">Adicionar novas fotos</label>
                            <input type="file" name="novas_fotos[]" id="novas_fotos" class="form-control" accept=".jpg,.jpeg,.png,.webp" multiple>

                            <p class="muted">
                                Podes selecionar varias fotos ao mesmo tempo. Formatos aceites: JPG, JPEG, PNG e WEBP.
                            </p>

                            <button type="submit" class="btn btn-primary">Fazer Upload</button>
                        </form>
                    </div>
                </div>
            </div>

            <?php if ($resMiniFotos && mysqli_num_rows($resMiniFotos) > 0): ?>
                <div class="mini-gallery">
                    <?php while ($foto = mysqli_fetch_assoc($resMiniFotos)): ?>
                        <div class="mini-item">
                            <img src="<?= h(public_url('uploads/' . $foto['foto'])) ?>" alt="Miniatura">
                            <div class="mini-meta">Ordem: <?= (int)$foto['ordem'] ?></div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
