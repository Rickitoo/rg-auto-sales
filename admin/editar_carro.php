<?php
// admin/editar_carro.php
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
            $permitidas = ['jpg', 'jpeg', 'png', 'webp'];
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
                $ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));

                if (!in_array($ext, $permitidas, true)) {
                    $falhas[] = "Formato não permitido: " . h($nomeOriginal);
                    continue;
                }

                if ($tamanho <= 0) {
                    $falhas[] = "Ficheiro inválido: " . h($nomeOriginal);
                    continue;
                }

                if ($tamanho > 8 * 1024 * 1024) {
                    $falhas[] = "Ficheiro muito grande (máx. 8MB): " . h($nomeOriginal);
                    continue;
                }

                $novoNome = normalizarNomeFicheiro($nomeOriginal);
                $destinoAbsoluto = $pastaUploads . DIRECTORY_SEPARATOR . $novoNome;

                if (!move_uploaded_file($tmp, $destinoAbsoluto)) {
                    $falhas[] = "Não foi possível guardar: " . h($nomeOriginal);
                    continue;
                }

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

include("includes/layout_top.php");
?>

<style>
    .edit-card{
        background:#fff;
        border-radius:16px;
        padding:20px;
        box-shadow:0 4px 18px rgba(0,0,0,.08);
        margin-bottom:20px;
    }
    .section-head{
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:15px;
        flex-wrap:wrap;
        margin-bottom:16px;
    }
    .section-head h2{
        margin:0;
        font-size:24px;
    }
    .section-sub{
        color:#6b7280;
        font-size:14px;
        margin-top:6px;
    }
    .top-actions{
        display:flex;
        gap:10px;
        flex-wrap:wrap;
    }
    .btn{
        display:inline-block;
        padding:10px 16px;
        border:none;
        border-radius:10px;
        text-decoration:none;
        cursor:pointer;
        font-weight:bold;
    }
    .btn-primary{ background:#0d6efd; color:#fff; }
    .btn-secondary{ background:#6c757d; color:#fff; }
    .btn-dark{ background:#212529; color:#fff; }

    .alert{
        padding:12px 15px;
        border-radius:10px;
        margin-bottom:15px;
        font-weight:bold;
    }
    .success{ background:#d1e7dd; color:#0f5132; }
    .danger{ background:#f8d7da; color:#842029; }

    .form-grid{
        display:grid;
        grid-template-columns:repeat(4, 1fr);
        gap:16px;
    }
    .field-full{
        grid-column:1 / -1;
    }
    label{
        display:block;
        font-weight:bold;
        margin-bottom:6px;
        color:#111827;
    }
    input, select, textarea{
        width:100%;
        padding:11px 12px;
        border:1px solid #d1d5db;
        border-radius:10px;
        background:#fff;
        box-sizing:border-box;
    }
    textarea{
        min-height:120px;
        resize:vertical;
    }

    .gallery-wrap{
        display:grid;
        grid-template-columns:240px 1fr;
        gap:20px;
        align-items:start;
    }
    .cover-img{
        width:100%;
        height:180px;
        object-fit:cover;
        border-radius:14px;
        border:1px solid #ddd;
        background:#f3f4f6;
    }
    .sem-foto{
        width:100%;
        height:180px;
        border-radius:14px;
        border:1px dashed #cbd5e1;
        display:flex;
        align-items:center;
        justify-content:center;
        background:#f8fafc;
        color:#64748b;
        font-weight:bold;
    }

    .stats{
        display:grid;
        grid-template-columns:repeat(4, 1fr);
        gap:14px;
    }
    .stat-box{
        background:#f9fafb;
        border:1px solid #e5e7eb;
        border-radius:12px;
        padding:14px;
    }
    .stat-label{
        font-size:12px;
        font-weight:bold;
        text-transform:uppercase;
        color:#6b7280;
        margin-bottom:6px;
    }
    .stat-value{
        font-size:18px;
        font-weight:bold;
        color:#111827;
    }

    .muted{
        color:#6b7280;
        font-size:13px;
    }

    .mini-gallery{
        display:grid;
        grid-template-columns:repeat(4, 1fr);
        gap:12px;
        margin-top:18px;
    }
    .mini-item{
        background:#fff;
        border:1px solid #e5e7eb;
        border-radius:12px;
        padding:8px;
    }
    .mini-gallery img{
        width:100%;
        height:95px;
        object-fit:cover;
        border-radius:10px;
        border:1px solid #ddd;
        background:#f3f4f6;
        display:block;
    }
    .mini-meta{
        margin-top:6px;
        font-size:12px;
        color:#6b7280;
        text-align:center;
    }

    .upload-box{
        margin-top:20px;
        padding:16px;
        border:1px dashed #cbd5e1;
        border-radius:14px;
        background:#f8fafc;
    }

    @media (max-width: 1100px){
        .form-grid{ grid-template-columns:repeat(2, 1fr); }
        .stats{ grid-template-columns:repeat(2, 1fr); }
    }
    @media (max-width: 760px){
        .gallery-wrap{ grid-template-columns:1fr; }
        .form-grid{ grid-template-columns:1fr; }
        .stats{ grid-template-columns:1fr; }
        .mini-gallery{ grid-template-columns:repeat(2, 1fr); }
    }
</style>

<div class="edit-card">
    <div class="section-head">
        <div>
            <h2>Editar Carro</h2>
            <div class="section-sub">
                ID: <?= (int)$carro['id'] ?> — <?= h($carro['marca']) ?> <?= h($carro['modelo']) ?>
            </div>
        </div>

        <div class="top-actions">
            <a href="gerir_fotos.php?id=<?= $id ?>" class="btn btn-dark">Gerir Fotos</a>
            <a href="listar_carros.php" class="btn btn-secondary">Voltar à Lista</a>
        </div>
    </div>

    <?php if ($mensagem): ?>
        <div class="alert success"><?= h($mensagem) ?></div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="alert danger"><?= h($erro) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="acao" value="salvar_carro">

        <div class="form-grid">
            <div>
                <label>Marca</label>
                <input type="text" name="marca" value="<?= h($carro['marca']) ?>" required>
            </div>

            <div>
                <label>Modelo</label>
                <input type="text" name="modelo" value="<?= h($carro['modelo']) ?>" required>
            </div>

            <div>
                <label>Ano</label>
                <input type="number" name="ano" value="<?= h($carro['ano']) ?>" required>
            </div>

            <div>
                <label>Preço</label>
                <input type="number" step="0.01" name="preco" value="<?= h($carro['preco']) ?>" required>
            </div>

            <div>
                <label>Status</label>
                <select name="status">
                    <option value="disponivel" <?= $carro['status'] === 'disponivel' ? 'selected' : '' ?>>Disponível</option>
                    <option value="vendido" <?= $carro['status'] === 'vendido' ? 'selected' : '' ?>>Vendido</option>
                </select>
            </div>

            <div>
                <label>Preço de Venda</label>
                <input type="number" step="0.01" name="preco_venda" value="<?= h($carro['preco_venda']) ?>">
            </div>

            <div>
                <label>Comissão</label>
                <input type="number" step="0.01" name="comissao" value="<?= h($carro['comissao']) ?>">
            </div>

            <div>
                <label>Data da Venda</label>
                <input
                    type="datetime-local"
                    name="data_venda"
                    value="<?= !empty($carro['data_venda']) ? date('Y-m-d\TH:i', strtotime($carro['data_venda'])) : '' ?>"
                >
            </div>

            <div class="field-full">
                <label>Descrição</label>
                <textarea name="descricao"><?= h($carro['descricao']) ?></textarea>
            </div>
        </div>

        <div style="margin-top:18px;">
            <button type="submit" class="btn btn-primary">Guardar Alterações</button>
        </div>
    </form>
</div>

<div class="edit-card">
    <div class="section-head">
        <div>
            <h2>Resumo da Galeria</h2>
            <div class="section-sub">Pré-visualização rápida das fotos deste carro</div>
        </div>
    </div>

    <div class="gallery-wrap">
        <div>
            <?php if ($imgCapa !== ''): ?>
                <img src="<?= h($imgCapa) ?>" alt="Foto principal" class="cover-img">
            <?php else: ?>
                <div class="sem-foto">Sem foto principal</div>
            <?php endif; ?>
        </div>

        <div>
            <div class="stats">
                <div class="stat-box">
                    <div class="stat-label">Total de fotos</div>
                    <div class="stat-value"><?= $totalFotos ?></div>
                </div>

                <div class="stat-box">
                    <div class="stat-label">Imagem principal</div>
                    <div class="stat-value"><?= $carro['imagem'] ? 'Definida' : 'Automática / vazia' ?></div>
                </div>

                <div class="stat-box">
                    <div class="stat-label">Preço atual</div>
                    <div class="stat-value"><?= money($carro['preco']) ?></div>
                </div>

                <div class="stat-box">
                    <div class="stat-label">Status</div>
                    <div class="stat-value"><?= ucfirst(h($carro['status'])) ?></div>
                </div>
            </div>

            <div style="margin-top:18px;">
                <a href="gerir_fotos.php?id=<?= $id ?>" class="btn btn-dark">Abrir gestor de fotos</a>
            </div>

            <div class="upload-box">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="acao" value="upload_fotos">

                    <label for="novas_fotos">Adicionar novas fotos</label>
                    <input type="file" name="novas_fotos[]" id="novas_fotos" accept=".jpg,.jpeg,.png,.webp" multiple>

                    <p class="muted" style="margin:10px 0 14px;">
                        Podes selecionar várias fotos ao mesmo tempo. Formatos aceites: JPG, JPEG, PNG e WEBP.
                    </p>

                    <button type="submit" class="btn btn-primary">Fazer Upload</button>
                </form>
            </div>
        </div>
    </div>

    <?php if ($resMiniFotos && mysqli_num_rows($resMiniFotos) > 0): ?>
        <div class="mini-gallery">
            <?php while ($foto = mysqli_fetch_assoc($resMiniFotos)): ?>
                <div class="mini-item">
                    <img src="../uploads/<?= h($foto['foto']) ?>" alt="Miniatura">
                    <div class="mini-meta">Ordem: <?= (int)$foto['ordem'] ?></div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<?php include("includes/layout_bottom.php"); ?>