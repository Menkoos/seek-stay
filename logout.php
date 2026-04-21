<?php
session_start();
require 'config.php';

if (isset($_SESSION['user_id'])) {
    try {
        $pdo->prepare("UPDATE utilisateur_ SET remember_token=NULL, remember_expires=NULL WHERE id_utilisateur=?")
            ->execute([$_SESSION['user_id']]);
    } catch (PDOException $e) {}
}

session_destroy();
setcookie('ss_user',     '', time() - 3600, '/');
setcookie('ss_remember', '', time() - 3600, '/');
header("Location: Authentification.html");
exit;
