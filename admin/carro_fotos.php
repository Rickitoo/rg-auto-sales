<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include("auth_check.php");
include("admin/includes/db.php");

require_once __DIR__ . "/../conexao.php";

function h($s){
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$carro_id = (int)($_GET['carro_id'] ?? 0);
if ($carro_id <= 0) {
    die("carro_id inválido.");
}

$stmtC = mysqli_prepare($conexao, "SELECT id, marca, modelo, ano FROM carros WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($stmtC, "i", $carro_id);
mysqli_stmt_execute($stmtC);
$resC = mysqli_stmt_get_result($stmtC);
$carro = $resC ? mysqli_fetch_assoc($resC) : null;
mysqli_stmt_close($stmtC);

if (!$carro) {
    die("Carro não encontrado.");
}

$erroUpload = '';
$sucessoUpload = '';

function fotoSrc(string $caminho): string {
    $src = trim($caminho);

    if ($src === '') {
        return "../ImagensRG/logo.png";
    }

    if (preg_match('~^(https?://|/)~', $src)) {
        return $src;
    }

    if (str_starts_with($src, 'uploads/')) {
        return "../" . $src;
    }

    if (str_starts_with($src, 'ImagensRG/')) {
        return "../" . $src;
    }

    return "../uploads/carros/" . $src;
}

// UPLOAD DE NOVAS FOTOS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_fotos'])) {
    if (!isset($_FILES['fotos']) || empty($_FILES['fotos']['name'][0])) {
        $erroUpload = "Seleciona pelo menos uma foto.";
    } else {
        $pastaFisica = realpath(__DIR__ . "/..") . "/uploads/carros/";
        if (!is_dir($pastaFisica)) {
            mkdir($pastaFisica, 0777, true);
        }

        $permitidas = ['jpg', 'jpeg', 'png', 'webp'];
        $enviadas = 0;

        $stmtMax = mysqli_prepare($conexao, "SELECT COALESCE(MAX(ordem), 0) AS max_ordem FROM carros_fotos WHERE carro_id=?");
        mysqli_stmt_bind_param($stmtMax, "i", $carro_id);
        mysqli_stmt_execute($stmtMax);
        $resMax = mysqli_stmt_get_result($stmtMax);
        $rowMax = $resMax ? mysqli_fetch_assoc($resMax) : ['max_ordem' => 0];
        mysqli_stmt_close($stmtMax);

        $ordemAtual = (int)($rowMax['max_ordem'] ?? 0);

        foreach ($_FILES['fotos']['tmp_name'] as $i => $tmpName) {
            $erro = $_FILES['fotos']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
            $nomeOriginal = $_FILES['fotos']['name'][$i] ?? '';

            if ($erro !== UPLOAD_ERR_OK || !is_uploaded_file($tmpName)) {
                continue;
            }

            $ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
            if (!in_array($ext, $permitidas, true)) {
                continue;
            }

            $novoNome = 'carro_' . $carro_id . '_' . time() . '_' . $i . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
            $destinoFisico = $pastaFisica . $novoNome;
            $caminhoBD = 'uploads/carros/' . $novoNome;

            if (!move_uploaded_file($tmpName, $destinoFisico)) {
                continue;
            }

            $ordemAtual++;

            $stmtIns = mysqli_prepare($conexao, "INSERT INTO carros_fotos (carro_id, caminho, ordem) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmtIns, "isi", $carro_id, $caminhoBD, $ordemAtual);
            mysqli_stmt_execute($stmtIns);
            mysqli_stmt_close($stmtIns);

            $enviadas++;
        }

        // Atualiza capa do carro com a primeira foto
        $stmtFirst = mysqli_prepare($conexao, "
            SELECT caminho
            FROM carros_fotos
            WHERE carro_id=?
            ORDER BY ordem ASC, id ASC
            LIMIT 1
        ");
        mysqli_stmt_bind_param($stmtFirst, "i", $carro_id);
        mysqli_stmt_execute($stmtFirst);
        $resFirst = mysqli_stmt_get_result($stmtFirst);
        $first = $resFirst ? mysqli_fetch_assoc($resFirst) : null;
        mysqli_stmt_close($stmtFirst);

        $capa = $first['caminho'] ?? null;
        if ($capa) {
            $stmtUp = mysqli_prepare($conexao, "UPDATE carros SET imagem=? WHERE id=? LIMIT 1");
            mysqli_stmt_bind_param($stmtUp, "si", $capa, $carro_id);
            mysqli_stmt_execute($stmtUp);
            mysqli_stmt_close($stmtUp);
        }

        if ($enviadas > 0) {
            header("Location: fotos_carro.php?carro_id=" . $carro_id . "&upload=ok");
            exit;
        } else {
            $erroUpload = "Nenhuma foto válida foi enviada. Usa JPG, JPEG, PNG ou WEBP.";
        }
    }
}

if (($_GET['upload'] ?? '') === 'ok') {
    $sucessoUpload = "Fotos enviadas com sucesso.";
}

// LISTAR FOTOS
$stmt = mysqli_prepare($conexao, "SELECT id, caminho, ordem FROM carros_fotos WHERE carro_id=? ORDER BY ordem ASC, id ASC");
mysqli_stmt_bind_param($stmt, "i", $carro_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$fotos = [];
while ($res && ($r = mysqli_fetch_assoc($res))) {
    $fotos[] = $r;
}
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fotos do carro | Admin RG</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .grid {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 20px;
        }

        .foto {
            width: 180px;
            border: 1px solid rgba(0,0,0,.12);
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
            cursor: grab;
            box-shadow: 0 4px 14px rgba(0,0,0,.06);
        }

        .foto.dragging {
            opacity: .5;
        }

        .foto img {
            width: 100%;
            height: 140px;
            object-fit: cover;
            display: block;
        }

        .foto .meta {
            padding: 10px;
        }

        .foto .meta-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .btn-del {
            border: none;
            background: #e11d48;
            color: #fff;
            padding: 6px 10px;
            border-radius: 10px;
            cursor: pointer;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .hint {
            opacity: .8;
            font-size: .95rem;
        }

        .upload-box {
            margin-top: 18px;
            padding: 16px;
            border: 1px solid rgba(0,0,0,.12);
            border-radius: 14px;
            background: #fff;
        }

        .upload-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            margin-top: 10px;
        }

        .alert-ok {
            margin-top: 15px;
            padding: 12px 14px;
            border-radius: 10px;
            background: #dcfce7;
            color: #166534;
        }

        .alert-err {
            margin-top: 15px;
            padding: 12px 14px;
            border-radius: 10px;
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>

<div class="small-container">
    <div class="topbar">
        <div>
            <h2 class="title" style="margin-bottom:6px;">
                Fotos: <?= h($carro['marca'] . " " . $carro['modelo'] . " " . $carro['ano']) ?>
            </h2>
            <div class="hint">Arrasta as fotos para reordenar. Clica “Apagar” para remover.</div>
        </div>

        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a class="btn btn--outline" href="dashboard.php">← Dashboard</a>
            <a class="btn btn--outline" href="listar_carros.php">← Carros</a>
            <a class="btn" href="../product-details.php?id=<?= $carro_id ?>" target="_blank" rel="noopener">Ver no site</a>
        </div>
    </div>

    <div class="upload-box">
        <h3 style="margin-top:0;">Adicionar novas fotos</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="upload_fotos" value="1">

            <div class="upload-row">
                <input type="file" name="fotos[]" multiple accept=".jpg,.jpeg,.png,.webp" required>
                <button type="submit" class="btn">Enviar fotos</button>
            </div>

            <div class="hint" style="margin-top:10px;">
                Formatos permitidos: JPG, JPEG, PNG e WEBP.
            </div>
        </form>

        <?php if ($sucessoUpload): ?>
            <div class="alert-ok"><?= h($sucessoUpload) ?></div>
        <?php endif; ?>

        <?php if ($erroUpload): ?>
            <div class="alert-err"><?= h($erroUpload) ?></div>
        <?php endif; ?>
    </div>

    <?php if (count($fotos) > 0): ?>
        <div id="grid" class="grid" data-carro-id="<?= $carro_id ?>">
            <?php foreach ($fotos as $f): ?>
                <div class="foto" draggable="true" data-id="<?= (int)$f['id'] ?>">
                    <img src="<?= h(fotoSrc((string)$f['caminho'])) ?>" alt="Foto <?= (int)$f['id'] ?>">

                    <div class="meta">
                        <div class="meta-top">
                            <small>ID #<?= (int)$f['id'] ?></small>
                            <small>Ordem: <?= (int)$f['ordem'] ?></small>
                        </div>

                        <button class="btn-del" type="button" data-id="<?= (int)$f['id'] ?>">
                            Apagar
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="margin-top: 20px;">Sem fotos na galeria.</p>
    <?php endif; ?>
</div>

<script>
const grid = document.getElementById('grid');
let dragEl = null;

if (grid) {
    function getOrderIds() {
        return [...grid.querySelectorAll('.foto')].map(el => el.dataset.id);
    }

    function sendOrder() {
        const ids = getOrderIds();
        const carroId = grid.dataset.carroId;

        fetch('carro_fotos_order.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ carro_id: carroId, ids })
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) {
                alert(data.error || 'Falha ao guardar ordem.');
            }
        })
        .catch(() => alert('Erro de rede ao guardar ordem.'));
    }

    grid.addEventListener('dragstart', (e) => {
        const item = e.target.closest('.foto');
        if (!item) return;

        dragEl = item;
        item.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    });

    grid.addEventListener('dragend', (e) => {
        const item = e.target.closest('.foto');
        if (item) item.classList.remove('dragging');
        dragEl = null;
        sendOrder();
    });

    grid.addEventListener('dragover', (e) => {
        e.preventDefault();

        const over = e.target.closest('.foto');
        if (!over || !dragEl || over === dragEl) return;

        const rect = over.getBoundingClientRect();
        const after = (e.clientX - rect.left) > (rect.width / 2);

        if (after) {
            over.after(dragEl);
        } else {
            over.before(dragEl);
        }
    });

    grid.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-del');
        if (!btn) return;

        const id = btn.dataset.id;
        if (!confirm('Apagar esta foto?')) return;

        fetch('carro_fotos_delete.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ id })
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) {
                return alert(data.error || 'Falha ao apagar.');
            }

            const el = grid.querySelector(`.foto[data-id="${id}"]`);
            if (el) el.remove();
            sendOrder();
        })
        .catch(() => alert('Erro de rede ao apagar.'));
    });
}
</script>

</body>
</html>