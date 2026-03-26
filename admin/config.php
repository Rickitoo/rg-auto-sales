<?php
include("../auth.php");
include("../conexao.php");
if (session_status() === PHP_SESSION_NONE) session_start();

$flash = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["token"] ?? "";
    if (!hash_equals($_SESSION["csrf_token"], $token)) {
        $flash = ["type"=>"danger","msg"=>"Token inválido."];
    } else {
        $percent = (float)($_POST["percent"] ?? 0.07);
        $minimo  = (float)($_POST["minimo"] ?? 20000);
        $rg      = (float)($_POST["rg_share"] ?? 0.40);
        $vend    = (float)($_POST["vend_share"] ?? 0.30);
        $cap     = (float)($_POST["cap_share"] ?? 0.30);

        if ($percent <= 0 || $percent >= 1) $flash = ["type"=>"danger","msg"=>"Percent inválido (ex: 0.07)."];
        elseif ($minimo < 0) $flash = ["type"=>"danger","msg"=>"Mínimo inválido."];
        elseif (abs(($rg+$vend+$cap) - 1.0) > 0.0001) $flash = ["type"=>"danger","msg"=>"As shares precisam somar 1.0 (ex: 0.4+0.3+0.3)."];
        else {
            $stmt = mysqli_prepare($conexao, "INSERT INTO config (percent_comissao, minimo_comissao, rg_share, vendedor_share, captador_share) VALUES (?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, "ddddd", $percent, $minimo, $rg, $vend, $cap);
            if (mysqli_stmt_execute($stmt)) $flash = ["type"=>"success","msg"=>"Configurações atualizadas."];
            else $flash = ["type"=>"danger","msg"=>"Erro: ".mysqli_error($conexao)];
            mysqli_stmt_close($stmt);
        }
    }
}

$res = mysqli_query($conexao, "SELECT * FROM config ORDER BY id DESC LIMIT 1");
$cfg = $res ? (mysqli_fetch_assoc($res) ?: []) : [];
?>
<!doctype html><html lang="pt"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin | Config</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{background:#f6f7fb}.card{border:0;border-radius:16px}</style>
</head><body>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div><h3 class="mb-0">Configurações</h3><small class="text-muted">Comissão e divisão interna</small></div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-dark" href="dashboard.php">Dashboard</a>
      <a class="btn btn-outline-dark" href="vendas.php">Vendas</a>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?php echo $flash["type"]; ?>"><?php echo htmlspecialchars($flash["msg"]); ?></div>
  <?php endif; ?>

  <div class="card shadow-sm"><div class="card-body">
    <form method="POST">
      <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>">

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Percent comissão (ex: 0.07)</label>
          <input class="form-control" name="percent" type="number" step="0.0001" value="<?php echo htmlspecialchars($cfg["percent_comissao"] ?? "0.0700"); ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Mínimo comissão (MT)</label>
          <input class="form-control" name="minimo" type="number" step="0.01" value="<?php echo htmlspecialchars($cfg["minimo_comissao"] ?? "20000"); ?>">
        </div>
      </div>

      <hr class="my-4">

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">RG share (ex: 0.40)</label>
          <input class="form-control" name="rg_share" type="number" step="0.0001" value="<?php echo htmlspecialchars($cfg["rg_share"] ?? "0.4000"); ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Vendedor share (ex: 0.30)</label>
          <input class="form-control" name="vend_share" type="number" step="0.0001" value="<?php echo htmlspecialchars($cfg["vendedor_share"] ?? "0.3000"); ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Captador share (ex: 0.30)</label>
          <input class="form-control" name="cap_share" type="number" step="0.0001" value="<?php echo htmlspecialchars($cfg["captador_share"] ?? "0.3000"); ?>">
        </div>
      </div>

      <div class="mt-4 d-grid">
        <button class="btn btn-success btn-lg">Salvar</button>
      </div>
    </form>
  </div></div>
</div>
</body></html>
