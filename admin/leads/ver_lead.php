<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

if ($_SESSION['user']['role'] !== 'admin') {
   redirect_to('auth/login.php');
    exit();
}

if (!function_exists('h')) {
function h($v){
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
}

$lead_id = (int)($_GET['id'] ?? 0);

if ($lead_id <= 0) {
    die("Lead inválido");
}

// ======================
// BUSCAR LEAD
// ======================
$stmt = mysqli_prepare($conexao, "
    SELECT * 
    FROM leads 
    WHERE id=?
");

mysqli_stmt_bind_param($stmt, "i", $lead_id);
mysqli_stmt_execute($stmt);

$lead = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

mysqli_stmt_close($stmt);

if (!$lead) {
    die("Lead não encontrado");
}

// ======================
// STATUS COLORIDO
// ======================
$statusClass = match($lead['status']) {

    'Novo Lead' => 'novo',
    'Contactado' => 'contactado',
    'Interessado' => 'interessado',
    'Negociação' => 'negociacao',
    'Aguardando Pagamento' => 'pagamento',
    'Fechado' => 'fechado',
    'Perdido' => 'perdido',

    default => 'padrao'
};

// ======================
// ENVIAR MENSAGEM
// ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $mensagem = trim($_POST['mensagem'] ?? '');

    if ($mensagem !== '') {

        // guardar mensagem
        $stmt = mysqli_prepare($conexao, "
            INSERT INTO mensagens (
                lead_id,
                mensagem,
                tipo
            )
            VALUES (?, ?, 'enviada')
        ");

        mysqli_stmt_bind_param(
            $stmt,
            "is",
            $lead_id,
            $mensagem
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // atualizar lead
        $stmt = mysqli_prepare($conexao, "
            UPDATE leads
            SET 
                ultima_interacao = NOW(),
                proximo_followup = DATE_ADD(NOW(), INTERVAL 1 DAY)
            WHERE id=?
        ");

        mysqli_stmt_bind_param($stmt, "i", $lead_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        redirect_to('admin/leads/ver_lead.php?id=' . $lead_id);
        exit();
    }
}

// ======================
// MENSAGENS
// ======================
$stmt = mysqli_prepare($conexao, "
    SELECT *
    FROM mensagens
    WHERE lead_id=?
    ORDER BY id ASC
");

mysqli_stmt_bind_param($stmt, "i", $lead_id);
mysqli_stmt_execute($stmt);

$mensagens = mysqli_stmt_get_result($stmt);

mysqli_stmt_close($stmt);

// ======================
// WHATSAPP
// ======================
$telefoneWhatsapp = preg_replace(
    '/[^0-9]/',
    '',
    $lead['telefone']
);

?>

<!DOCTYPE html>
<html lang="pt">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>CRM Lead</title>

<style>

*{
    box-sizing:border-box;
}

body{
    margin:0;
    font-family:Arial, sans-serif;
    background:#f4f6f9;
}

.container{
    max-width:1000px;
    margin:auto;
    padding:20px;
}

.card{
    background:#fff;
    border-radius:14px;
    padding:20px;
    margin-bottom:20px;
    box-shadow:0 2px 10px rgba(0,0,0,.05);
}

.top{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
}

.badge{
    padding:7px 14px;
    border-radius:30px;
    color:#fff;
    font-size:12px;
    font-weight:bold;
}

.novo{
    background:#0d6efd;
}

.contactado{
    background:#0dcaf0;
}

.interessado{
    background:#ffc107;
    color:#000;
}

.negociacao{
    background:#fd7e14;
}

.pagamento{
    background:#6f42c1;
}

.fechado{
    background:#198754;
}

.perdido{
    background:#dc3545;
}

.padrao{
    background:#6c757d;
}

.info{
    margin-top:15px;
    line-height:1.8;
}

.actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-top:15px;
}

.btn{
    display:inline-block;
    padding:10px 16px;
    border:none;
    border-radius:10px;
    text-decoration:none;
    color:#fff;
    cursor:pointer;
    font-size:14px;
    transition:.2s;
}

.btn:hover{
    opacity:.9;
}

.btn-green{
    background:#25D366;
}

.btn-blue{
    background:#0d6efd;
}

.btn-orange{
    background:#fd7e14;
}

.chat{
    background:#fff;
    border-radius:14px;
    padding:15px;
    height:450px;
    overflow-y:auto;
    box-shadow:0 2px 10px rgba(0,0,0,.05);
}

.msg{
    max-width:75%;
    padding:12px;
    margin:10px 0;
    border-radius:14px;
    word-wrap:break-word;
}

.enviada{
    background:#d1e7ff;
    margin-left:auto;
}

.recebida{
    background:#e9ecef;
}

.msg small{
    display:block;
    margin-top:6px;
    opacity:.7;
    font-size:11px;
}

textarea{
    width:100%;
    min-height:110px;
    border:1px solid #ddd;
    border-radius:10px;
    padding:12px;
    resize:vertical;
    font-size:14px;
}

.quick-buttons{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-top:15px;
}

.quick-buttons button{
    border:none;
    padding:10px 14px;
    border-radius:10px;
    cursor:pointer;
    background:#e9ecef;
    transition:.2s;
}

.quick-buttons button:hover{
    background:#dfe3e7;
}

.submit-btn{
    width:100%;
    margin-top:15px;
    padding:14px;
    border:none;
    border-radius:10px;
    background:#0d6efd;
    color:#fff;
    cursor:pointer;
    font-size:15px;
    font-weight:bold;
}

.submit-btn:hover{
    opacity:.9;
}

</style>

</head>
<body>

<div class="container">

    <!-- LEAD INFO -->
    <div class="card">

        <div class="top">

            <h2>
                <?= h($lead['nome']) ?>
            </h2>

            <span class="badge <?= $statusClass ?>">
                <?= h($lead['status']) ?>
            </span>

        </div>

        <div class="info">

            <p>
                📞 <?= h($lead['telefone']) ?>
            </p>

            <p>
                🚗 <?= h($lead['marca'].' '.$lead['modelo']) ?>
            </p>

            <p>
                📅 Última interação:
                <?= h($lead['ultima_interacao'] ?? 'Sem interação') ?>
            </p>

        </div>

        <div class="actions">

            <a
                href="https://wa.me/<?= $telefoneWhatsapp ?>"
                target="_blank"
                class="btn btn-green"
            >
                💬 WhatsApp
            </a>

            <a
                href="<?= h(url('admin/vendas/marcar_venda.php?lead_id=' . (int)$lead_id)) ?>"
                class="btn btn-blue"
            >
                💰 Fechar Venda
            </a>

            <a
                href="<?= h(url('admin/crm/inbox.php?id=' . (int)$lead_id)) ?>"
                class="btn btn-orange"
            >
                ✏️ CRM / Follow-up
            </a>

        </div>

    </div>

    <!-- CHAT -->
    <div class="chat" id="chat">

        <?php while($m = mysqli_fetch_assoc($mensagens)): ?>

            <div class="msg <?= h($m['tipo']) ?>">

                <?= nl2br(h($m['mensagem'])) ?>

                <small>
                    <?= date('d/m/Y H:i', strtotime($m['criado_em'])) ?>
                </small>

            </div>

        <?php endwhile; ?>

    </div>

    <!-- ENVIAR MENSAGEM -->
    <div class="card">

        <form method="POST">

            <textarea
                name="mensagem"
                placeholder="Digite uma mensagem..."
                required
            ></textarea>

            <button
                type="submit"
                class="submit-btn"
            >
                Enviar Mensagem
            </button>

        </form>

        <!-- MENSAGENS RÁPIDAS -->
        <div class="quick-buttons">

            <button onclick="fastMsg('Olá, ainda está interessado no veículo?')">
                Follow-up
            </button>

            <button onclick="fastMsg('Tenho uma proposta especial para si hoje.')">
                Oferta
            </button>

            <button onclick="fastMsg('Posso reservar o carro para si ainda hoje?')">
                Reserva
            </button>

            <button onclick="fastMsg('Quando gostaria de agendar a visita ou test drive?')">
                Test Drive
            </button>

        </div>

    </div>

</div>

<script>

function fastMsg(text){

    document.querySelector(
        'textarea[name="mensagem"]'
    ).value = text;
}

window.onload = () => {

    const chat = document.getElementById('chat');

    chat.scrollTop = chat.scrollHeight;
};

</script>

</body>
</html>


