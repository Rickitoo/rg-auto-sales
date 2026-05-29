<div class="inventory-page">
    <div class="rg-kpi-grid">
        <div class="rg-kpi-card is-info">
            <strong><?= h($totalCarros ?? 0) ?></strong>
            <span>Total de carros</span>
            <small>Viaturas no sistema</small>
        </div>
        <div class="rg-kpi-card is-success">
            <strong><?= h($totalDisponiveis ?? 0) ?></strong>
            <span>Disponiveis</span>
            <small>Prontas para venda</small>
        </div>
        <div class="rg-kpi-card is-warning">
            <strong><?= h($totalVendidos ?? 0) ?></strong>
            <span>Vendidos</span>
            <small>Historico comercial</small>
        </div>
    </div>

    <div class="rg-panel">
        <div class="rg-panel-body rg-section-head">
            <div>
                <h2>Carros Cadastrados</h2>
                <p>Gestao completa dos veiculos da RG Auto Sales</p>
            </div>

            <div class="rg-page-actions">
                <a href="<?= h(url('admin/dashboard.php')) ?>" class="btn btn-light">Dashboard</a>
                <a href="<?= h(url('admin/carros/adicionar_carro.php')) ?>" class="btn btn-primary">+ Adicionar Carro</a>
            </div>
        </div>
    </div>

    <div class="rg-panel">
        <div class="rg-panel-body">
            <form method="GET" class="rg-filter-grid">
                <input type="text" name="busca" class="form-control" placeholder="Buscar por marca ou modelo..." value="<?= h($busca) ?>">

                <select name="status" class="form-select">
                    <option value="">Todos os status</option>
                    <option value="disponivel" <?= $status === 'disponivel' ? 'selected' : '' ?>>Disponivel</option>
                    <option value="vendido" <?= $status === 'vendido' ? 'selected' : '' ?>>Vendido</option>
                </select>

                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="<?= h(url('admin/carros/listar_carros.php')) ?>" class="btn btn-secondary">Limpar</a>
            </form>
        </div>
    </div>

    <div class="rg-table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Imagem</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Ano</th>
                    <th>Preco</th>
                    <th>Fotos</th>
                    <th>Status</th>
                    <th>Registo</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($carros)): ?>
                    <?php foreach ($carros as $carro): ?>
                        <?php
                        $idCarro = (int)$carro['id_carro'];
                        $totalFotos = (int)($carro['total_fotos'] ?? 0);
                        ?>
                        <tr>
                            <td><?= h($idCarro) ?></td>

                            <td>
                                <?php if ($carro['img_src'] !== ''): ?>
                                    <img src="<?= h($carro['img_src']) ?>" alt="Capa" class="thumb">
                                <?php else: ?>
                                    <div class="thumb thumb-empty">Sem foto</div>
                                <?php endif; ?>
                            </td>

                            <td><?= h($carro['marca']) ?></td>
                            <td><?= h($carro['modelo']) ?></td>
                            <td><?= h($carro['ano']) ?></td>
                            <td><?= h(money($carro['preco'])) ?></td>

                            <td>
                                <span class="mini-badge"><?= h($totalFotos) ?> foto<?= $totalFotos === 1 ? '' : 's' ?></span>
                            </td>

                            <td>
                                <span class="badge <?= h($carro['status_classe']) ?>">
                                    <?= h(ucfirst((string)$carro['status'])) ?>
                                </span>
                            </td>

                            <td><?= h($carro['data_registo_formatada']) ?></td>

                            <td>
                                <div class="rg-row-actions">
                                    <a href="<?= h(url('admin/carros/editar_carro.php?id=' . $idCarro)) ?>" class="btn btn-primary btn-sm">Editar</a>
                                    <a href="<?= h(url('admin/gerir_fotos.php?id=' . $idCarro)) ?>" class="btn btn-dark btn-sm">Fotos</a>

                                    <?php if ($carro['status'] !== 'vendido'): ?>
                                        <form class="d-inline" method="POST" action="<?= h(url('admin/vendas/marcar_venda.php')) ?>">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="id" value="<?= $idCarro ?>">
                                            <button class="btn btn-success btn-sm" type="submit">Marcar Venda</button>
                                        </form>
                                    <?php endif; ?>

                                    <form class="d-inline" method="POST" action="<?= h(url('admin/carros/apagar_carro.php')) ?>" onsubmit="return confirm('Tens certeza que queres apagar este carro?')">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="id" value="<?= $idCarro ?>">
                                        <button class="btn btn-danger btn-sm" type="submit">Apagar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="empty">Nenhum carro encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
