<?php
session_start();
session_unset();
session_destroy();
header("Location: /RG_AUTO_SALES/login.php");
exit;
