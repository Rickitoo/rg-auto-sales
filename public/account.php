<?php
require_once __DIR__ . '/../app/core/bootstrap.php';

if (is_logged_in()) {
    redirect_to(is_admin() ? 'admin/dashboard.php' : 'public/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar | RG Auto Sales</title>
    <link rel="icon" type="image/png" href="<?= h(asset('ImagensRG/logo.png')) ?>">
    <link rel="stylesheet" href="<?= h(asset('css/style.css')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body{background:#f5f7fb;color:#102033;text-align:left;padding:0}
        .auth-shell{min-height:100vh;display:grid;grid-template-columns:1fr 430px}
        .auth-brand{background:#01203f;color:#fff;padding:48px;display:flex;flex-direction:column;justify-content:space-between}
        .auth-brand img{width:130px;padding:0}
        .auth-brand h1{color:#fff;text-align:left;font-size:42px;line-height:1.08;margin:36px 0 14px}
        .auth-brand p{color:#d8e8f8;max-width:620px;line-height:1.7}
        .auth-links{display:flex;gap:12px;flex-wrap:wrap;margin-top:28px}
        .auth-links a{color:#fff;border:1px solid rgba(255,255,255,.25);border-radius:10px;padding:10px 13px}
        .auth-panel{background:#fff;padding:42px;display:flex;align-items:center}
        .auth-card{width:100%}
        .auth-card h2{text-align:left;color:#102033;font-size:28px;margin-bottom:8px}
        .auth-card p{color:#667085;margin-bottom:24px}
        .tabs{display:grid;grid-template-columns:1fr 1fr;background:#eef2f7;border-radius:12px;padding:4px;margin-bottom:22px}
        .tabs button{border:0;border-radius:9px;background:transparent;padding:11px;font-weight:700;cursor:pointer;color:#344054}
        .tabs button.active{background:#01203f;color:#fff}
        .form{display:none}
        .form.active{display:block}
        .field{display:flex;flex-direction:column;gap:7px;margin-bottom:14px}
        .field label{font-weight:700;color:#344054;font-size:14px}
        .field input{border:1px solid #d0d5dd;border-radius:10px;padding:12px 13px;font:inherit}
        .auth-btn{width:100%;border:0;border-radius:10px;padding:13px;background:#00aeef;color:#fff;font-weight:800;cursor:pointer}
        .auth-btn:hover{background:#01203f}
        .message{display:none;margin-bottom:14px;padding:11px 13px;border-radius:10px;font-weight:700}
        .message.ok{display:block;background:#ecfdf3;color:#027a48}
        .message.error{display:block;background:#fef3f2;color:#b42318}
        .small-note{font-size:13px;color:#667085;margin-top:14px}
        @media(max-width:900px){.auth-shell{grid-template-columns:1fr}.auth-brand{padding:28px}.auth-panel{padding:28px}.auth-brand h1{font-size:32px}}
    </style>
</head>
<body>
<main class="auth-shell">
    <section class="auth-brand">
        <div>
            <a href="<?= h(public_url('index.php')) ?>">
                <img src="<?= h(asset('ImagensRG/logo.png')) ?>" alt="RG Auto Sales">
            </a>
            <h1>Venda mais carros com controlo do primeiro contacto ao fecho.</h1>
            <p>Entre para gerir leads, test drives, propostas, stock, vendas e follow-ups da RG Auto Sales num único painel.</p>
            <div class="auth-links">
                <a href="<?= h(public_url('index.php')) ?>">Site</a>
                <a href="<?= h(public_url('products.php')) ?>">Carros</a>
                <a href="<?= h(public_url('test_drive.php')) ?>">Test Drive</a>
            </div>
        </div>
        <p class="small-note">Acesso administrativo requer conta com perfil admin.</p>
    </section>

    <section class="auth-panel">
        <div class="auth-card">
            <h2>Aceder à conta</h2>
            <p>Use o seu email ou nome de utilizador.</p>

            <div id="message" class="message"></div>

            <div class="tabs" role="tablist">
                <button id="loginTab" class="active" type="button">Login</button>
                <button id="registerTab" type="button">Registo</button>
            </div>

            <form id="loginForm" class="form active" method="post">
                <?= csrf_input() ?>
                <?= public_honeypot_input() ?>
                <div class="field">
                    <label for="login_username">Email ou utilizador</label>
                    <input id="login_username" name="username" autocomplete="username" required>
                </div>
                <div class="field">
                    <label for="login_password">Senha</label>
                    <input id="login_password" name="password" type="password" autocomplete="current-password" required>
                </div>
                <button class="auth-btn" type="submit">Entrar</button>
            </form>

            <form id="registerForm" class="form" method="post">
                <?= csrf_input() ?>
                <?= public_honeypot_input() ?>
                <div class="field">
                    <label for="reg_username">Nome</label>
                    <input id="reg_username" name="username" autocomplete="name" required>
                </div>
                <div class="field">
                    <label for="reg_email">Email</label>
                    <input id="reg_email" name="email" type="email" autocomplete="email" required>
                </div>
                <div class="field">
                    <label for="reg_password">Senha</label>
                    <input id="reg_password" name="password" type="password" autocomplete="new-password" minlength="6" required>
                </div>
                <button class="auth-btn" type="submit">Criar conta</button>
            </form>
        </div>
    </section>
</main>

<script>
const loginTab = document.getElementById('loginTab');
const registerTab = document.getElementById('registerTab');
const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');
const message = document.getElementById('message');

function showMessage(type, text) {
    message.className = 'message ' + type;
    message.textContent = text;
}

function setTab(tab) {
    const register = tab === 'register';
    loginTab.classList.toggle('active', !register);
    registerTab.classList.toggle('active', register);
    loginForm.classList.toggle('active', !register);
    registerForm.classList.toggle('active', register);
    message.className = 'message';
    message.textContent = '';
}

loginTab.addEventListener('click', () => setTab('login'));
registerTab.addEventListener('click', () => setTab('register'));

loginForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const response = await fetch('<?= h(url('auth/processa_login.php')) ?>', {method: 'POST', body: new FormData(loginForm)});
    const data = await response.json().catch(() => ({status: 'erro', message: 'Resposta invalida do servidor.'}));

    if (data.status === 'ok') {
        window.location.href = data.redirect;
        return;
    }

    showMessage('error', data.message || 'Login invalido.');
});

registerForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const response = await fetch('<?= h(url('auth/processa_registo.php')) ?>', {method: 'POST', body: new FormData(registerForm)});
    const data = await response.json().catch(() => ({status: 'erro', message: 'Resposta invalida do servidor.'}));

    if (data.status === 'ok') {
        registerForm.reset();
        setTab('login');
        showMessage('ok', 'Conta criada. Agora pode iniciar sessao.');
        return;
    }

    showMessage('error', data.message || 'Nao foi possivel criar a conta.');
});
</script>
</body>
</html>
