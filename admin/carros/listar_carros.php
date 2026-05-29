<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!function_exists('money')) {
    function money($v) {
        return number_format((float)$v, 2, ',', '.') . " MT";
    }
}

$busca  = trim($_GET['busca'] ?? '');
$status = trim($_GET['status'] ?? '');

$where = [];

if ($busca !== '') {
    $buscaEsc = mysqli_real_escape_string($conexao, $busca);
    $where[] = "(c.marca LIKE '%$buscaEsc%' OR c.modelo LIKE '%$buscaEsc%')";
}

if ($status !== '' && in_array($status, ['disponivel', 'vendido'], true)) {
    $statusEsc = mysqli_real_escape_string($conexao, $status);
    $where[] = "c.status = '$statusEsc'";
}

$sql = "
    SELECT
        c.*,
        COUNT(cf.id) AS total_fotos
    FROM carros c
    LEFT JOIN carros_fotos cf ON cf.carro_id = c.id
";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " GROUP BY c.id ORDER BY c.id DESC";

$res = mysqli_query($conexao, $sql);

if (!$res) {
    die("Erro ao buscar carros: " . mysqli_error($conexao));
}

$carros = [];

while ($carro = mysqli_fetch_assoc($res)) {
    $idCarro = (int)$carro['id'];
    $capa = $carro['imagem'] ?? '';

    $resFoto = mysqli_query(
        $conexao,
        "SELECT caminho FROM carros_fotos WHERE carro_id = $idCarro ORDER BY ordem ASC, id ASC LIMIT 1"
    );

    if ($resFoto && mysqli_num_rows($resFoto) > 0) {
        $fotoRow = mysqli_fetch_assoc($resFoto);
        if (!empty($fotoRow['caminho'])) {
            $capa = $fotoRow['caminho'];
        }
    }

    $carro['id_carro'] = $idCarro;
    $carro['img_src'] = !empty($capa) ? fotoCarroUrl(['imagem' => $capa]) : '';
    $carro['status_classe'] = $carro['status'] === 'vendido' ? 'badge-vendido' : 'badge-disponivel';
    $carro['total_fotos'] = (int)($carro['total_fotos'] ?? 0);
    $carro['data_registo_formatada'] = !empty($carro['data_registo'])
        ? date('d/m/Y H:i', strtotime($carro['data_registo']))
        : '-';

    $carros[] = $carro;
}

$totalCarros = count($carros);
$totalDisponiveis = count(array_filter($carros, static fn($carro) => ($carro['status'] ?? '') !== 'vendido'));
$totalVendidos = $totalCarros - $totalDisponiveis;

$pageTitle = 'Carros';
$pageSubtitle = 'Gestão de viaturas, estoque e disponibilidade';
$contentFile = BASE_PATH . '/app/views/admin/carros/listar_carros_content.php';

require BASE_PATH . '/app/views/layouts/admin_layout.php';
