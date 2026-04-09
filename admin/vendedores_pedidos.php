<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include("../auth.php"); // remove se não tiveres
include("../conexao.php");
include("auth_check.php");

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($v){ return number_format((float)$v, 2, ',', '.') . " MT"; }

$sql = "
  SELECT
    v.id, v.nome, v.telefone, v.email, v.marca, v.modelo, v.ano, v.preco, v.mensagem, v.criado_em,
    v.status,
    COUNT(f.id) AS total_fotos
  FROM vendedores v
  LEFT JOIN vendedores_fotos f ON f.vendedor_id = v.id
  GROUP BY v.id
  ORDER BY v.id DESC
";

$res = mysqli_query($conexao, $sql);
if (!$res) die("Erro ao buscar pedidos: " . mysqli_error($conexao));

$statuses = ['Novo','Em análise','Aprovado','Recusado','Publicado'];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin | Pedidos de Venda</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f6f7fb; margin: 0; padding: 20px; }
    h2 { margin: 0 0 15px; }
    .topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom: 15px; gap:10px; flex-wrap:wrap; }
    .card { background:#fff; border-radius:12px; padding:14px; box-shadow:0 6px 16px rgba(0,0,0,.07); margin-bottom: 12px; }
    .grid { display:grid; grid-template-columns: 1fr 1fr; gap: 8px 16px; }
    .muted { color:#666; font-size: 13px; }
    .row { display:flex; gap:10px; flex-wrap:wrap; margin-top: 10px; align-items:center; }
    .btn { display:inline-block; padding:10px 12px; border-radius:10px; text-decoration:none; font-weight:600; font-size: 14px; }
    .btn-view { background:#111; color:#fff; }
    .btn-wa { background:#25D366; color:#fff; }
    .btn-del { background:#e53935; color:#fff; border:none; cursor:pointer; }
    .pill { display:inline-block; padding:4px 10px; border-radius:999px; background:#eef2ff; font-size: 12px; }
    .empty { background:#fff; padding:18px; border-radius:12px; box-shadow:0 6px 16px rgba(0,0,0,.07); }
    .search { padding:10px 12px; border-radius:10px; border:1px solid #ddd; width:280px; }
    select { padding:9px 10px; border-radius:10px; border:1px solid #ddd; }
    .badge { padding:6px 10px; border-radius:999px; font-size:12px; font-weight:700; display:inline-block; }
    .b-novo{ background:#e3f2fd; }
    .b-analise{ background:#fff8e1; }
    .b-aprov{ background:#e8f5e9; }
    .b-recus{ background:#ffebee; }
    .b-pub{ background:#ede7f6; }

    @media (max-width: 700px){
      .grid { grid-template-columns: 1fr; }
      .search { width: 100%; }
      .topbar { flex-direction: column; align-items: stretch; }
    }
  </style>
</head>
<body>

<div class="topbar">
  <h2>Pedidos para Vender Carro</h2>
  <input class="search" id="search" placeholder="Pesquisar nome, marca, modelo, telefone..." />
</div>

<?php if (mysqli_num_rows($res) === 0): ?>
  <div class="empty">Ainda não há pedidos.</div>
<?php else: ?>
  <div id="list">
    <?php while($r = mysqli_fetch_assoc($res)):
      $id = (int)$r['id'];

      $tel = preg_replace('/\D+/', '', (string)$r['telefone']);
      $waBase = (strpos($tel, '258') === 0) ? ("https://wa.me/" . $tel) : ("https://wa.me/258" . $tel);

      $msg = rawurlencode("Olá " . $r['nome'] . ", aqui é a RG Auto Sales. Recebemos o seu pedido para vender o " . $r['marca'] . " " . $r['modelo'] . " (" . $r['ano'] . "). Vamos avançar?");
      $waLink = $waBase . "?text=" . $msg;

      $st = $r['status'] ?? 'Novo';
      $badgeClass = 'b-novo';
      if ($st === 'Em análise') $badgeClass = 'b-analise';
      if ($st === 'Aprovado') $badgeClass = 'b-aprov';
      if ($st === 'Recusado') $badgeClass = 'b-recus';
      if ($st === 'Publicado') $badgeClass = 'b-pub';
    ?>
      <div class="card item"
           data-text="<?php echo h(strtolower($r['nome'].' '.$r['telefone'].' '.$r['marca'].' '.$r['modelo'].' '.$r['ano'].' '.$r['email'])); ?>">

        <div class="grid">
          <div>
            <div><strong>#<?php echo $id; ?> — <?php echo h($r['nome']); ?></strong></div>
            <div class="muted">
              Tel: <?php echo h($r['telefone']); ?> · Email: <?php echo h($r['email']); ?>
            </div>
            <div class="muted">Data: <?php echo h($r['criado_em']); ?></div>
          </div>

          <div>
            <div><strong><?php echo h($r['marca']); ?> <?php echo h($r['modelo']); ?></strong></div>
            <div class="muted">Ano: <?php echo h($r['ano']); ?></div>
            <div class="muted">Preço: <?php echo money($r['preco']); ?></div>
            <div class="muted">
              <span class="pill"><?php echo (int)$r['total_fotos']; ?> fotos</span>
              &nbsp; <span class="badge <?php echo h($badgeClass); ?>"><?php echo h($st); ?></span>
            </div>
          </div>
        </div>

        <?php if (!empty($r['mensagem'])): ?>
          <div style="margin-top:10px" class="muted">
            <strong>Obs:</strong> <?php echo h($r['mensagem']); ?>
          </div>
        <?php endif; ?>

        <div class="row">
          <a class="btn btn-view" href="vendedor_ver.php?id=<?php echo $id; ?>">Ver detalhes</a>
          <a class="btn btn-wa" href="<?php echo h($waLink); ?>" target="_blank">WhatsApp</a>
          <?php if ($st === 'Aprovado'): ?>
            <a class="btn btn-view"
              href="vendedor_converter.php?id=<?php echo $id; ?>">
              Criar Venda
            </a>
          <?php endif; ?>

          <!-- Mudar status -->
          <form action="vendedor_status.php" method="POST" style="display:flex; gap:8px; align-items:center;">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <input type="hidden" name="token" value="<?php echo h($_SESSION['csrf_token']); ?>">

            <select name="status">
              <?php foreach($statuses as $opt): ?>
                <option value="<?php echo h($opt); ?>" <?php echo ($opt === $st ? 'selected' : ''); ?>>
                  <?php echo h($opt); ?>
                </option>
              <?php endforeach; ?>
            </select>

            <button class="btn btn-view" type="submit">Salvar</button>
          </form>

          <!-- Apagar -->
          <form action="vendedor_apagar.php" method="POST" onsubmit="return confirm('Apagar este pedido?');" style="display:inline;">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <input type="hidden" name="token" value="<?php echo h($_SESSION['csrf_token']); ?>">
            <button class="btn btn-del" type="submit">Apagar</button>
          </form>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
<?php endif; ?>

<script>
  const search = document.getElementById('search');
  const items = document.querySelectorAll('.item');

  search?.addEventListener('input', () => {
    const q = search.value.trim().toLowerCase();
    items.forEach(el => {
      const t = el.getAttribute('data-text') || '';
      el.style.display = t.includes(q) ? '' : 'none';
    });
  });
</script>

</body>
</html>
