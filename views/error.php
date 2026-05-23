<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
?>
<h2>❌ Erro</h2>
<p><?= $_GET['msg'] ?? '' ?></p>
<a href="javascript:history.back()">Voltar</a>