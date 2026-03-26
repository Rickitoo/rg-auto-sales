<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include("../auth.php");
include("../conexao.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function redirectSelf($id, $msg = '', $tipo = 'ok') {
    $url = "gerir_fotos.php?id=" . (int)$id;
    if ($msg !== '') {
        $url .= "&msg=" . urlencode($msg) . "&tipo=" . urlencode($tipo);
    }
    header("Location: $url");
    exit;
}

function atualizarImagemPrincipal($conexao, $carroId) {
    $carroId = (int)$carroId;

    $resPrincipal = mysqli_query($conexao, "SELECT imagem FROM carros WHERE id = $carroId LIMIT 1");
    $carro = $resPrincipal ? mysqli_fetch_assoc($resPrincipal) : null;
    $imagemAtual = $carro['imagem'] ?? '';

    if ($imagemAtual !== '') {
        $imgEsc = mysqli_real_escape_string($conexao, $imagemAtual);
        $resExiste = mysqli_query($conexao, "SELECT id FROM caminho WHERE carro_id = $carroId AND foto = '$imgEsc' LIMIT 1");
        if ($resExiste && mysqli_num_rows($resExiste) > 0) {
            return;
        }
    }

    $resPrimeira = mysqli_query($conexao, "
        SELECT foto
        FROM caminho
        WHERE carro_id = $carroId
        ORDER BY ordem ASC, id ASC
        LIMIT 1
    ");

    if ($resPrimeira && mysqli_num_rows($resPrimeira) > 0) {
        $primeira = mysqli_fetch_assoc($resPrimeira);
        $fotoPrincipal = mysqli_real_escape_string($conexao, $primeira['foto']);
        mysqli_query($conexao, "UPDATE carros SET imagem = '$fotoPrincipal' WHERE id = $carroId");
    } else {
        mysqli_query($conexao, "UPDATE carros SET imagem = NULL WHERE id = $carroId");
    }
}

function reordenarFotos($conexao, $carroId) {
    $carroId = (int)$carroId;

    $resFotos = mysqli_query($conexao, "
        SELECT id
        FROM caminho
        WHERE carro_id = $carroId
        ORDER BY ordem ASC, id ASC
    ");

    $novaOrdem = 1;
    if ($resFotos) {
        while ($foto = mysqli_fetch_assoc($resFotos)) {
            $fotoId = (int)$foto['id'];
            mysqli_query($conexao, "UPDATE caminho SET ordem = $novaOrdem WHERE id = $fotoId");
            $novaOrdem++;
        }
    }
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("ID inválido.");

$resCarro = mysqli_query($conexao, "SELECT * FROM carros WHERE id = $id LIMIT 1");
if (!$resCarro || mysqli_num_rows($resCarro) === 0) die("Carro não encontrado.");
$carro = mysqli_fetch_assoc($resCarro);

$uploadDir = realpath(__DIR__ . "/../uploads");
if ($uploadDir === false) die("Cria a pasta /uploads.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrf)) die("CSRF inválido.");

    if ($acao === 'upload') {

        $nomes = $_FILES['fotos']['name'] ?? [];
        $tmpNames = $_FILES['fotos']['tmp_name'] ?? [];
        $errors = $_FILES['fotos']['error'] ?? [];
        $sizes = $_FILES['fotos']['size'] ?? [];

        $permitidas = ['jpg','jpeg','png','webp'];

        $resMax = mysqli_query($conexao, "SELECT COALESCE(MAX(ordem),0) AS max_ordem FROM caminho WHERE carro_id=$id");
        $ordemAtual = (int)(mysqli_fetch_assoc($resMax)['max_ordem'] ?? 0);

        $enviadas = 0;

        for ($i=0;$i<count($nomes);$i++) {

            if (($errors[$i] ?? 0) !== UPLOAD_ERR_OK) continue;

            $tmp = $tmpNames[$i];
            $nomeOriginal = $nomes[$i];
            $size = (int)$sizes[$i];

            if ($size <= 0 || $size > 8*1024*1024) continue;

            $ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
            if (!in_array($ext,$permitidas)) continue;

            $mime = @mime_content_type($tmp);
            if (!in_array($mime,['image/jpeg','image/png','image/webp'])) continue;

            $novoNome = "carro_{$id}_" . time() . "_" . bin2hex(random_bytes(4)) . ".$ext";
            $destino = $uploadDir . "/" . $novoNome;

            if (move_uploaded_file($tmp,$destino)) {
                $ordemAtual++;
                mysqli_query($conexao,"INSERT INTO caminho (carro_id,foto,ordem) VALUES ($id,'$novoNome',$ordemAtual)");
                $enviadas++;
            }
        }

        reordenarFotos($conexao,$id);
        atualizarImagemPrincipal($conexao,$id);

        redirectSelf($id, $enviadas > 0 ? "Upload OK ($enviadas)" : "Nenhuma válida","ok");
    }

    if ($acao === 'apagar_foto') {
        $fotoId = (int)$_POST['foto_id'];

        $res = mysqli_query($conexao,"SELECT * FROM caminho WHERE id=$fotoId AND carro_id=$id");
        if ($res && $f=mysqli_fetch_assoc($res)) {

            $file = $uploadDir . "/" . $f['foto'];

            mysqli_query($conexao,"DELETE FROM caminho WHERE id=$fotoId");

            if (file_exists($file)) unlink($file);
        }

        reordenarFotos($conexao,$id);
        atualizarImagemPrincipal($conexao,$id);

        redirectSelf($id,"Apagada");
    }

    if ($acao === 'definir_principal') {
        $fotoId = (int)$_POST['foto_id'];

        $res = mysqli_query($conexao,"SELECT * FROM caminho WHERE id=$fotoId AND carro_id=$id");
        if ($res && $f=mysqli_fetch_assoc($res)) {
            mysqli_query($conexao,"UPDATE carros SET imagem='{$f['foto']}' WHERE id=$id");
        }

        redirectSelf($id,"Principal definida");
    }
}

/* VARIÁVEIS PARA O HTML */
$fotos = mysqli_query($conexao,"SELECT * FROM caminho WHERE carro_id=$id ORDER BY ordem ASC");

$resTotal = mysqli_query($conexao,"SELECT COUNT(*) total FROM caminho WHERE carro_id=$id");
$totalFotos = (int)(mysqli_fetch_assoc($resTotal)['total'] ?? 0);

$resImg = mysqli_query($conexao,"SELECT imagem FROM carros WHERE id=$id");
$fotoPrincipalAtual = mysqli_fetch_assoc($resImg)['imagem'] ?? '';

$srcPrincipal = $fotoPrincipalAtual ? "../uploads/".$fotoPrincipalAtual : '';
?>