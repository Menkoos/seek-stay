<?php
session_start();
session_destroy();
setcookie('ss_user', '', time() - 3600, '/');
header("Location: Authentification.php");
exit;
