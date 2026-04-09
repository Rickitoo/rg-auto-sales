<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include("auth_check.php");
include("admin/includes/db.php");

require_once __DIR__ . "/../conexao.php";

header('Content-Type: application/json');

function caminhoFisicoFoto(string $caminho): string {
    $caminho = trim($caminho);

    if ($caminho === '') {
        return '';
    }

    // se já vier como uploads/carros/ficheiro.jpg
    if (str_starts_with($caminho, 'uploads/')) {
        return __DIR__ . '/../' . $caminho;
    }

    // se vier só nome do ficheiro
    return __DIR__ . '/../uploads/carros/' . ltrim($caminho, '/');
}

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$id = (int)($data['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'ID inválido.']);
    exit;
}

// buscar foto
$stmt = mysqli_prepare($conexao, "SELECT id, caminho, carro_id FROM carros_fotos WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($stmt);

if (!$row) {
    echo json_encode(['ok' => false, 'error' => 'Foto não encontrada.']);
    exit;
}

$caminho = (string)$row['caminho'];
$carro_id = (int)$row['carro_id'];
$abs = caminhoFisicoFoto($caminho);

mysqli_begin_transaction($conexao);

try {
    // apagar da BD
    $stmtD = mysqli_prepare($conexao, "DELETE FROM carros_fotos WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtD, "i", $id);
    if (!mysqli_stmt_execute($stmtD)) {
        throw new Exception("Erro ao apagar foto da base.");
    }
    mysqli_stmt_close($stmtD);

    // buscar nova capa
    $stmtFirst = mysqli_prepare($conexao, "
        SELECT caminho
        FROM carros_fotos
        WHERE carro_id = ?
        ORDER BY ordem ASC, id ASC
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmtFirst, "i", $carro_id);
    mysqli_stmt_execute($stmtFirst);
    $resFirst = mysqli_stmt_get_result($stmtFirst);
    $first = $resFirst ? mysqli_fetch_assoc($resFirst) : null;
    mysqli_stmt_close($stmtFirst);

    $newCapa = $first['caminho'] ?? null;

    // atualizar imagem principal do carro
    if ($newCapa !== null && $newCapa !== '') {
        $stmtUp = mysqli_prepare($conexao, "UPDATE carros SET imagem = ? WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmtUp, "si", $newCapa, $carro_id);
        if (!mysqli_stmt_execute($stmtUp)) {
            throw new Exception("Erro ao atualizar capa do carro.");
        }
        mysqli_stmt_close($stmtUp);
    } else {
        $stmtUpNull = mysqli_prepare($conexao, "UPDATE carros SET imagem = NULL WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmtUpNull, "i", $carro_id);
        if (!mysqli_stmt_execute($stmtUpNull)) {
            throw new Exception("Erro ao limpar capa do carro.");
        }
        mysqli_stmt_close($stmtUpNull);
    }

    mysqli_commit($conexao);

    // apagar ficheiro físico só depois do commit
    if ($abs !== '' && is_file($abs)) {
        @unlink($abs);
    }

    echo json_encode(['ok' => true]);

} catch (Throwable $e) {
    mysqli_rollback($conexao);
    echo json_encode([
        'ok' => false,
        'error' => 'Erro ao apagar.'
    ]);
}