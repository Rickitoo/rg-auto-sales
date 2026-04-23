<?php

// Verifica login
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header("Location: /RG_AUTO_SALES/login.php");
    exit();
}
?>