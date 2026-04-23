<?php
require_once("init.php");

if (!isset($_SESSION['test'])) {
    $_SESSION['test'] = 1;
} else {
    $_SESSION['test']++;
}

echo "SESSION ID: " . session_id() . "<br>";
echo "VALOR: " . $_SESSION['test'];