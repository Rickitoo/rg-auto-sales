<div class="crm-inbox-page">
    <div class="crm-inbox-shell">
        <aside class="crm-inbox-sidebar">
            <div class="crm-inbox-side-head">
                <div class="rg-section-head">
                    <div>
                        <h2>CRM Inbox</h2>
                        <p>Leads, follow-ups e WhatsApp.</p>
                    </div>
                    <div class="rg-page-actions">
                        <a class="btn btn-light" href="<?= h(url('admin/dashboard.php')) ?>">Dashboard</a>
                    </div>
                </div>

                <form class="crm-inbox-filters" method="GET" action="<?= h(url('admin/crm/inbox.php')) ?>">
                    <input type="text" name="q" value="<?= h($busca) ?>" placeholder="Pesquisar lead">
                    <select name="status">
                        <option value="">Todos</option>
                        <?php foreach ($statuses as $key => $label): ?>
                            <option value="<?= h($key) ?>" <?= $statusFiltro === $key ? 'selected' : '' ?>><?= h($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-dark">Filtrar</button>
                </form>
            </div>

            <div class="crm-lead-list">
                <?php foreach ($leads as $lead): ?>
                    <?php
                    $active = (int)$lead['id'] === $leadSelecionadoId;
                    $attention = $lead['_crm_attention'];
                    $attentionClass = $attention['urgente'] ? 'urgent' : ($attention['esquecido'] ? 'attention' : '');
                    $carro = trim(($lead['marca'] ?? '') . ' ' . ($lead['modelo'] ?? '') . ' ' . ($lead['ano'] ?? ''));
                    $iniciais = mb_strtoupper(mb_substr((string)$lead['nome'], 0, 1));
                    ?>
                    <a class="crm-lead-item <?= $active ? 'active' : '' ?> <?= h($attentionClass) ?>" href="<?= h(url('admin/crm/inbox.php?id=' . (int)$lead['id'] . ($busca !== '' ? '&q=' . urlencode($busca) : '') . ($statusFiltro !== '' ? '&status=' . urlencode($statusFiltro) : ''))) ?>">
                        <div class="avatar"><?= h($iniciais ?: 'L') ?></div>
                        <div class="crm-lead-main">
                            <div class="crm-lead-row">
                                <div class="crm-lead-name"><?= h($lead['nome']) ?></div>
                                <div class="crm-lead-time"><?= h(date('d/m', strtotime($lead['ultima_atividade'] ?? $lead['criado_em']))) ?></div>
                            </div>
                            <div class="crm-lead-meta"><?= h($lead['telefone']) ?><?= $carro !== '' ? ' | ' . h($carro) : '' ?></div>
                            <div class="crm-lead-signals">
                                <span class="badge s-<?= h($lead['status']) ?>"><?= h(status_label($statuses, $lead['status'])) ?></span>
                                <span class="smart <?= h($attention['badge']['class']) ?>"><?= h($attention['badge']['label']) ?></span>
                            </div>
                            <div class="crm-days">
                                <?= $attention['dias_sem_contacto'] !== null ? h($attention['dias_sem_contacto'] . ' dia(s) sem contacto') : 'Sem historico' ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </aside>

        <main class="crm-inbox-detail">
            <?php if ($leadSelecionado): ?>
                <?php
                $carroSelecionado = trim(($leadSelecionado['marca'] ?? '') . ' ' . ($leadSelecionado['modelo'] ?? '') . ' ' . ($leadSelecionado['ano'] ?? ''));
                $smartMessage = smart_whatsapp_message($leadSelecionado, $leadAttention);
                $smartWhatsappUrl = whatsapp_url($leadSelecionado, $leadAttention);
                ?>
                <div class="crm-inbox-topbar">
                    <div>
                        <h2><?= h($leadSelecionado['nome']) ?></h2>
                        <p><?= h($leadSelecionado['telefone']) ?><?= $leadSelecionado['email'] ? ' | ' . h($leadSelecionado['email']) : '' ?></p>
                    </div>
                    <div class="rg-page-actions">
                        <a class="btn btn-success" href="<?= h($smartWhatsappUrl) ?>" target="_blank" rel="noopener">Mensagem Inteligente</a>
                        <form class="d-inline" method="POST" action="<?= h(url('admin/vendas/marcar_venda.php')) ?>">
                            <?= csrf_input() ?>
                            <input type="hidden" name="lead_id" value="<?= (int)$leadSelecionado['id'] ?>">
                            <?php if (!empty($leadSelecionado['carro_id'])): ?>
                                <input type="hidden" name="carro_id" value="<?= (int)$leadSelecionado['carro_id'] ?>">
                            <?php endif; ?>
                            <button class="btn btn-primary" type="submit">Fechar venda</button>
                        </form>
                        <a class="btn btn-light" href="<?= h(url('admin/leads/leads.php')) ?>">Lista de leads</a>
                    </div>
                </div>

                <div class="crm-inbox-content">
                    <div class="rg-panel">
                        <div class="rg-panel-body">
                            <h5 class="fw-bold mb-3">Resumo do lead</h5>
                            <div class="rg-detail-grid">
                                <div class="rg-detail-item"><span class="label">Status</span><span class="value"><span class="badge s-<?= h($leadSelecionado['status']) ?>"><?= h(status_label($statuses, $leadSelecionado['status'])) ?></span></span></div>
                                <div class="rg-detail-item"><span class="label">Origem</span><span class="value"><?= h($leadSelecionado['origem'] ?? '-') ?></span></div>
                                <div class="rg-detail-item"><span class="label">Tipo</span><span class="value"><?= h($leadSelecionado['tipo'] ?? '-') ?></span></div>
                                <div class="rg-detail-item"><span class="label">Carro</span><span class="value"><?= h($carroSelecionado !== '' ? $carroSelecionado : '-') ?></span></div>
                                <div class="rg-detail-item"><span class="label">Criado em</span><span class="value"><?= h(date('d/m/Y H:i', strtotime($leadSelecionado['criado_em']))) ?></span></div>
                                <div class="rg-detail-item"><span class="label">Proximo contacto</span><span class="value"><?= h(!empty($leadSelecionado['proximo_evento']) ? date('d/m/Y H:i', strtotime($leadSelecionado['proximo_evento'])) : '-') ?></span></div>
                                <div class="rg-detail-item"><span class="label">Ultimo follow-up</span><span class="value"><?= h(!empty($leadSelecionado['ultimo_followup']) ? date('d/m/Y H:i', strtotime($leadSelecionado['ultimo_followup'])) : 'Sem follow-up') ?></span></div>
                                <div class="rg-detail-item"><span class="label">Dias sem contacto</span><span class="value"><?= h($leadAttention && $leadAttention['dias_sem_contacto'] !== null ? $leadAttention['dias_sem_contacto'] . ' dia(s)' : '-') ?></span></div>
                                <div class="rg-detail-item"><span class="label">Prioridade CRM</span><span class="value"><?php if ($leadAttention): ?><span class="smart <?= h($leadAttention['badge']['class']) ?>"><?= h($leadAttention['badge']['label']) ?></span><?php else: ?>-<?php endif; ?></span></div>
                            </div>
                        </div>
                    </div>

                    <div class="rg-panel">
                        <div class="rg-panel-body">
                            <h5 class="fw-bold mb-3">Acoes rapidas</h5>
                            <form class="crm-status-form" method="POST" action="<?= h(url('admin/crm/inbox.php')) ?>">
                                <?= csrf_input() ?>
                                <input type="hidden" name="acao" value="status">
                                <input type="hidden" name="lead_id" value="<?= (int)$leadSelecionado['id'] ?>">
                                <select name="status" class="form-select">
                                    <?php foreach ($statuses as $key => $label): ?>
                                        <option value="<?= h($key) ?>" <?= $leadSelecionado['status'] === $key ? 'selected' : '' ?>><?= h($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-dark" type="submit">Alterar status</button>
                                <a class="btn btn-light" href="<?= h(url('admin/leads/ver_lead.php?id=' . (int)$leadSelecionado['id'])) ?>">Abrir detalhe classico</a>
                            </form>
                        </div>
                    </div>

                    <div class="rg-panel">
                        <div class="rg-panel-body">
                            <h5 class="fw-bold mb-3">Mensagem WhatsApp sugerida</h5>
                            <div class="smart-message">
                                <div class="smart-message-top">
                                    <div class="smart-message-title"><?= h($smartMessage['label']) ?></div>
                                    <a class="btn btn-success" href="<?= h($smartWhatsappUrl) ?>" target="_blank" rel="noopener">Abrir WhatsApp</a>
                                </div>
                                <div class="smart-message-text"><?= h($smartMessage['texto']) ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="rg-panel">
                        <div class="rg-panel-body">
                            <h5 class="fw-bold mb-3">Mensagem inicial</h5>
                            <div class="crm-message"><?= h($leadSelecionado['mensagem'] ?: 'Sem mensagem registada.') ?></div>
                        </div>
                    </div>

                    <div class="rg-panel">
                        <div class="rg-panel-body">
                            <h5 class="fw-bold mb-3">Adicionar follow-up</h5>
                            <form class="crm-note-form" method="POST" action="<?= h(url('admin/crm/inbox.php')) ?>">
                                <?= csrf_input() ?>
                                <input type="hidden" name="acao" value="followup">
                                <input type="hidden" name="lead_id" value="<?= (int)$leadSelecionado['id'] ?>">
                                <textarea name="mensagem" class="form-control" placeholder="Registar nota, chamada, resposta do cliente ou proximo passo..." required></textarea>
                                <div class="crm-note-form-row">
                                    <select name="status" class="form-select">
                                        <option value="">Status desta nota</option>
                                        <?php foreach ($statuses as $key => $label): ?>
                                            <option value="<?= h($key) ?>" <?= $leadSelecionado['status'] === $key ? 'selected' : '' ?>><?= h($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-dark" type="submit">Guardar nota</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="rg-panel">
                        <div class="rg-panel-body">
                            <h5 class="fw-bold mb-3">Historico de acompanhamento</h5>
                            <?php if ($followups): ?>
                                <div class="crm-timeline">
                                    <?php foreach ($followups as $item): ?>
                                        <?php
                                        $autor = $item['admin_nome'] ?: 'Admin';
                                        $inicial = mb_strtoupper(mb_substr((string)$autor, 0, 1));
                                        ?>
                                        <div class="crm-timeline-item">
                                            <div class="timeline-dot"><?= h($inicial ?: 'A') ?></div>
                                            <div class="timeline-card">
                                                <div class="timeline-top">
                                                    <div>
                                                        <div class="timeline-user"><?= h($autor) ?></div>
                                                        <?php if (!empty($item['status'])): ?>
                                                            <span class="badge s-<?= h($item['status']) ?>"><?= h(status_label($statuses, $item['status'])) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="timeline-date"><?= h(date('d/m/Y H:i', strtotime($item['criado_em']))) ?></div>
                                                </div>
                                                <div class="timeline-text"><?= h($item['mensagem']) ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="timeline-empty">Ainda nao ha follow-ups registados para este lead.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="rg-panel">
                        <div class="rg-panel-body">
                            <h5 class="fw-bold mb-3">Notas internas</h5>
                            <div class="crm-message is-muted"><?= h($leadSelecionado['notas'] ?: 'Sem notas internas.') ?></div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty">
                    <div>
                        <h2>Nenhum lead encontrado</h2>
                        <p>Quando existirem leads, eles aparecem nesta caixa de entrada.</p>
                        <a class="btn btn-dark" href="<?= h(url('admin/leads/leads.php')) ?>">Voltar para leads</a>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>
