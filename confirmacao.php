<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include("conexao.php");

$RG_WA = "258862934721"; // WhatsApp da RG

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    $erro = "Não foi possível carregar os detalhes (ID inválido).";
} else {
    $stmt = mysqli_prepare($conexao, "
        SELECT nome, telefone, email, sexo, data, hora, marca, modelo, ano, mensagem, data_registo
        FROM clientes
        WHERE id = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $dados = mysqli_fetch_assoc($res);

    if (!$dados) {
        $erro = "Agendamento não encontrado.";
    } else {
        // ✅ Mensagem WhatsApp automática
        $msg  = "Olá RG Auto Sales, sou {$dados['nome']}. Quero confirmar o meu test drive:\n";
        $msg .= "Data: {$dados['data']} às {$dados['hora']}\n";
        $msg .= "Carro: {$dados['marca']} {$dados['modelo']} {$dados['ano']}\n";
        $msg .= "Telefone: {$dados['telefone']}\n";
        if (!empty($dados['mensagem'])) {
            $msg .= "Obs: {$dados['mensagem']}\n";
        }
        $msg .= "Obrigado!";

        $wa_link = "https://wa.me/" . $RG_WA . "?text=" . urlencode($msg);
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Confirmação | RG Auto Sales</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body{ font-family: Arial, sans-serif; background:#f2f2f2; margin:0; padding:20px; }
    .wrap{ max-width:760px; margin:0 auto; }
    .brand{ font-weight:900; font-size:24px; letter-spacing:.5px; margin-bottom:12px; }
    .brand span{ color:#0a7cff; }
    .card{ background:#fff; padding:18px; border-radius:12px; box-shadow:0 8px 22px rgba(0,0,0,.08); }
    .ok{ display:flex; align-items:center; gap:10px; font-size:20px; font-weight:800; margin:0 0 12px; }
    .badge{ background:#12b76a; color:#fff; padding:6px 10px; border-radius:999px; font-size:13px; font-weight:700; }
    .grid{ display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-top:12px; }
    .item{ background:#f7f7f7; border:1px solid #e6e6e6; padding:10px; border-radius:10px; font-size:14px; }
    .item b{ display:block; margin-bottom:4px; }
    .actions{ margin-top:14px; display:flex; gap:10px; flex-wrap:wrap; }
    .btn{ display:inline-block; padding:10px 14px; border-radius:10px; text-decoration:none; font-weight:700; }
    .btn-primary{ background:#000; color:#fff; }
    .btn-outline{ border:1px solid #000; color:#000; background:#fff; }
    .footer{ margin-top:12px; color:#666; font-size:13px; }
  </style>
</head>
<body>
<div class="wrap">
  <div class="brand">RG <span>Auto Sales</span></div>

  <div class="card">
    <?php if (!empty($erro)) { ?>
      <p class="ok">Agendamento confirmado <span class="badge">✅</span></p>
      <p style="color:#b42318; font-weight:700;"><?php echo htmlspecialchars($erro); ?></p>
    <?php } else { ?>
      <p class="ok">Agendamento confirmado <span class="badge">✅</span></p>
      <p>Olá, <b><?php echo htmlspecialchars($dados['nome']); ?></b>! O teu pedido de test drive foi registado com sucesso.</p>

      <div class="grid">
        <div class="item"><b>Data</b><?php echo htmlspecialchars($dados['data']); ?></div>
        <div class="item"><b>Hora</b><?php echo htmlspecialchars($dados['hora']); ?></div>

        <div class="item"><b>Marca</b><?php echo htmlspecialchars($dados['marca']); ?></div>
        <div class="item"><b>Modelo</b><?php echo htmlspecialchars($dados['modelo']); ?></div>

        <div class="item"><b>Ano</b><?php echo htmlspecialchars($dados['ano']); ?></div>
        <div class="item"><b>Telefone</b><?php echo htmlspecialchars($dados['telefone']); ?></div>

        <div class="item"><b>Email</b><?php echo htmlspecialchars($dados['email']); ?></div>
        <div class="item"><b>Sexo</b><?php echo htmlspecialchars($dados['sexo']); ?></div>

        <div class="item" style="grid-column:1/-1;">
          <b>Observações</b>
          <?php echo $dados['mensagem'] ? nl2br(htmlspecialchars($dados['mensagem'])) : "—"; ?>
        </div>

        <div class="item" style="grid-column:1/-1;">
          <b>Registado em</b>
          <?php echo htmlspecialchars($dados['data_registo']); ?>
        </div>
      </div>
    <?php } ?>

    <div class="actions">
      <?php if (empty($erro)) { ?>
        <a class="btn btn-primary" href="<?php echo $wa_link; ?>" target="_blank" rel="noopener">
          Confirmar no WhatsApp
        </a>
      <?php } ?>

      <a class="btn btn-outline" href="index.html">Voltar ao início</a>
      <a class="btn btn-outline" href="test_drive.html">Novo agendamento</a>
      <a class="btn btn-outline" href="admin.php">Admin</a>
    </div>

    <div class="footer">
      RG Auto Sales • Maputo • Atendimento via WhatsApp disponível.
    </div>
  </div>
</div>

<div class="footer">
    <div class="container">
      <div class="row">

        <div class="footer-col-1">
          <h3>Download do App</h3>
          <p>Disponível para Android e iOS.</p>
          <div class="app-logo">
            <img src="ImagensRG/AppStore.png" alt="App Store" />
            <img src="ImagensRG/pngtree-google-play-store-vector-png-image_9183318.png" alt="Google Play" />
          </div>
        </div>

        <div class="footer-col-2">
          <img src="ImagensRG/logo.png" alt="RG Auto Sales" />
          <p>Nosso objetivo é tornar acessível o prazer de dirigir veículos de qualidade, com transparência e confiança.</p>
        </div>

        <div class="footer-col-1">
          <h3>Links úteis</h3>
          <ul>
            <li><a href="products.html">Carros</a></li>
            <li><a href="Test_drive.html">Agendar Test Drive</a></li>
            <li><a href="vender_carro.html">Vender viatura</a></li>
            <li><a href="contacto.html">Contactos</a></li>
          </ul>
        </div>

        <div class="footer-col-4">
          <h3>Siga a RG</h3>
          <ul>
            <li><a href="https://www.facebook.com/profile.php?id=61588204178280&locale=pt_BR">Facebook</a></li>
            <li><a href="https://www.instagram.com/rgauto_sales/">Instagram</a></li>
            <li><a href="#">TikTok</a></li>
            <li><a href="#">YouTube</a></li>
          </ul>
        </div>

      </div>

      <hr />
      <p class="copyright">Copyright 2026 - RG SALES</p>
    </div>
  </div>


<?php if (empty($erro)) { ?>
<script>
  // tenta abrir WhatsApp automaticamente (pode ser bloqueado pelo browser)
  setTimeout(() => {
    window.open("<?php echo $wa_link; ?>", "_blank");
  }, 1000);
</script>
<?php } ?>

</body>
</html>
