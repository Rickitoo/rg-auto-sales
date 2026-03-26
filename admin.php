<?php
include("auth.php"); // ✅ protege o admin

ini_set('display_errors', 1);
error_reporting(E_ALL);

include("conexao.php");


// CARROS
$sqlC1 = "SELECT * FROM carros WHERE status='disponivel' ORDER BY data_registo DESC";
$resCarrosDisp = mysqli_query($conexao, $sqlC1);
if (!$resCarrosDisp) {
    die("Erro na consulta carros disponíveis: " . mysqli_error($conexao));
}

$sqlC2 = "SELECT * FROM carros WHERE status='vendido' ORDER BY data_venda DESC, data_registo DESC";
$resCarrosVend = mysqli_query($conexao, $sqlC2);
if (!$resCarrosVend) {
    die("Erro na consulta carros vendidos: " . mysqli_error($conexao));
}


// Garante CSRF token (caso auth.php não gere)
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$sql = "SELECT * FROM clientes ORDER BY data_registo DESC";
$resultado = mysqli_query($conexao, $sql);

$sqlV = "SELECT * FROM vendedores ORDER BY data_registo DESC";
$resultadoV = mysqli_query($conexao, $sqlV);

if (!$resultado) {
    die("Erro na consulta clientes: " . mysqli_error($conexao));
}
if (!$resultadoV) {
    die("Erro na consulta vendedores: " . mysqli_error($conexao));
}

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Admin | RG Auto Sales</title>
   <link rel="icon" type="image/png" href="ImagensRG/logo.png" />
  <title>RG Auto Sales | Encontre o seu carro</title>
  <meta name="description" content="RG Auto Sales — viaturas de qualidade, procedência garantida e test drive. Encontre o carro dos seus sonhos." />

  <link rel="stylesheet" href="style.css" />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap" rel="stylesheet" />

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    body{ font-family: Arial, sans-serif; background:#f2f2f2; margin:0; padding:20px; }
    .top{ display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; gap:10px; flex-wrap:wrap;}
    .brand{ font-weight:900; font-size:20px; letter-spacing:.5px; }
    .brand span{ color:#0a7cff; }
    .btn{ display:inline-block; padding:9px 12px; border-radius:10px; text-decoration:none; font-weight:800; }
    .btn-outline{ border:1px solid #000; color:#000; background:#fff; }
    .btn-dark{ background:#000; color:#fff; }
    .card{ background:#fff; padding:16px; border-radius:10px; box-shadow:0 6px 18px rgba(0,0,0,.08); }
    table{ width:100%; border-collapse:collapse; background:#fff; }
    th,td{ padding:10px; border:1px solid #ddd; text-align:center; font-size:13px; }
    th{ background:#000; color:#fff; }
    .msg{ text-align:left; max-width:320px; }
    .toast{ background:#12b76a; color:#fff; padding:10px 12px; border-radius:10px; font-weight:800; margin-bottom:12px; }
    .toast-warn{ background:#f79009; color:#111; padding:10px 12px; border-radius:10px; font-weight:800; margin-bottom:12px; }
    .danger{ background:#b42318; color:#fff; padding:7px 10px; border-radius:10px; text-decoration:none; font-weight:900; }
  </style>
</head>

<body>

  <!-- Search box (AGORA NO BODY, correto) -->
  <div class="search-box">
    <input class="search-txt" type="text" placeholder="Pesquise aqui" aria-label="Pesquisar" />
    <a class="search-btn" href="#" aria-label="Botão pesquisar">
      <i class="fas fa-search"></i>
    </a>
  </div>

  <header class="header header--rg">
  <div class="header__overlay">
    <div class="container">

      <!-- NAVBAR -->
      <div class="navbar">
        <div class="logo">
          <a href="index.html">
            <img src="ImagensRG/logo.png" alt="RG Auto Sales" width="120">
          </a>
        </div>

        <nav>
          <ul id="MenuItems">
            <li><a href="index.html">Início</a></li>
            <li><a href="products.html">Carros</a></li>
            <li><a href="about.html">Sobre</a></li>
            <li><a href="contacto.html">Contacto</a></li>
            <li><a href="account.html">Conta</a></li>
            <li><a href="Test_drive.html">Test Drive</a></li>
            <li><a href="leasing.html">Leasing</a></li>
            <li><a href="vender_carro.html">Vender</a></li>
          </ul>
        </nav>

        <button class="menu-icon" type="button" onclick="menutoggle()" aria-label="Abrir menu">
          <i class="fa-solid fa-bars"></i>
        </button>
      </div>

<div class="top">
  <div class="brand">RG <span>Auto Sales</span> • Admin</div>
  <div style="display:flex; gap:10px;">
    <a class="btn btn-outline" href="index.html">Site</a>
    <a class="btn btn-dark" href="logout.php">Sair</a>
  </div>
</div>

<?php if ($msg === "apagado") { ?>
  <div class="toast">✅ Agendamento apagado com sucesso.</div>
<?php } ?>

<?php if ($msg === "nao_encontrado") { ?>
  <div class="toast-warn">⚠️ Esse agendamento já não existe (ou já foi apagado).</div>
<?php } ?>

<?php if ($msg === "status_ok") { ?>
  <div class="toast">✅ Status atualizado com sucesso.</div>
<?php } ?>

<div class="card">
  <h2 style="margin-top:0;">Agendamentos de Test Drive</h2>

  <table>
    <tr>
      <th>ID</th>
      <th>Nome</th>
      <th>Telefone</th>
      <th>Data</th>
      <th>Hora</th>
      <th>Marca</th>
      <th>Modelo</th>
      <th>Ano</th>
      <th>Mensagem</th>
      <th>Registo</th>
      <th>Ações</th>
    </tr>

    <?php if (mysqli_num_rows($resultado) == 0) { ?>
      <tr><td colspan="11">Nenhum agendamento encontrado.</td></tr>
    <?php } ?>

    <?php while($linha = mysqli_fetch_assoc($resultado)) { ?>
      <tr>
        <td><?php echo (int)$linha['id']; ?></td>
        <td><?php echo htmlspecialchars($linha['nome']); ?></td>
        <td><?php echo htmlspecialchars($linha['telefone']); ?></td>
        <td><?php echo htmlspecialchars($linha['data']); ?></td>
        <td><?php echo htmlspecialchars($linha['hora'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($linha['marca'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($linha['modelo'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($linha['ano'] ?? ''); ?></td>
        <td class="msg"><?php echo nl2br(htmlspecialchars($linha['mensagem'] ?? '')); ?></td>
        <td><?php echo htmlspecialchars($linha['data_registo']); ?></td>
        <td>
          <a class="danger"
             href="delete.php?id=<?php echo (int)$linha['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>"
             onclick="return confirm('Tens certeza que queres apagar este agendamento?');">
             Apagar
          </a>
        </td>
      </tr>
    <?php } ?>
  </table>
</div>

<div class="card" style="margin-top:20px;">
  <h2 style="margin-top:0;">Pedidos para Vender Carro</h2>

  <table>
    <tr>
      <th>ID</th>
      <th>Nome</th>
      <th>Telefone</th>
      <th>Email</th>
      <th>Marca</th>
      <th>Modelo</th>
      <th>Ano</th>
      <th>Preço</th>
      <th>Mensagem</th>
      <th>Status</th>
      <th>Registo</th>
      <th>Ações</th>
    </tr>

    <?php if (mysqli_num_rows($resultadoV) == 0) { ?>
      <tr><td colspan="12">Nenhum pedido encontrado.</td></tr>
    <?php } ?>

    <?php while($v = mysqli_fetch_assoc($resultadoV)) { ?>
      <tr>
        <td><?php echo (int)$v['id']; ?></td>
        <td><?php echo htmlspecialchars($v['nome']); ?></td>
        <td><?php echo htmlspecialchars($v['telefone']); ?></td>
        <td><?php echo htmlspecialchars($v['email'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($v['marca']); ?></td>
        <td><?php echo htmlspecialchars($v['modelo']); ?></td>
        <td><?php echo htmlspecialchars($v['ano']); ?></td>
        <td><?php echo number_format((float)$v['preco'], 2, '.', ' '); ?></td>
        <td class="msg"><?php echo nl2br(htmlspecialchars($v['mensagem'] ?? '')); ?></td>
        <td><?php echo htmlspecialchars($v['status']); ?></td>
        <td><?php echo htmlspecialchars($v['data_registo']); ?></td>

        <td>
          <a class="btn btn-outline"
             href="update_status.php?id=<?php echo (int)$v['id']; ?>&status=aprovado&token=<?php echo $_SESSION['csrf_token']; ?>"
             onclick="return confirm('Aprovar este pedido?');">
             Aprovar
          </a>

          <a class="danger"
             href="update_status.php?id=<?php echo (int)$v['id']; ?>&status=rejeitado&token=<?php echo $_SESSION['csrf_token']; ?>"
             onclick="return confirm('Rejeitar este pedido?');">
             Rejeitar
          </a>
        </td>
      </tr>
    <?php } ?>
  </table>
</div>

<div class="card" style="margin-top:20px;">
  <h2 style="margin-top:0;">Carros Disponíveis</h2>

  <table>
    <tr>
      <th>ID</th>
      <th>Imagem</th>
      <th>Marca</th>
      <th>Modelo</th>
      <th>Ano</th>
      <th>Preço</th>
      <th>Registo</th>
      <th>Marcar como vendido</th>
    </tr>

    <?php if (mysqli_num_rows($resCarrosDisp) == 0) { ?>
      <tr><td colspan="8">Nenhum carro disponível.</td></tr>
    <?php } ?>

    <?php while($c = mysqli_fetch_assoc($resCarrosDisp)) { ?>
      <tr>
        <td><?php echo (int)$c['id']; ?></td>

        <td>
          <?php if (!empty($c['imagem'])) { ?>
            <img src="<?php echo htmlspecialchars($c['imagem']); ?>" style="width:80px; border-radius:8px;">
          <?php } else { ?>
            —
          <?php } ?>
        </td>

        <td><?php echo htmlspecialchars($c['marca']); ?></td>
        <td><?php echo htmlspecialchars($c['modelo']); ?></td>
        <td><?php echo htmlspecialchars($c['ano']); ?></td>
        <td><?php echo number_format((float)$c['preco'], 2, '.', ' '); ?> MT</td>
        <td><?php echo htmlspecialchars($c['data_registo']); ?></td>

        <td style="text-align:left;">
          <form action="marcar_vendido.php" method="POST" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
            <input type="hidden" name="token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <input type="number" step="0.01" name="preco_venda" placeholder="Preço venda (MT)" required style="max-width:160px;">
            <input type="number" step="0.01" name="comissao" placeholder="Comissão (MT)" style="max-width:140px;">

            <button class="btn btn-outline" type="submit"
              onclick="return confirm('Confirmar marcar como VENDIDO?');">
              Vendido
            </button>
          </form>
          <small style="color:#666;">
            Dica: se não souber a comissão, pode deixar em branco e depois editamos.
          </small>
        </td>
      </tr>
    <?php } ?>
  </table>
</div>


<div class="card" style="margin-top:20px;">
  <h2 style="margin-top:0;">Carros Vendidos (Histórico)</h2>

  <table>
    <tr>
      <th>ID</th>
      <th>Marca</th>
      <th>Modelo</th>
      <th>Ano</th>
      <th>Preço Anunciado</th>
      <th>Preço Venda</th>
      <th>Comissão</th>
      <th>Data Venda</th>
    </tr>

    <?php if (mysqli_num_rows($resCarrosVend) == 0) { ?>
      <tr><td colspan="8">Nenhum carro vendido ainda.</td></tr>
    <?php } ?>

    <?php while($c = mysqli_fetch_assoc($resCarrosVend)) { ?>
      <tr>
        <td><?php echo (int)$c['id']; ?></td>
        <td><?php echo htmlspecialchars($c['marca']); ?></td>
        <td><?php echo htmlspecialchars($c['modelo']); ?></td>
        <td><?php echo htmlspecialchars($c['ano']); ?></td>

        <td><?php echo number_format((float)$c['preco'], 2, '.', ' '); ?> MT</td>
        <td><?php echo $c['preco_venda'] !== null ? number_format((float)$c['preco_venda'], 2, '.', ' ') . " MT" : "—"; ?></td>
        <td><?php echo $c['comissao'] !== null ? number_format((float)$c['comissao'], 2, '.', ' ') . " MT" : "—"; ?></td>
        <td><?php echo htmlspecialchars($c['data_venda'] ?? '—'); ?></td>
      </tr>
    <?php } ?>
  </table>
</div>

</body>
</html>
