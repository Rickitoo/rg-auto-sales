<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../conexao.php";

header('Content-Type: application/json');

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$carro_id = (int)($data['carro_id'] ?? 0);
$ids = $data['ids'] ?? [];

if ($carro_id <= 0 || !is_array($ids) || count($ids) === 0) {
    echo json_encode(['ok' => false, 'error' => 'Dados inválidos.']);
    exit;
}

// limpar ids
$ids = array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));

if (count($ids) === 0) {
    echo json_encode(['ok' => false, 'error' => 'Nenhuma foto válida recebida.']);
    exit;
}

mysqli_begin_transaction($conexao);

try {
    // garantir que os ids pertencem ao carro
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids) + 1);

    $sqlCheck = "SELECT id FROM carros_fotos WHERE carro_id = ? AND id IN ($placeholders)";
    $stmtCheck = mysqli_prepare($conexao, $sqlCheck);

    $params = array_merge([$carro_id], $ids);

    mysqli_stmt_bind_param($stmtCheck, $types, ...$params);
    mysqli_stmt_execute($stmtCheck);
    $resCheck = mysqli_stmt_get_result($stmtCheck);

    $idsValidos = [];
    while ($row = mysqli_fetch_assoc($resCheck)) {
        $idsValidos[] = (int)$row['id'];
    }
    mysqli_stmt_close($stmtCheck);

    if (count($idsValidos) !== count($ids)) {
        throw new Exception("Há fotos inválidas na ordenação.");
    }

    // atualizar ordem
    $stmt = mysqli_prepare($conexao, "UPDATE carros_fotos SET ordem = ? WHERE id = ? AND carro_id = ?");
    $ordem = 1;

    foreach ($ids as $id) {
        mysqli_stmt_bind_param($stmt, "iii", $ordem, $id, $carro_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Erro ao atualizar ordem.");
        }
        $ordem++;
    }
    mysqli_stmt_close($stmt);

    // buscar primeira foto
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

    $capa = $first['caminho'] ?? null;

    // atualizar imagem principal do carro
    if ($capa !== null && $capa !== '') {
        $stmtUp = mysqli_prepare($conexao, "UPDATE carros SET imagem = ? WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmtUp, "si", $capa, $carro_id);
        if (!mysqli_stmt_execute($stmtUp)) {
            throw new Exception("Erro ao atualizar capa.");
        }
        mysqli_stmt_close($stmtUp);
    }

    mysqli_commit($conexao);
    echo json_encode(['ok' => true]);

} catch (Throwable $e) {
    mysqli_rollback($conexao);
    echo json_encode([
        'ok' => false,
        'error' => 'Erro ao guardar ordem/capa.'
    ]);
}