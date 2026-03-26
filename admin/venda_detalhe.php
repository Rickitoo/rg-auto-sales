<?php
// admin/venda_detalhe.php  (MODELO NOVO: LUCRO REAL)
// - Desliga totalmente o modelo antigo (valor_carro / comissao 7%)
// - Mostra apenas valor_venda, valor_proprietario, custos, lucro e comissões por lucro real
// - Comissões devem ser calculadas em includes/financeiro.php (recalcular_venda)

include("../auth.php");
include("../conexao.php");

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($v){ return number_format((float)$v, 2, ',', '.')." MT"; }

// evita redeclare se financeiro.php também tiver
if (!function_exists('col_exists')) {
  function col_exists(mysqli $con, string $table, string $col): bool {
    $table = mysqli_real_escape_string($con, $table);
    $col   = mysqli_real_escape_string($con, $col);
    $q = mysqli_query($con, "SHOW COLUMNS FROM `$table` LIKE '$col'");
    return $q && mysqli_num_rows($q) > 0;
  }
}

// tenta descobrir id do admin logado (ajusta se no teu auth.php usar outro nome)
function current_admin_id(): ?int {
  $keys = ['user_id','id','admin_id','usuario_id','utilizador_id'];
  foreach ($keys as $k) {
    if (isset($_SESSION[$k]) && is_numeric($_SESSION[$k])) return (int)$_SESSION[$k];
  }
  return null;
}

// inclui financeiro se existir
$financeiro_path = __DIR__ . "/includes/financeiro.php";
if (file_exists($financeiro_path)) {
  include($financeiro_path);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die("ID inválido.");

$flash = null;

// =========================
// Garante colunas do MODELO NOVO
// =========================
$requiredCols = [
  "valor_venda",
  "valor_proprietario",
  "total_custos",
  "lucro",
  "comissao_vendedor",
  "comissao_rg",
  "perc_vendedor",
  "perc_rg",
  "lucro_minimo",
  "precisa_aprovacao",
  "status"
];

foreach ($requiredCols as $c) {
  if (!col_exists($conexao, "vendas", $c)) {
    die("Modelo novo não está completo. Falta a coluna <b>".h($c)."</b> na tabela <b>vendas</b>.");
  }
}

$hasAtualizado  = col_exists($conexao, "vendas", "atualizado_em");

// parceiro/captador é opcional
$hasCParc   = col_exists($conexao, "vendas", "comissao_parceiro");
$hasPP      = col_exists($conexao, "vendas", "perc_parceiro");
$hasCapId   = col_exists($conexao, "vendas", "captador_id");

// aprovação (auditoria) é opcional
$hasAprovPor = col_exists($conexao, "vendas", "aprovado_por");
$hasAprovEm  = col_exists($conexao, "vendas", "aprovado_em");

// =========================
// AÇÕES POST
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $acao  = $_POST['acao'] ?? '';
  $token = $_POST['token'] ?? '';

  if (!hash_equals($_SESSION['csrf_token'], $token)) {
    $flash = ["type" => "danger", "msg" => "Ação bloqueada (token inválido)."];
  } elseif (!in_array($acao, ['pagar', 'cancelar', 'recalcular', 'aprovar'], true)) {
    $flash = ["type" => "danger", "msg" => "Ação inválida."];
  } else {

    // ✅ Recalcular sem mudar status
    if ($acao === "recalcular") {
      if (function_exists("recalcular_venda")) {
        $calc = recalcular_venda($conexao, $id);
        $flash = $calc["ok"]
          ? ["type"=>"success","msg"=>"Venda recalculada com sucesso."]
          : ["type"=>"danger","msg"=>"Falhou recalcular: ".$calc["erro"]];
      } else {
        $flash = ["type"=>"warning","msg"=>"financeiro.php não encontrado — não consegui recalcular."];
      }
    }

    // ✅ Aprovar (tira o bloqueio)
    if ($acao === "aprovar") {
      // confere se realmente precisa de aprovação e está pendente
      $chk = mysqli_prepare($conexao, "SELECT status, precisa_aprovacao FROM vendas WHERE id=? LIMIT 1");
      mysqli_stmt_bind_param($chk, "i", $id);
      mysqli_stmt_execute($chk);
      $r = mysqli_stmt_get_result($chk);
      $row = mysqli_fetch_assoc($r);
      mysqli_stmt_close($chk);

      if (!$row) {
        $flash = ["type"=>"danger","msg"=>"Venda não encontrada."];
      } elseif ($row['status'] !== 'PENDENTE') {
        $flash = ["type"=>"warning","msg"=>"Só é possível aprovar quando a venda está PENDENTE."];
      } elseif ((int)$row['precisa_aprovacao'] !== 1) {
        $flash = ["type"=>"info","msg"=>"Esta venda não precisa de aprovação."];
      } else {
        $adminId = current_admin_id();
        if (($hasAprovPor || $hasAprovEm) && $adminId === null && $hasAprovPor) {
          $flash = ["type"=>"danger","msg"=>"Não consegui identificar o utilizador logado para registrar 'aprovado_por'. Ajusta o nome do id no auth.php (session)."];
        } else {
          // monta update dinâmico conforme colunas existentes
          $sql = "UPDATE vendas SET precisa_aprovacao=0";
          if ($hasAprovPor) $sql .= ", aprovado_por=?";
          if ($hasAprovEm)  $sql .= ", aprovado_em=NOW()";
          if ($hasAtualizado) $sql .= ", atualizado_em=NOW()";
          $sql .= " WHERE id=? LIMIT 1";

          $st = mysqli_prepare($conexao, $sql);
          if (!$st) {
            $flash = ["type"=>"danger","msg"=>"Erro ao preparar aprovação: ".mysqli_error($conexao)];
          } else {
            if ($hasAprovPor) {
              mysqli_stmt_bind_param($st, "ii", $adminId, $id);
            } else {
              mysqli_stmt_bind_param($st, "i", $id);
            }

            if (mysqli_stmt_execute($st)) {
              $flash = ["type"=>"success","msg"=>"Venda aprovada. Agora já pode ser marcada como PAGA."];
              // recalcula para atualizar percentuais/comissões (ainda ficam 0 até PAGO)
              if (function_exists("recalcular_venda")) {
                recalcular_venda($conexao, $id);
              }
            } else {
              $flash = ["type"=>"danger","msg"=>"Erro ao aprovar: ".mysqli_error($conexao)];
            }
            mysqli_stmt_close($st);
          }
        }
      }
    }

    // ✅ Pagar/Cancelar
    if ($acao === "pagar" || $acao === "cancelar") {
      $novoStatus = ($acao === 'pagar') ? 'PAGO' : 'CANCELADO';

      // trava por aprovação (modelo novo)
      if ($acao === "pagar") {
        $chk = mysqli_prepare($conexao, "SELECT precisa_aprovacao FROM vendas WHERE id=? LIMIT 1");
        mysqli_stmt_bind_param($chk, "i", $id);
        mysqli_stmt_execute($chk);
        $r = mysqli_stmt_get_result($chk);
        $row = mysqli_fetch_assoc($r);
        mysqli_stmt_close($chk);

        if ($row && (int)$row["precisa_aprovacao"] === 1) {
          $flash = ["type"=>"warning","msg"=>"Esta venda precisa de aprovação (lucro abaixo do mínimo / lucro <= 0). Não foi marcada como PAGA."];
          $novoStatus = null;
        }
      }

      if ($novoStatus) {
        if ($hasAtualizado) {
          $stmt = mysqli_prepare($conexao, "
            UPDATE vendas
            SET status = ?, atualizado_em = NOW()
            WHERE id = ? AND status = 'PENDENTE'
          ");
        } else {
          $stmt = mysqli_prepare($conexao, "
            UPDATE vendas
            SET status = ?
            WHERE id = ? AND status = 'PENDENTE'
          ");
        }

        mysqli_stmt_bind_param($stmt, "si", $novoStatus, $id);

        if (mysqli_stmt_execute($stmt)) {
          if (mysqli_stmt_affected_rows($stmt) > 0) {

            // recalcular após mudar status (importante para comissões)
            if (function_exists("recalcular_venda")) {
              $calc = recalcular_venda($conexao, $id);
              if (!$calc["ok"]) {
                $flash = ["type"=>"warning","msg"=>"Status atualizado para {$novoStatus}, mas falhou recalcular: ".$calc["erro"]];
              } else {
                $flash = ["type"=>"success","msg"=>"Status atualizado para {$novoStatus} e recalculado."];
              }
            } else {
              $flash = ["type"=>"success","msg"=>"Status atualizado para {$novoStatus}."];
            }

            // se marcou como PAGO, marca lead como CONCLUIDO
            if ($novoStatus === 'PAGO' && col_exists($conexao, 'clientes', 'status')) {
              $q = mysqli_query($conexao, "SELECT cliente_id FROM vendas WHERE id=".(int)$id." LIMIT 1");
              $row = $q ? mysqli_fetch_assoc($q) : null;
              $cid = (int)($row['cliente_id'] ?? 0);

              if ($cid > 0) {
                $stmt2 = mysqli_prepare($conexao, "UPDATE clientes SET status='CONCLUIDO' WHERE id=?");
                mysqli_stmt_bind_param($stmt2, "i", $cid);
                mysqli_stmt_execute($stmt2);
                mysqli_stmt_close($stmt2);
              }
            }

          } else {
            $flash = ["type"=>"warning","msg"=>"Nada mudou. Talvez já não esteja PENDENTE."];
          }
        } else {
          $flash = ["type"=>"danger","msg"=>"Erro ao atualizar: ".mysqli_error($conexao)];
        }
        mysqli_stmt_close($stmt);
      }
    }
  }
}

// =========================
// Carregar venda + cliente (MODELO NOVO)
// =========================
$selectExtras = "";
if ($hasAtualizado) $selectExtras .= ", v.atualizado_em";
if ($hasCParc) $selectExtras .= ", v.comissao_parceiro";
if ($hasPP)    $selectExtras .= ", v.perc_parceiro";
if ($hasCapId) $selectExtras .= ", v.captador_id";
if ($hasAprovPor) $selectExtras .= ", v.aprovado_por";
if ($hasAprovEm)  $selectExtras .= ", v.aprovado_em";

$stmt = mysqli_prepare($conexao, "
  SELECT
    v.id, v.cliente_id, v.marca, v.modelo, v.ano,
    v.status, v.data_venda, v.criado_em
    $selectExtras,
    v.valor_venda, v.valor_proprietario, v.total_custos, v.lucro,
    v.perc_vendedor, v.perc_rg,
    v.comissao_vendedor, v.comissao_rg,
    v.lucro_minimo, v.precisa_aprovacao,
    c.nome AS cliente_nome, c.telefone AS cliente_telefone, c.email AS cliente_email
  FROM vendas v
  INNER JOIN clientes c ON c.id = v.cliente_id
  WHERE v.id = ?
  LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$venda = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$venda) die("Venda não encontrada.");

// msg na querystring
if (isset($_GET['msg']) && $_GET['msg'] === 'criada') {
  $flash = ["type"=>"success","msg"=>"Venda criada com sucesso."];
}

// Normaliza dados para mostrar
$valorVendaShow = (float)($venda["valor_venda"] ?? 0);
$valorPropShow  = (float)($venda["valor_proprietario"] ?? 0);
$totalCustos    = (float)($venda["total_custos"] ?? 0);
$lucroShow      = (float)($venda["lucro"] ?? 0);

$comVendShow    = (float)($venda["comissao_vendedor"] ?? 0);
$comRGShow      = (float)($venda["comissao_rg"] ?? 0);

$percVend       = (float)($venda["perc_vendedor"] ?? 0);
$percRG         = (float)($venda["perc_rg"] ?? 0);

$precisaApv     = (int)($venda["precisa_aprovacao"] ?? 0);
$lucroMin = (float)($venda["lucro_minimo"] ?? 0);

$motivoApv = '';
if ($precisaApv === 1) {
  if ($lucroShow <= 0) {
    $motivoApv = 'Lucro ≤ 0 (não comissiona).';
  } elseif ($lucroShow < $lucroMin) {
    $motivoApv = 'Lucro abaixo do mínimo (mínimo: '.money($lucroMin).').';
  } else {
    $motivoApv = 'Precisa aprovação.';
  }
}

$lucroMin = (float)($venda["lucro_minimo"] ?? 0);

$motivoApv = '';
if ($precisaApv === 1) {
  if ($lucroShow <= 0) {
    $motivoApv = 'Motivo: lucro ≤ 0 (não comissiona).';
  } elseif ($lucroShow < $lucroMin) {
    $motivoApv = 'Motivo: lucro abaixo do mínimo ('.money($lucroMin).').';
  } else {
    $motivoApv = 'Motivo: precisa de aprovação.';
  }
}


$hasParceiro = ($hasCapId && !empty($venda["captador_id"])) && $hasCParc;
$comParcShow = $hasParceiro ? (float)($venda["comissao_parceiro"] ?? 0) : 0.0;
$percParc    = ($hasParceiro && $hasPP) ? (float)($venda["perc_parceiro"] ?? 0) : 0.0;

?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin | Detalhe da Venda - RG Auto Sales</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background:#f6f7fb; }
    .card { border:0; border-radius:16px; }
  </style>
</head>
<body>
<div class="container py-4">

  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h3 class="mb-0">Detalhe da Venda #<?php echo (int)$venda['id']; ?></h3>
      <small class="text-muted">
        <?php echo 'Modelo: lucro real (comissões só quando PAGO).'; ?>
      </small>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-dark" href="vendas.php">Voltar</a>
      <a class="btn btn-outline-dark" href="nova_venda.php">Nova venda</a>
      <a class="btn btn-outline-secondary" href="custos.php?venda_id=<?php echo (int)$venda['id']; ?>">Custos da venda</a>

      <?php if ($venda['status'] === 'PAGO'): ?>
        <a class="btn btn-outline-dark" href="recibo.php?id=<?php echo (int)$venda['id']; ?>" target="_blank">Recibo (PDF)</a>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?php echo h($flash['type']); ?> alert-dismissible fade show" role="alert">
      <?php echo h($flash['msg']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="row g-3">

    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body">

          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="fw-semibold">Informações da venda</div>
            <?php
              $st = $venda['status'];
              $badge = 'secondary';
              if ($st === 'PENDENTE') $badge = 'warning';
              if ($st === 'PAGO') $badge = 'success';
              if ($st === 'CANCELADO') $badge = 'danger';
            ?>
            <div class="d-flex align-items-center gap-2">
              <span class="badge text-bg-<?php echo $badge; ?>"><?php echo h($st); ?></span>
            <?php if ($precisaApv === 1): ?>
                <span class="badge text-bg-dark">Precisa aprovação</span>
                <?php if (!empty($motivoApv)): ?>
                  <span class="text-muted small ms-2"><?php echo h($motivoApv); ?></span>
                <?php endif; ?>
            <?php endif; ?>

            </div>
            <?php if ($precisaApv === 1 && !empty($motivoApv)): ?>
              <div class="alert alert-warning py-2 mb-3">
                <strong>Precisa aprovação:</strong> <?php echo h($motivoApv); ?>
              </div>
            <?php endif; ?>

          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <div class="text-muted small">Data da venda</div>
              <div class="fw-semibold"><?php echo h($venda['data_venda']); ?></div>
            </div>
            <div class="col-md-6">
              <div class="text-muted small">Criado em</div>
              <div class="fw-semibold"><?php echo h($venda['criado_em']); ?></div>
              <?php if ($hasAtualizado && !empty($venda['atualizado_em'])): ?>
                <div class="text-muted small">Atualizado: <span class="fw-semibold"><?php echo h($venda['atualizado_em']); ?></span></div>
              <?php endif; ?>
              <?php if ($hasAprovEm && !empty($venda['aprovado_em'])): ?>
                <div class="text-muted small">Aprovado em: <span class="fw-semibold"><?php echo h($venda['aprovado_em']); ?></span></div>
              <?php endif; ?>
            </div>

            <div class="col-md-6">
              <div class="text-muted small">Carro</div>
              <div class="fw-semibold">
                <?php echo h($venda['marca']); ?> <?php echo h($venda['modelo']); ?> (<?php echo h($venda['ano']); ?>)
              </div>
            </div>

            <div class="col-md-6">
              <div class="text-muted small">Valor de venda (final)</div>
              <div class="fw-semibold"><?php echo money($valorVendaShow); ?></div>
            </div>

            <div class="col-md-6">
              <div class="text-muted small">Valor pago ao proprietário</div>
              <div class="fw-semibold"><?php echo money($valorPropShow); ?></div>
            </div>

            <div class="col-md-6">
              <div class="text-muted small">Total de custos</div>
              <div class="fw-semibold"><?php echo money($totalCustos); ?></div>
            </div>

            <div class="col-md-6">
              <div class="text-muted small">Lucro real</div>
              <div class="fw-semibold"><?php echo money($lucroShow); ?></div>
              <div class="text-muted small">
                Fórmula: valor_venda − valor_proprietário − custos
              </div>
            </div>

            <div class="col-md-6">
              <div class="text-muted small">Percentuais</div>
              <div class="fw-semibold">
                Vendedor: <?php echo h(number_format($percVend, 2, ',', '.')); ?>% •
                RG: <?php echo h(number_format($percRG, 2, ',', '.')); ?>%
                <?php if ($hasParceiro): ?>
                  • Parceiro: <?php echo h(number_format($percParc, 2, ',', '.')); ?>%
                <?php endif; ?>
              </div>
              <div class="text-muted small">
                * Só comissiona quando status = PAGO e lucro &gt; 0
              </div>
            </div>

            <div class="col-md-6">
              <div class="text-muted small">Comissão do vendedor</div>
              <div class="fw-semibold"><?php echo money($comVendShow); ?></div>
            </div>

            <?php if ($hasParceiro): ?>
              <div class="col-md-6">
                <div class="text-muted small">Comissão do parceiro (captador)</div>
                <div class="fw-semibold"><?php echo money($comParcShow); ?></div>
              </div>
            <?php endif; ?>

            <div class="col-md-6">
              <div class="text-muted small">Comissão da RG</div>
              <div class="fw-semibold"><?php echo money($comRGShow); ?></div>
            </div>

            <div class="col-md-6">
              <div class="text-muted small">Lucro mínimo configurado</div>
              <div class="fw-semibold"><?php echo money((float)$venda["lucro_minimo"]); ?></div>
            </div>
              <?php if ($precisaApv === 1 && !empty($motivoApv)): ?>
                <div class="col-12">
                  <div class="alert alert-warning mb-0">
                    <strong>Precisa aprovação:</strong> <?php echo h($motivoApv); ?>
                  </div>
                </div>
              <?php endif; ?>
          </div>

          <hr class="my-4">

          <div class="d-flex flex-wrap gap-2">
            <?php if (function_exists("recalcular_venda") && $venda['status'] !== 'CANCELADO'): ?>
              <form method="POST" action="">
                <input type="hidden" name="token" value="<?php echo h($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="acao" value="recalcular">
                <button class="btn btn-outline-dark" type="submit">Recalcular</button>
              </form>
            <?php endif; ?>

            <?php if ($venda['status'] === 'PENDENTE'): ?>

              <?php if ($precisaApv === 1): ?>
                <form method="POST" action="">
                  <input type="hidden" name="token" value="<?php echo h($_SESSION['csrf_token']); ?>">
                  <input type="hidden" name="acao" value="aprovar">
                  <button class="btn btn-dark" type="submit" onclick="return confirm('Aprovar esta venda para permitir pagamento?');">
                    Aprovar
                  </button>
                </form>
              <?php endif; ?>

              <form method="POST" action="">
                <input type="hidden" name="token" value="<?php echo h($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="acao" value="pagar">
                <button
                  class="btn btn-success"
                  type="submit"
                  <?php echo ($precisaApv === 1) ? 'disabled' : ''; ?>
                  onclick="return confirm('Marcar como PAGA?');"
                  title="<?php echo ($precisaApv === 1) ? ('Bloqueado: '.h($motivoApv ?: 'Precisa aprovação antes de pagar')) : ''; ?>"

                  Marcar Pago
                </button>
              </form>

              <form method="POST" action="">
                <input type="hidden" name="token" value="<?php echo h($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="acao" value="cancelar">
                <button class="btn btn-outline-danger" type="submit" onclick="return confirm('Cancelar esta venda?');">
                  Cancelar
                </button>
              </form>

            <?php endif; ?>
          </div>

        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="fw-semibold mb-2">Cliente</div>
          <div class="mb-1"><span class="text-muted small">Nome:</span> <span class="fw-semibold"><?php echo h($venda['cliente_nome']); ?></span></div>
          <div class="mb-1"><span class="text-muted small">Telefone:</span> <span class="fw-semibold"><?php echo h($venda['cliente_telefone']); ?></span></div>
          <div class="mb-1"><span class="text-muted small">Email:</span> <span class="fw-semibold"><?php echo h($venda['cliente_email']); ?></span></div>

          <div class="mt-3">
            <a class="btn btn-outline-primary w-100" href="dashboard.php">Voltar ao Dashboard</a>
          </div>
        </div>
      </div>
    </div>

  </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
