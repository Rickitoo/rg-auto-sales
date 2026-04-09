<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include("conexao.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: vender_carro.html");
    exit;
}

// =====================
// Config Upload
// =====================
$MAX_FILES = 8;                  // máximo de fotos
$MAX_SIZE  = 3 * 1024 * 1024;    // 3MB por foto
$ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp'];

$UPLOAD_DIR = __DIR__ . "/uploads/vendas/";
$UPLOAD_URL = "uploads/vendas/"; // caminho relativo gravado no BD

if (!is_dir($UPLOAD_DIR)) {
    mkdir($UPLOAD_DIR, 0755, true);
}

// =====================
// Ler campos
// =====================
$nome     = trim($_POST['nome'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$email    = trim($_POST['email'] ?? '');
$marca    = trim($_POST['marca'] ?? '');
$modelo   = trim($_POST['modelo'] ?? '');
$ano      = intval($_POST['ano'] ?? 0);
$preco    = floatval($_POST['preco'] ?? 0);
$mensagem = trim($_POST['mensagem'] ?? '');

// validação mínima
if ($nome === '' || $telefone === '' || $marca === '' || $modelo === '' || $ano <= 0 || $preco <= 0) {
    die("Preencha os campos obrigatórios.");
}

// =====================
// Validar fotos
// =====================
if (!isset($_FILES['fotos']) || !is_array($_FILES['fotos']['name'])) {
    die("Envie as fotos do carro.");
}

$fotos = $_FILES['fotos'];

// Filtrar entradas vazias (às vezes o browser manda slots vazios)
$validIndexes = [];
for ($i = 0; $i < count($fotos['name']); $i++) {
    if (!empty($fotos['name'][$i]) && !empty($fotos['tmp_name'][$i])) {
        $validIndexes[] = $i;
    }
}

$total = count($validIndexes);

if ($total < 1) die("Envie pelo menos 1 foto.");
if ($total > $MAX_FILES) die("Máximo permitido: {$MAX_FILES} fotos.");

// =====================
// 1) Inserir pedido em vendedores
// =====================
$sql = "INSERT INTO vendedores (nome, telefone, email, marca, modelo, ano, preco, mensagem)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conexao, $sql);
if (!$stmt) {
    die("Erro ao preparar: " . mysqli_error($conexao));
}

mysqli_stmt_bind_param($stmt, "sssssids", $nome, $telefone, $email, $marca, $modelo, $ano, $preco, $mensagem);

if (!mysqli_stmt_execute($stmt)) {
    die("Erro ao gravar: " . mysqli_error($conexao));
}

$vendedor_id = mysqli_insert_id($conexao);
mysqli_stmt_close($stmt);

// =====================
// 2) Inserir fotos em vendedores_fotos
// =====================
$stmtFoto = mysqli_prepare($conexao, "INSERT INTO vendedores_fotos (vendedor_id, arquivo) VALUES (?, ?)");
if (!$stmtFoto) {
    die("Erro ao preparar fotos: " . mysqli_error($conexao));
}

$salvas = 0;
$salvasPaths = []; // para limpar se der ruim
$falhas = [];

$finfo = finfo_open(FILEINFO_MIME_TYPE);

foreach ($validIndexes as $i) {

    $err  = $fotos['error'][$i];
    $tmp  = $fotos['tmp_name'][$i];
    $size = $fotos['size'][$i];

    if ($err !== UPLOAD_ERR_OK) {
        $falhas[] = "Foto " . ($i+1) . ": erro de upload.";
        continue;
    }

    if ($size > $MAX_SIZE) {
        $falhas[] = "Foto " . ($i+1) . ": maior que 3MB.";
        continue;
    }

    $mime = finfo_file($finfo, $tmp);
    if (!in_array($mime, $ALLOWED_MIME, true)) {
        $falhas[] = "Foto " . ($i+1) . ": formato inválido (use JPG/PNG/WEBP).";
        continue;
    }

    $ext = 'jpg';
    if ($mime === 'image/png')  $ext = 'png';
    if ($mime === 'image/webp') $ext = 'webp';

    $safeName = "venda_" . $vendedor_id . "_" . bin2hex(random_bytes(8)) . "." . $ext;

    $destPath = $UPLOAD_DIR . $safeName;
    $destUrl  = $UPLOAD_URL . $safeName;

    if (!move_uploaded_file($tmp, $destPath)) {
        $falhas[] = "Foto " . ($i+1) . ": falha ao salvar no servidor.";
        continue;
    }

    mysqli_stmt_bind_param($stmtFoto, "is", $vendedor_id, $destUrl);
    if (!mysqli_stmt_execute($stmtFoto)) {
        // se falhar BD, apaga o arquivo
        @unlink($destPath);
        $falhas[] = "Foto " . ($i+1) . ": falha ao gravar no banco.";
        continue;
    }

    $salvas++;
    $salvasPaths[] = $destPath;
}

finfo_close($finfo);
mysqli_stmt_close($stmtFoto);

// =====================
// Garantir que pelo menos 1 foto foi salva
// =====================
if ($salvas < 1) {
    // apaga pedido (evita pedido órfão)
    mysqli_query($conexao, "DELETE FROM vendedores WHERE id=" . (int)$vendedor_id);

    // apaga arquivos que por acaso tenham sido salvos (normalmente 0 aqui)
    foreach ($salvasPaths as $p) {
        @unlink($p);
    }

    mysqli_close($conexao);
    die("Não foi possível salvar nenhuma foto. Use JPG/PNG/WEBP até 3MB e tente novamente.");
}

// =====================
// Sucesso
// =====================
mysqli_close($conexao);
$nome_url = urlencode($nome);
$marca_url = urlencode($marca);
$modelo_url = urlencode($modelo);
$para = "rgsolutions420@gmail.com";
$assunto = "Novo pedido de venda - $marca $modelo";

$mensagem_email = "
Novo pedido de venda:

Nome: $nome
Telefone: $telefone
Email: $email
Carro: $marca $modelo
Ano: $ano
Preço: $preco
Mensagem: $mensagem
";

$headers = "From: noreply@rgautosales.com";

@mail($para, $assunto, $mensagem_email, $headers);

header("Location: vender_sucesso.php?nome=$nome_url&marca=$marca_url&modelo=$modelo_url");
exit;
if (!empty($falhas)) {
    echo "<br><br><strong>Algumas fotos foram ignoradas:</strong><br>";
    echo implode("<br>", array_map('htmlspecialchars', $falhas));
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" type="image/png" href="ImagensRG/logo.png" />
  <title>Vender minha viatura | RG Auto Sales</title>
  <meta name="description" content="Venda a sua viatura com segurança e sem complicações. Envie os dados e fotos e receba proposta da RG Auto Sales." />
  <link rel="stylesheet" href="style.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<link rel="icon" type="image/png" href="ImagensRG/logo.png" />
  <title>RG Auto Sales | Encontre o seu carro</title>
  <meta name="description" content="RG Auto Sales — viaturas de qualidade, procedência garantida e test drive. Encontre o carro dos seus sonhos." />

  <link rel="stylesheet" href="style.css" />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap" rel="stylesheet" />

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
  
  <!-- Search box -->
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
          <a href="index.php">
            <img src="ImagensRG/logo.png" alt="RG Auto Sales" width="120">
          </a>
        </div>

        <nav>
          <ul id="MenuItems">
            <li><a href="index.php">Início</a></li>
            <li><a href="products.php">Carros</a></li>
            <li><a href="about.php">Sobre</a></li>
            <li><a href="contacto.php">Contacto</a></li>
            <li><a href="account.php">Conta</a></li>
            <li><a href="Test_drive.php">Test Drive</a></li>
            <li><a href="leasing.php">Leasing</a></li>
            <li><a href="vender_carro.php">Vender</a></li>
          </ul>
        </nav>

        <button class="menu-icon" type="button" onclick="menutoggle()" aria-label="Abrir menu">
          <i class="fa-solid fa-bars"></i>
        </button>
      </div>
      
  <div class="vender-page">
    <div class="vender-card">

      <div class="vender-top">
        <a class="vender-brand" href="index.html" aria-label="Voltar ao início">
          <img src="ImagensRG/logo.png" alt="RG Auto Sales" width="120">
        </a>

        <h1>Venda a sua viatura com segurança</h1>
        <p class="vender-sub">
          Preencha os dados abaixo e envie até 8 fotos. A RG analisa e entra em contacto com uma proposta.
        </p>

        <div class="vender-beneficios" role="list">
          <div class="vender-chip" role="listitem"><i class="fa-solid fa-circle-check"></i> Avaliação justa</div>
          <div class="vender-chip" role="listitem"><i class="fa-solid fa-circle-check"></i> Divulgação estratégica</div>
          <div class="vender-chip" role="listitem"><i class="fa-solid fa-circle-check"></i> Negociação segura</div>
          <div class="vender-chip" role="listitem"><i class="fa-solid fa-circle-check"></i> Mais rapidez na venda</div>
        </div>
      </div>

      <form class="vender-form" action="vender_carro.php" method="POST" enctype="multipart/form-data">

        <div class="vender-grid">
          <div class="vender-field">
            <label for="nome">Seu nome *</label>
            <input id="nome" type="text" name="nome" placeholder="Ex: Rick Gani" required>
          </div>

          <div class="vender-field">
            <label for="telefone">Telefone/WhatsApp *</label>
            <input id="telefone" type="text" name="telefone" placeholder="Ex: 84xxxxxxx" required inputmode="tel">
          </div>

          <div class="vender-field">
            <label for="email">Email (opcional)</label>
            <input id="email" type="email" name="email" placeholder="Ex: email@gmail.com">
          </div>

          <div class="vender-field">
            <label for="marca">Marca *</label>
            <input id="marca" type="text" name="marca" placeholder="Ex: Toyota" required>
          </div>

          <div class="vender-field">
            <label for="modelo">Modelo *</label>
            <input id="modelo" type="text" name="modelo" placeholder="Ex: Prado" required>
          </div>

          <div class="vender-field">
            <label for="ano">Ano *</label>
            <input id="ano" type="number" name="ano" placeholder="Ex: 2018" required min="1970" max="2035">
          </div>

          <div class="vender-field vender-span-2">
            <label for="preco">Preço pretendido (MT) *</label>
            <input id="preco" type="number" step="0.01" name="preco" placeholder="Ex: 1500000" required min="1">
            <small class="hint">Dica: coloca o valor aproximado, nós ajudamos a ajustar ao mercado.</small>
          </div>

          <div class="vender-field vender-span-2">
            <label for="fotos">Fotos da viatura (até 8) *</label>
            <input
              type="file"
              id="fotos"
              name="fotos[]"
              accept="image/jpeg,image/png,image/webp"
              multiple
              required
            >
            <small class="hint">Recomendado: frente, traseira, laterais, interior, painel e motor.</small>
          </div>

          <div class="vender-field vender-span-2">
            <label for="mensagem">Observações (opcional)</label>
            <textarea id="mensagem" name="mensagem" placeholder="Ex: automático, 4x4, histórico de manutenção, pequenos detalhes..." rows="4"></textarea>
          </div>
        </div>

        <button class="btn-vender" type="submit">
          <i class="fa-solid fa-paper-plane"></i>
          Enviar pedido de venda
        </button>

        <p class="vender-footer">
          Ao enviar, confirmas que as informações são verdadeiras. A RG Auto Sales entra em contacto pelo WhatsApp/telefone.
        </p>
      </form>

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
            <li><a href="products.php">Carros</a></li>
            <li><a href="Test_drive.php">Agendar Test Drive</a></li>
            <li><a href="vender_carro.php">Vender viatura</a></li>
            <li><a href="contacto.php">Contactos</a></li>
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

<script>
  const fotos = document.getElementById("fotos");
  fotos.addEventListener("change", () => {
    if (fotos.files.length > 8) {
      alert("Máximo de 8 fotos. Seleciona novamente.");
      fotos.value = "";
    }
  });
</script>

</body>
</html>