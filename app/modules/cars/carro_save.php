<?php
require_once __DIR__ . '/../../core/bootstrap.php';
require_admin();

// admin/carro_save.php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('admin/carros/adicionar_carro.php?msg=metodo_invalido');
}

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    http_response_code(403);
    exit('CSRF invalido.');
}



function clean($s){ return trim((string)$s); }

$marca = clean($_POST['marca'] ?? '');
$modelo = clean($_POST['modelo'] ?? '');
$ano = (int)($_POST['ano'] ?? 0);
$preco = (float)($_POST['preco'] ?? 0);
$status = clean($_POST['status'] ?? 'disponivel');
$descricao = clean($_POST['descricao'] ?? '');

if ($marca === '' || $modelo === '' || $ano <= 0 || $preco <= 0) {
  die("Preencha os campos obrigatórios.");
}

// ===== Config upload =====
$maxSize = 3 * 1024 * 1024; // 3MB por foto

$uploadDirAbs = __DIR__ . "/../uploads/carros/";

function saveImageFile(array $file, int $maxSize, string $uploadDirAbs, string $baseName): array {
  return secure_uploaded_image($file, $uploadDirAbs, 'uploads/carros', $maxSize, $baseName);
}

$baseName = "{$marca}-{$modelo}-{$ano}";

// ===== 1) Upload da CAPA (opcional) =====
$capaRel = null;
$uploadedAbsToCleanup = [];

if (isset($_FILES['imagem_capa']) && ($_FILES['imagem_capa']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
  [$ok, $info, $err] = saveImageFile($_FILES['imagem_capa'], $maxSize, $uploadDirAbs, $baseName . "-capa");
  if (!$ok) die("Capa: " . $err);
  $capaRel = $info['rel'];
  $uploadedAbsToCleanup[] = $info['abs'];
}

// ===== 2) Inserir carro primeiro (para ter ID) =====
mysqli_begin_transaction($conexao);

try {
  $stmt = mysqli_prepare($conexao, "
    INSERT INTO carros (marca, modelo, ano, preco, descricao, status, imagem, data_registo)
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
  ");
  // imagem = capa (pode ser null)
  mysqli_stmt_bind_param($stmt, "ssidsss", $marca, $modelo, $ano, $preco, $descricao, $status, $capaRel);
  if (!mysqli_stmt_execute($stmt)) throw new Exception("Erro ao inserir carro: " . mysqli_error($conexao));

  $carroId = mysqli_insert_id($conexao);

  // ===== 3) Upload GALERIA (múltiplas) =====
  if (!isset($_FILES['galeria'])) throw new Exception("Galeria não enviada.");

  $gal = $_FILES['galeria'];
  $count = is_array($gal['name'] ?? null) ? count($gal['name']) : 0;
  if ($count <= 0) throw new Exception("Seleciona pelo menos 1 foto na galeria.");

  // limite opcional (evitar abuso)
  if ($count > 15) throw new Exception("Máximo 15 fotos por carro.");

  $stmtFoto = mysqli_prepare($conexao, "
    INSERT INTO carros_fotos (carro_id, caminho, criado_em)
    VALUES (?, ?, NOW())
  ");

  $firstGalleryRel = null;

  for ($i=0; $i<$count; $i++){
    if (($gal['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;

    $file = [
      'name' => $gal['name'][$i] ?? '',
      'type' => $gal['type'][$i] ?? '',
      'tmp_name' => $gal['tmp_name'][$i] ?? '',
      'error' => $gal['error'][$i] ?? UPLOAD_ERR_NO_FILE,
      'size' => $gal['size'][$i] ?? 0,
    ];

    [$ok, $info, $err] = saveImageFile($file, $maxSize, $uploadDirAbs, $baseName . "-g" . ($i+1));
    if (!$ok) throw new Exception("Galeria: " . $err);

    $uploadedAbsToCleanup[] = $info['abs'];
    $rel = $info['rel'];
    if ($firstGalleryRel === null) $firstGalleryRel = $rel;

    mysqli_stmt_bind_param($stmtFoto, "is", $carroId, $rel);
    if (!mysqli_stmt_execute($stmtFoto)) throw new Exception("Erro ao inserir foto: " . mysqli_error($conexao));
  }

  // ===== 4) Se não tiver capa, usa 1ª foto da galeria como capa =====
  if ($capaRel === null && $firstGalleryRel !== null) {
    $stmtUp = mysqli_prepare($conexao, "UPDATE carros SET imagem = ? WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtUp, "si", $firstGalleryRel, $carroId);
    if (!mysqli_stmt_execute($stmtUp)) throw new Exception("Erro ao definir capa: " . mysqli_error($conexao));
  }

  mysqli_commit($conexao);
  redirect_to('public/products.php');

} catch (Exception $e) {
  mysqli_rollback($conexao);
  // cleanup uploads que já foram enviados
  foreach ($uploadedAbsToCleanup as $p) { @unlink($p); }
  die("Falhou: " . $e->getMessage());
}
