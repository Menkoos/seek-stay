<?php
session_start();
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: Authentification.html?tab=forgot");
    exit;
}

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: Authentification.html?tab=forgot&error=" . urlencode("Adresse email invalide."));
    exit;
}

$stmt = $pdo->prepare("SELECT id_utilisateur FROM utilisateur_ WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->fetch()) {
    $token   = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $pdo->prepare("UPDATE utilisateur_ SET reset_token = ?, reset_expires = ? WHERE email = ?")
        ->execute([$token, $expires, $email]);

    // Pour activer l'envoi d'email : configurer sendmail dans php.ini (XAMPP)
    $lien = "http://localhost/Seek-Stay-website/reset-password.php?token=" . $token;
    // mail($email, "Réinitialisation — Seek & Stay", "Cliquez ici pour réinitialiser : " . $lien);
}

header("Location: Authentification.html?tab=forgot&success=" . urlencode("Si cette adresse est connue, un lien de réinitialisation vous a été envoyé."));
exit;
