<?php
// admin/salvar_ordem_fotos.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include("../auth.php");
include("../conexao.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

function jsonOut($ok, $msg = '', $extra = []) {
    echo json_encode(array_merge([
        'ok' => $ok,
        'msg' => $msg
    ], $extra));
    exit;
}

function atualizarImagemPrincipal($conexao, $carroId) {
    $carroId = (int)$carroId;

    $resPrincipal = mysqli_query($conexao, "SELECT imagem FROM carros WHERE id = $carroId LIMIT 1");
    $carro = $resPrincipal ? mysqli_fetch_assoc($resPrincipal) : null;
    $imagemAtual = $carro['imagem'] ?? '';

    if ($imagemAtual !== '') {
        $imgEsc = mysqli_real_escape_string($conexao, $imagemAtual);
        $resExiste = mysqli_query($conexao, "SELECT id FROM carros_fotos WHERE carro_id = $carroId AND foto = '$imgEsc' LIMIT 1");
        if ($resExiste && mysqli_num_rows($resExiste) > 0) {
            return;
        }
    }

    $resPrimeira = mysqli_query($conexao, "
        SELECT foto
        FROM carros_fotos
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(false, 'Método inválido.');
}

$csrf = $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    jsonOut(false, 'CSRF inválido.');
}

$carroId = (int)($_POST['carro_id'] ?? 0);
$ordemJson = $_POST['ordem'] ?? '';

if ($carroId <= 0) {
    jsonOut(false, 'Carro inválido.');
}

$resCarro = mysqli_query($conexao, "SELECT id FROM carros WHERE id = $carroId LIMIT 1");
if (!$resCarro || mysqli_num_rows($resCarro) === 0) {
    jsonOut(false, 'Carro não encontrado.');
}

$ordem = json_decode($ordemJson, true);
if (!is_array($ordem) || count($ordem) === 0) {
    jsonOut(false, 'Ordem inválida.');
}

// Buscar fotos reais desse carro
$resFotos = mysqli_query($conexao, "SELECT id FROM carros_fotos WHERE carro_id = $carroId");
$fotosValidas = [];
if ($resFotos) {
    while ($row = mysqli_fetch_assoc($resFotos)) {
        $fotosValidas[] = (int)$row['id'];
    }
}

sort($fotosValidas);

$ordemRecebida = array_map('intval', $ordem);
sort($ordemRecebida);

// Garante que a lista recebida bate com as fotos reais do carro
if ($fotosValidas !== $ordemRecebida) {
    jsonOut(false, 'A lista de fotos não corresponde ao carro.');
}

mysqli_begin_transaction($conexao);

try {
    $pos = 1;
    foreach ($ordem as $fotoId) {
        $fotoId = (int)$fotoId;
        mysqli_query($conexao, "UPDATE carros_fotos SET ordem = $pos WHERE id = $fotoId AND carro_id = $carroId");
        $pos++;
    }

    atualizarImagemPrincipal($conexao, $carroId);

    mysqli_commit($conexao);
    jsonOut(true, 'Ordem guardada com sucesso.');
} catch (Throwable $e) {
    mysqli_rollback($conexao);
    jsonOut(false, 'Erro ao guardar ordem.');
} 