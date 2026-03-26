<?php
// admin/carro_save.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../conexao.php";

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
$allowed = [
  'image/jpeg' => 'jpg',
  'image/png'  => 'png',
  'image/webp' => 'webp',
];
$maxSize = 3 * 1024 * 1024; // 3MB por foto

$uploadDirAbs = __DIR__ . "/../uploads/carros/";
if (!is_dir($uploadDirAbs)) {
  if (!mkdir($uploadDirAbs, 0755, true)) die("Não foi possível criar uploads/carros.");
}

function saveImageFile(array $file, array $allowed, int $maxSize, string $uploadDirAbs, string $baseName): array {
  if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    return [false, null, "Falha no upload."];
  }

  $tmp = $file['tmp_name'] ?? '';
  $size = (int)($file['size'] ?? 0);
  if ($size <= 0 || $size > $maxSize) return [false, null, "Imagem inválida ou muito grande (máx 3MB)."];

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime = $finfo->file($tmp);
  if (!isset($allowed[$mime])) return [false, null, "Formato não permitido. Usa JPG/PNG/WEBP."];

  $ext = $allowed[$mime];
  $safeBase = preg_replace('~[^a-z0-9]+~i', '-', strtolower($baseName));
  $safeBase = trim($safeBase, '-');

  $filename = $safeBase . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
  $destAbs = $uploadDirAbs . $filename;
  $destRel = "uploads/carros/" . $filename;

  if (!move_uploaded_file($tmp, $destAbs)) return [false, null, "Não foi possível salvar a imagem."];

  return [true, ['abs'=>$destAbs, 'rel'=>$destRel], null];
}

$baseName = "{$marca}-{$modelo}-{$ano}";

// ===== 1) Upload da CAPA (opcional) =====
$capaRel = null;
$uploadedAbsToCleanup = [];

if (isset($_FILES['imagem_capa']) && ($_FILES['imagem_capa']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
  [$ok, $info, $err] = saveImageFile($_FILES['imagem_capa'], $allowed, $maxSize, $uploadDirAbs, $baseName . "-capa");
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

    [$ok, $info, $err] = saveImageFile($file, $allowed, $maxSize, $uploadDirAbs, $baseName . "-g" . ($i+1));
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
  header("Location: ../products.php");
  exit;

} catch (Exception $e) {
  mysqli_rollback($conexao);
  // cleanup uploads que já foram enviados
  foreach ($uploadedAbsToCleanup as $p) { @unlink($p); }
  die("Falhou: " . $e->getMessage());
}