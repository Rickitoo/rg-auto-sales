<?php
// admin/vendedor_ver.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include("../auth.php");     // se não tiveres auth, remove esta linha
include("../conexao.php");

if (session_status() === PHP_SESSION_NONE) session_start();

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($v){ return number_format((float)$v, 2, ',', '.') . " MT"; }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die("ID inválido.");

// Buscar pedido
$stmt = mysqli_prepare($conexao, "SELECT * FROM vendedores WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$v = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$v) die("Pedido não encontrado.");

// Buscar fotos
$stmt2 = mysqli_prepare($conexao, "SELECT id, arquivo FROM vendedores_fotos WHERE vendedor_id = ? ORDER BY id DESC");
mysqli_stmt_bind_param($stmt2, "i", $id);
mysqli_stmt_execute($stmt2);
$res2 = mysqli_stmt_get_result($stmt2);
$fotos = [];
while($f = mysqli_fetch_assoc($res2)) $fotos[] = $f;
mysqli_stmt_close($stmt2);

// WhatsApp link (ajustável)
$telDigits = preg_replace('/\D+/', '', (string)$v['telefone']);
if (strpos($telDigits, '258') === 0) {
  $waBase = "https://wa.me/" . $telDigits;
} else {
  $waBase = "https://wa.me/258" . $telDigits;
}
$msg = rawurlencode("Olá " . $v['nome'] . ", aqui é a RG Auto Sales. Recebemos o seu pedido para vender o " . $v['marca'] . " " . $v['modelo'] . " (" . $v['ano'] . "). Vamos avançar?");
$waLink = $waBase . "?text=" . $msg;
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin | Pedido #<?php echo (int)$v['id']; ?></title>
  <style>
    body { font-family: Arial, sans-serif; background:#f6f7fb; margin:0; padding:20px; }
    .card { background:#fff; border-radius:14px; padding:16px; box-shadow:0 6px 16px rgba(0,0,0,.07); }
    .top { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap; }
    .muted { color:#666; font-size: 13px; }
    .grid { display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px; }
    .row { display:flex; gap:10px; flex-wrap:wrap; margin-top: 14px; }
    .btn { display:inline-block; padding:10px 12px; border-radius:10px; text-decoration:none; font-weight:700; font-size: 14px; }
    .btn-back { background:#111; color:#fff; }
    .btn-wa { background:#25D366; color:#fff; }
    .box { background:#f2f4ff; border-radius:12px; padding:12px; }
    .gallery { margin-top: 16px; display:grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
    .gallery a { display:block; border-radius:12px; overflow:hidden; box-shadow:0 6px 16px rgba(0,0,0,.08); background:#fff; }
    .gallery img { width:100%; height:160px; object-fit:cover; display:block; }
    .note { margin-top: 12px; background:#fff7e6; border:1px solid #ffe3a3; padding:12px; border-radius:12px; }
    @media (max-width: 900px){
      .grid { grid-template-columns: 1fr; }
      .gallery { grid-template-columns: repeat(2, 1fr); }
      .gallery img { height:170px; }
    }
  </style>
</head>
<body>

<div class="card">
  <div class="top">
    <div>
      <h2 style="margin:0;">Pedido #<?php echo (int)$v['id']; ?> — <?php echo h($v['nome']); ?></h2>
      <div class="muted">
        Tel: <?php echo h($v['telefone']); ?> · Email: <?php echo h($v['email']); ?>
      </div>
      <?php if (!empty($v['criado_em'])): ?>
        <div class="muted">Criado em: <?php echo h($v['criado_em']); ?></div>
      <?php endif; ?>
    </div>

    <div class="row" style="margin-top:0;">
      <a class="btn btn-back" href="vendedores_pedidos.php">Voltar</a>
      <a class="btn btn-wa" href="<?php echo h($waLink); ?>" target="_blank">WhatsApp</a>
    </div>
  </div>

  <div class="grid">
    <div class="box">
      <strong>Carro</strong>
      <div class="muted">Marca: <?php echo h($v['marca']); ?></div>
      <div class="muted">Modelo: <?php echo h($v['modelo']); ?></div>
      <div class="muted">Ano: <?php echo h($v['ano']); ?></div>
      <div class="muted">Preço pretendido: <?php echo money($v['preco']); ?></div>
      <div class="muted">Status: <strong><?php echo h($v['status'] ?? 'Novo'); ?></strong></div>
    </div>

    <div class="box">
      <strong>Dono</strong>
      <div class="muted">Nome: <?php echo h($v['nome']); ?></div>
      <div class="muted">Telefone: <?php echo h($v['telefone']); ?></div>
      <div class="muted">Email: <?php echo h($v['email']); ?></div>
      <div class="muted">Status: <strong><?php echo h($v['status'] ?? 'Novo'); ?></strong></div>
    </div>
  </div>

  <?php if (!empty($v['mensagem'])): ?>
    <div class="note">
      <strong>Observações do dono</strong><br>
      <?php echo nl2br(h($v['mensagem'])); ?>
    </div>
  <?php endif; ?>

  <h3 style="margin:16px 0 10px;">Fotos (<?php echo count($fotos); ?>)</h3>

  <?php if (count($fotos) === 0): ?>
    <div class="muted">Nenhuma foto encontrada para este pedido.</div>
  <?php else: ?>
    <div class="gallery">
      <?php foreach($fotos as $f): ?>
        <a href="../<?php echo h($f['arquivo']); ?>" target="_blank" title="Abrir">
          <img src="../<?php echo h($f['arquivo']); ?>" alt="Foto do carro">
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

</body>
</html>
