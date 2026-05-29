<?php
require_once __DIR__ . '/../../core/bootstrap.php';
require_admin();

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}
// Helpers
function respond($msg, $code = 200) {
    http_response_code($code);
    echo $msg;
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond("method_not_allowed", 405);
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    respond("csrf_invalid", 403);
}

// Receber dados
$id = (int)($_POST['lead_id'] ?? 0);
$status = $_POST['status'] ?? '';

// Validação básica
$allowed = [
    'novo',
    'contactado',
    'qualificado',
    'agendado',
    'orcamento',
    'aguardando_opcoes',
    'negociacao',
    'pagamento',
    'embarcado',
    'em_transito',
    'desalfandegamento',
    'entregue',
    'fechado',
    'perdido',
];

if ($id <= 0 || !in_array($status, $allowed)) {
    respond("invalid", 400);
}

// Atualizar
$stmt = mysqli_prepare($conexao, "UPDATE leads SET status=? WHERE id=?");
if (!$stmt) {
    respond("sql_error", 500);
}

$now = date('Y-m-d H:i:s');

mysqli_query($conexao, "
    UPDATE leads 
    SET ultimo_contacto='$now'
    WHERE id=$id
");

$proximo = date('Y-m-d H:i:s', strtotime('+2 days'));

mysqli_query($conexao, "
    UPDATE leads 
    SET proximo_followup='$proximo'
    WHERE id=$id
");

mysqli_stmt_bind_param($stmt, "si", $status, $id);

if (mysqli_stmt_execute($stmt)) {
    respond("ok");
} else {
    respond("error", 500);
}

mysqli_stmt_close($stmt);
?>
<button onclick="setStatus(<?= $row['id'] ?>, 'contactado')">📞</button>
<button onclick="setStatus(<?= $row['id'] ?>, 'negociacao')">🤝</button>
<button onclick="setStatus(<?= $row['id'] ?>, 'fechado')">✅</button>
<script>
function setStatus(id, status) {

    fetch('leads_status_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'lead_id=' + id + '&status=' + status + '&csrf_token=<?= h(csrf_token()) ?>'
    })
    .then(res => res.text())
    .then(res => {

        if (res === 'ok') {

            // Atualizar visual sem reload
            const el = document.getElementById('status-' + id);

            let color = 'bg-light';

            if (status === 'contactado') color = 'bg-success';
            else if (status === 'qualificado') color = 'bg-primary';
            else if (status === 'agendado') color = 'bg-dark';
            else if (status === 'negociacao') color = 'bg-warning';
            else if (status === 'fechado') color = 'bg-secondary';
            else if (status === 'perdido') color = 'bg-danger';

            el.className = 'badge ' + color;
            el.innerText = status;

        } else {
            alert('Erro: ' + res);
        }

    });
}
</script>
