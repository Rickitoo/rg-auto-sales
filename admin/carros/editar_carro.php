<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

// admin/editar_carro.php


if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}


if (session_status() === PHP_SESSION_NONE) {
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!function_exists('h')) {
function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
}

function money($v) {
    return number_format((float)$v, 2, ',', '.') . " MT";
}

function normalizarNomeFicheiro($nome) {
    $ext = strtolower(pathinfo($nome, PATHINFO_EXTENSION));
    $base = pathinfo($nome, PATHINFO_FILENAME);

    $base = preg_replace('/[^a-zA-Z0-9_-]/', '_', $base);
    $base = preg_replace('/_+/', '_', $base);
    $base = trim($base, '_');

    if ($base === '') {
        $base = 'foto';
    }

    return $base . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die("ID inválido.");
}

$sqlCarro = "SELECT * FROM caminho WHERE id = $id LIMIT 1";
$resCarro = mysqli_query($conexao, $sqlCarro);

if (!$resCarro || mysqli_num_rows($resCarro) === 0) {
    die("Carro não encontrado.");
}

$carro = mysqli_fetch_assoc($resCarro);

$mensagem = "";
$erro = "";

/*
|--------------------------------------------------------------------------
| POST: Atualizar dados do carro
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar_carro') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
        die("CSRF inválido.");
    }

    $marca       = trim($_POST['marca'] ?? '');
    $modelo      = trim($_POST['modelo'] ?? '');
    $ano         = (int)($_POST['ano'] ?? 0);
    $preco       = (float)str_replace(',', '.', $_POST['preco'] ?? 0);
    $descricao   = trim($_POST['descricao'] ?? '');
    $status      = trim($_POST['status'] ?? 'disponivel');

    $preco_venda = (isset($_POST['preco_venda']) && $_POST['preco_venda'] !== '')
        ? (float)str_replace(',', '.', $_POST['preco_venda'])
        : null;

    $comissao = (isset($_POST['comissao']) && $_POST['comissao'] !== '')
        ? (float)str_replace(',', '.', $_POST['comissao'])
        : null;

    $data_venda = !empty($_POST['data_venda']) ? $_POST['data_venda'] : null;

    if ($marca === '' || $modelo === '' || $ano <= 0 || $preco <= 0) {
        $erro = "Preencha os campos obrigatórios corretamente.";
    } elseif (!in_array($status, ['disponivel', 'vendido'], true)) {
        $erro = "Status inválido.";
    } else {
        $stmt = mysqli_prepare($conexao, "
            UPDATE carros
            SET marca = ?, modelo = ?, ano = ?, preco = ?, descricao = ?, status = ?, preco_venda = ?, comissao = ?, data_venda = ?
            WHERE id = ?
        ");

        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt,
                "ssidssddsi",
                $marca,
                $modelo,
                $ano,
                $preco,
                $descricao,
                $status,
                $preco_venda,
                $comissao,
                $data_venda,
                $id
            );

            if (mysqli_stmt_execute($stmt)) {
                $mensagem = "Carro atualizado com sucesso.";
            } else {
                $erro = "Erro ao atualizar: " . mysqli_stmt_error($stmt);
            }

            mysqli_stmt_close($stmt);
        } else {
            $erro = "Erro ao preparar query.";
        }
    }
}

/*
|--------------------------------------------------------------------------
| POST: Upload de novas fotos
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'upload_fotos') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
        die("CSRF inválido.");
    }

    if (
        !isset($_FILES['novas_fotos']) ||
        !isset($_FILES['novas_fotos']['name']) ||
        !is_array($_FILES['novas_fotos']['name'])
    ) {
        $erro = "Nenhuma foto selecionada.";
    } else {
        $pastaUploads = realpath(__DIR__ . "/../uploads");
        if ($pastaUploads === false) {
            $erro = "A pasta uploads não foi encontrada.";
        } else {
            $totalEnviadas = 0;
            $falhas = [];

            $sqlOrdem = "SELECT COALESCE(MAX(ordem), 0) AS max_ordem FROM caminho WHERE carro_id = $id";
            $resOrdem = mysqli_query($conexao, $sqlOrdem);
            $rowOrdem = $resOrdem ? mysqli_fetch_assoc($resOrdem) : ['max_ordem' => 0];
            $ordemAtual = (int)($rowOrdem['max_ordem'] ?? 0);

            $nomes = $_FILES['novas_fotos']['name'];
            $tmpNames = $_FILES['novas_fotos']['tmp_name'];
            $errors = $_FILES['novas_fotos']['error'];
            $sizes = $_FILES['novas_fotos']['size'];

            for ($i = 0; $i < count($nomes); $i++) {
                if (($errors[$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                if (($errors[$i] ?? 0) !== UPLOAD_ERR_OK) {
                    $falhas[] = "Falha no upload do ficheiro: " . h($nomes[$i]);
                    continue;
                }

                $nomeOriginal = $nomes[$i];
                $tmp = $tmpNames[$i];
                $tamanho = (int)($sizes[$i] ?? 0);

                $file = [
                    'name' => $nomeOriginal,
                    'tmp_name' => $tmp,
                    'error' => $errors[$i] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $tamanho,
                ];
                [$okUpload, $infoUpload, $erroUpload] = secure_uploaded_image($file, $pastaUploads, '', 8 * 1024 * 1024, 'carro-' . $id);
                if (!$okUpload) {
                    $falhas[] = h($nomeOriginal) . ": " . h($erroUpload);
                    continue;
                }

                $novoNome = $infoUpload['name'];
                $destinoAbsoluto = $infoUpload['abs'];

                $ordemAtual++;

                $stmtFoto = mysqli_prepare($conexao, "
                    INSERT INTO caminho (carro_id, foto, ordem)
                    VALUES (?, ?, ?)
                ");

                if (!$stmtFoto) {
                    @unlink($destinoAbsoluto);
                    $falhas[] = "Erro ao preparar registo da foto: " . h($nomeOriginal);
                    continue;
                }

                mysqli_stmt_bind_param($stmtFoto, "isi", $id, $novoNome, $ordemAtual);

                if (!mysqli_stmt_execute($stmtFoto)) {
                    @unlink($destinoAbsoluto);
                    $falhas[] = "Erro ao gravar no banco: " . h($nomeOriginal);
                    mysqli_stmt_close($stmtFoto);
                    continue;
                }

                mysqli_stmt_close($stmtFoto);
                $totalEnviadas++;
            }

            if ($totalEnviadas > 0 && empty($carro['imagem'])) {
                $sqlPrimeiraNova = "SELECT foto FROM caminho WHERE carro_id = $id ORDER BY ordem ASC, id ASC LIMIT 1";
                $resPrimeiraNova = mysqli_query($conexao, $sqlPrimeiraNova);
                if ($resPrimeiraNova && mysqli_num_rows($resPrimeiraNova) > 0) {
                    $rowPrimeiraNova = mysqli_fetch_assoc($resPrimeiraNova);
                    $primeiraFoto = $rowPrimeiraNova['foto'];

                    $stmtCapa = mysqli_prepare($conexao, "UPDATE carros SET imagem = ? WHERE id = ?");
                    if ($stmtCapa) {
                        mysqli_stmt_bind_param($stmtCapa, "si", $primeiraFoto, $id);
                        mysqli_stmt_execute($stmtCapa);
                        mysqli_stmt_close($stmtCapa);
                    }
                }
            }

            if ($totalEnviadas > 0) {
                $mensagem = "Upload concluído com sucesso. {$totalEnviadas} foto(s) adicionada(s).";
            }

            if (!empty($falhas)) {
                $textoFalhas = implode(" | ", $falhas);
                if ($erro !== '') {
                    $erro .= " | ";
                }
                $erro .= $textoFalhas;
            }
            function comprimirImagem($origem, $destino, $qualidade = 75) {
                $info = getimagesize($origem);

                if ($info['mime'] == 'image/jpeg') {
                    $img = imagecreatefromjpeg($origem);
                    imagejpeg($img, $destino, $qualidade);
                } 
                elseif ($info['mime'] == 'image/png') {
                    $img = imagecreatefrompng($origem);
                    imagepng($img, $destino, 7);
                }

                imagedestroy($img);
            }
            if ($totalEnviadas === 0 && empty($falhas)) {
                $erro = "Nenhuma foto foi enviada.";
            }
        }
    }
}

// Recarregar carro após qualquer POST
$resCarro = mysqli_query($conexao, $sqlCarro);
if ($resCarro && mysqli_num_rows($resCarro) > 0) {
    $carro = mysqli_fetch_assoc($resCarro);
}

// Buscar estatísticas das fotos
$sqlFotosInfo = "
    SELECT COUNT(*) AS total_fotos
    FROM caminho
    WHERE carro_id = $id
";
$resFotosInfo = mysqli_query($conexao, $sqlFotosInfo);
$fotosInfo = $resFotosInfo ? mysqli_fetch_assoc($resFotosInfo) : ['total_fotos' => 0];

$totalFotos = (int)($fotosInfo['total_fotos'] ?? 0);

// Buscar miniaturas da galeria
$sqlMiniFotos = "
    SELECT id, foto, ordem
    FROM caminho
    WHERE carro_id = $id
    ORDER BY ordem ASC, id ASC
    LIMIT 8
";
$resMiniFotos = mysqli_query($conexao, $sqlMiniFotos);

// Definir imagem de capa
$capa = $carro['imagem'] ?? '';

if (empty($capa)) {
    $sqlPrimeira = "SELECT foto FROM caminho WHERE carro_id = $id ORDER BY ordem ASC, id ASC LIMIT 1";
    $resPrimeira = mysqli_query($conexao, $sqlPrimeira);
    if ($resPrimeira && mysqli_num_rows($resPrimeira) > 0) {
        $rowPrimeira = mysqli_fetch_assoc($resPrimeira);
        $capa = $rowPrimeira['foto'];
    }
}

$imgCapa = !empty($capa) ? "../uploads/" . $capa : "";

$pageTitle = 'Editar Carro';
$pageSubtitle = 'Atualiza��o dos dados da viatura';
$contentFile = BASE_PATH . '/app/views/admin/carros/editar_carro_content.php';

require BASE_PATH . '/app/views/layouts/admin_layout.php';