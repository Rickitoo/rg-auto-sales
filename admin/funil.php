<?php
// admin/funil.php
include("../auth.php");      // se o teu admin usa auth
include("../conexao.php");   // $conexao

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Status do funil (ajusta se o teu ENUM for diferente)
$stages = [
  'novo' => ['label' => 'NOVO', 'badge' => 'bg-danger'],
  'contactado' => ['label' => 'CONTACTADO', 'badge' => 'bg-primary'],
  'negociacao' => ['label' => 'NEGOCIAÇÃO', 'badge' => 'bg-warning text-dark'],
  'fechado' => ['label' => 'FECHADO', 'badge' => 'bg-success'],
  'perdido' => ['label' => 'PERDIDO', 'badge' => 'bg-secondary'],
];

// Puxa leads (últimos 400, ajusta se quiser)
$res = mysqli_query($conexao, "
  SELECT id, tipo, nome, telefone, email, mensagem, marca, modelo, ano, origem, status, criado_em
  FROM leads
  ORDER BY id DESC
  LIMIT 400
");
if(!$res) die("Erro SQL: " . mysqli_error($conexao));

// Agrupa por status
$byStage = [];
foreach ($stages as $k => $_) $byStage[$k] = [];

while($row = mysqli_fetch_assoc($res)){
  $s = $row['status'] ?? 'novo';
  if (!isset($byStage[$s])) $s = 'novo'; // fallback
  $byStage[$s][] = $row;
}

// helper: próxima fase / fase anterior (para botões)
$keys = array_keys($stages);
function prev_stage($keys, $current){
  $i = array_search($current, $keys, true);
  return ($i !== false && $i > 0) ? $keys[$i-1] : null;
}
function next_stage($keys, $current){
  $i = array_search($current, $keys, true);
  return ($i !== false && $i < count($keys)-1) ? $keys[$i+1] : null;
}

?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <title>Funil - RG</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .kanban-wrap { overflow-x: auto; padding-bottom: 12px; }
    .kanban { display: flex; gap: 14px; min-width: 1100px; }
    .kcol { width: 320px; flex: 0 0 320px; }
    .kcol .head { position: sticky; top: 0; z-index: 2; background: #f8f9fa; padding: 10px 10px 6px; border-radius: 10px; }
    .klist { max-height: calc(100vh - 190px); overflow-y: auto; padding-right: 6px; }
    .kcard { border: 1px solid rgba(0,0,0,.08); border-radius: 12px; }
    .smallmuted { font-size: 12px; color: #6c757d; }
    .btn-xs { padding: 2px 8px; font-size: 12px; }
  </style>
</head>
<body class="bg-light">
<div class="container-fluid py-3">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">Funil de Vendas</h3>
      <div class="smallmuted">Arrasta mentalmente o fluxo: NOVO → CONTACTADO → NEGOCIAÇÃO → FECHADO (ou PERDIDO)</div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-dark" href="leads.php">Lista</a>
      <a class="btn btn-outline-dark" href="dashboard.php">Dashboard</a>
    </div>
  </div>

  <div class="kanban-wrap">
    <div class="kanban">

      <?php foreach($stages as $stageKey => $meta): 
        $count = count($byStage[$stageKey] ?? []);
      ?>
        <div class="kcol dropzone" data-status="<?=h($stageKey)?>">
          <div class="head shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
              <span class="badge <?=h($meta['badge'])?>"><?=h($meta['label'])?></span>
              <span class="smallmuted"><?=h($count)?> lead(s)</span>
            </div>
          </div>

          <div class="klist mt-2">
            <?php if($count === 0): ?>
              <div class="text-center smallmuted py-3">Sem leads aqui.</div>
            <?php endif; ?>

            <?php foreach(($byStage[$stageKey] ?? []) as $row): 
              $prev = prev_stage($keys, $stageKey);
              $next = next_stage($keys, $stageKey);

              $carro = trim(($row['marca'] ?? '').' '.($row['modelo'] ?? '').' '.(($row['ano'] ?? '') ? '('.$row['ano'].')' : ''));
              $tipo = $row['tipo'] ?? '';
            ?>
              <div class="kcard bg-white p-3 mb-2 shadow-sm" draggable="true" data-lead-id="<?=h($row['id'])?>">
                <div class="d-flex justify-content-between align-items-start gap-2">
                  <div>
                    <div class="fw-semibold"><?=h($row['nome'] ?? '')?></div>
                    <div class="smallmuted">#<?=h($row['id'])?> • <?=h($tipo)?> • <?=h($row['origem'] ?? '')?></div>
                  </div>
                  <span class="badge <?=h($meta['badge'])?>"><?=h($meta['label'])?></span>
                </div>

                <div class="mt-2">
                  <div><span class="smallmuted">Tel:</span> <span class="fw-semibold"><?=h($row['telefone'] ?? '')?></span></div>
                  <?php if($carro !== ''): ?>
                    <div class="mt-1"><span class="smallmuted">Carro:</span> <?=h($carro)?></div>
                  <?php endif; ?>
                  <div class="mt-1 smallmuted">Criado: <?=h($row['criado_em'] ?? '')?></div>
                </div>

                <div class="d-flex gap-2 flex-wrap mt-3">
                  <?php if($prev): ?>
                    <a class="btn btn-outline-secondary btn-xs"
                       href="lead_status.php?id=<?=h($row['id'])?>&s=<?=h($prev)?>">
                       ◀ Voltar
                    </a>
                  <?php endif; ?>

                  <?php if($next): ?>
                    <a class="btn btn-outline-primary btn-xs"
                       href="lead_status.php?id=<?=h($row['id'])?>&s=<?=h($next)?>">
                       Avançar ▶
                    </a>
                  <?php endif; ?>

                  <a class="btn btn-outline-success btn-xs"
                     href="https://wa.me/<?=h(preg_replace('/\D+/', '', $row['telefone'] ?? ''))?>"
                     target="_blank" rel="noopener">
                     WhatsApp
                  </a>

                  <a class="btn btn-outline-dark btn-xs"
                     href="lead_detalhe.php?id=<?=h($row['id'])?>">
                     Detalhe
                  </a>
                </div>

              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>

    </div>
  </div>

</div>
<script>
  document.querySelectorAll('.kcard[draggable="true"]').forEach(card => {
    card.addEventListener('dragstart', (e) => {
      e.dataTransfer.setData('text/plain', card.dataset.leadId);
      e.dataTransfer.effectAllowed = 'move';
      card.classList.add('opacity-50');
    });
    card.addEventListener('dragend', () => card.classList.remove('opacity-50'));
  });

  document.querySelectorAll('.dropzone').forEach(zone => {
    zone.addEventListener('dragover', (e) => {
      e.preventDefault();
      zone.classList.add('border', 'border-2', 'border-primary', 'rounded');
    });

    zone.addEventListener('dragleave', () => {
      zone.classList.remove('border', 'border-2', 'border-primary', 'rounded');
    });

    zone.addEventListener('drop', async (e) => {
      e.preventDefault();
      zone.classList.remove('border', 'border-2', 'border-primary', 'rounded');

      const leadId = e.dataTransfer.getData('text/plain');
      const newStatus = zone.dataset.status;
      if (!leadId || !newStatus) return;

      // manda o card para o topo da lista da coluna
      const card = document.querySelector(`.kcard[data-lead-id="${leadId}"]`);
      const list = zone.querySelector('.klist');
      if (card && list) list.prepend(card);

      try {
        const fd = new FormData();
        fd.append('id', leadId);
        fd.append('status', newStatus);

        const res = await fetch('lead_move.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.redirect) {
          window.location.href = data.redirect;
          return;
        }

        if (!data.ok) {
          alert('Erro ao mover: ' + (data.error || 'desconhecido'));
          location.reload();
        }
      } catch (err) {
        alert('Falha ao atualizar no servidor.');
        location.reload();
      }
    });
  });
</script>
</body>
</html>