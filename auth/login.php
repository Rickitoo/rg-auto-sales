<?php
require_once __DIR__ . '/../app/core/bootstrap.php';

$next = $_GET['next'] ?? '';
$safeNext = is_string($next) && str_starts_with($next, app_base_url() . '/') ? $next : '';

if (is_logged_in()) {
    if ($safeNext !== '') {
        header('Location: ' . $safeNext);
        exit;
    }

    redirect_to(is_admin() ? 'admin/dashboard.php' : 'public/dashboard.php');
}
?>
<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | RG Auto Sales</title>
    <link rel="icon" type="image/png" href="<?= h(asset('ImagensRG/logo.png')) ?>">
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;font-family:Arial,sans-serif;background:#f4f7fb;color:#102033;display:grid;place-items:center;padding:24px}
        .login{width:min(420px,100%);background:#fff;border-radius:16px;padding:30px;box-shadow:0 18px 50px rgba(16,24,40,.12)}
        .logo{display:block;width:104px;margin:0 auto 18px}
        h1{margin:0 0 8px;text-align:center;font-size:26px;color:#01203f}
        p{margin:0 0 22px;text-align:center;color:#667085}
        label{display:block;font-weight:700;margin:14px 0 7px}
        input{width:100%;border:1px solid #d0d5dd;border-radius:10px;padding:12px;font:inherit}
        button{width:100%;border:0;border-radius:10px;padding:13px;margin-top:18px;background:#00aeef;color:#fff;font-weight:800;cursor:pointer}
        button:hover{background:#01203f}
        .msg{display:none;margin-top:14px;padding:11px;border-radius:10px;font-weight:700}
        .msg.error{display:block;background:#fef3f2;color:#b42318}
        .links{display:flex;justify-content:center;gap:12px;margin-top:18px;font-size:14px}
        .links a{color:#01203f;text-decoration:none;font-weight:700}
    </style>
</head>
<body>
    <main class="login">
        <img class="logo" src="<?= h(asset('ImagensRG/logo.png')) ?>" alt="RG Auto Sales">
        <h1>Entrar no sistema</h1>
        <p>Acesso ao painel RG Auto Sales</p>

        <form id="loginForm" method="post">
            <input type="hidden" name="next" value="<?= h($safeNext) ?>">

            <label for="username">Email ou utilizador</label>
            <input id="username" name="username" autocomplete="username" required>

            <label for="password">Senha</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>

            <button type="submit">Entrar</button>
            <div id="message" class="msg"></div>
        </form>

        <div class="links">
            <a href="<?= h(public_url('index.php')) ?>">Site público</a>
            <a href="<?= h(public_url('account.php')) ?>">Criar conta</a>
        </div>
    </main>

    <script>
    const form = document.getElementById('loginForm');
    const message = document.getElementById('message');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        message.className = 'msg';
        message.textContent = '';

        const response = await fetch('<?= h(url('auth/processa_login.php')) ?>', {
            method: 'POST',
            body: new FormData(form)
        });

        const data = await response.json().catch(() => ({
            status: 'erro',
            message: 'Resposta inválida do servidor.'
        }));

        if (data.status === 'ok') {
            window.location.href = data.redirect;
            return;
        }

        message.className = 'msg error';
        message.textContent = data.message || 'Login inválido.';
    });
    </script>
</body>
</html>
