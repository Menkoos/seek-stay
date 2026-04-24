<?php
require 'session.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: Authentification.html");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: mon-compte.php?tab=securite");
    exit;
}

$uid         = $_SESSION['user_id'];
$current     = $_POST['current_password']  ?? '';
$new_pass    = $_POST['new_password']      ?? '';
$confirm     = $_POST['confirm_password']  ?? '';

if (empty($current) || empty($new_pass) || empty($confirm)) {
    header("Location: mon-compte.php?tab=securite&error=" . urlencode("Veuillez remplir tous les champs."));
    exit;
}
if (strlen($new_pass) < 8) {
    header("Location: mon-compte.php?tab=securite&error=" . urlencode("Le nouveau mot de passe doit contenir au moins 8 caractères."));
    exit;
}
if ($new_pass !== $confirm) {
    header("Location: mon-compte.php?tab=securite&error=" . urlencode("Les nouveaux mots de passe ne correspondent pas."));
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT mot_de_passe FROM utilisateur_ WHERE id_utilisateur = ?");
    $stmt->execute([$uid]);
    $hash = $stmt->fetchColumn();

    if (!$hash || !password_verify($current, $hash)) {
        header("Location: mon-compte.php?tab=securite&error=" . urlencode("Mot de passe actuel incorrect."));
        exit;
    }

    $pdo->prepare("UPDATE utilisateur_ SET mot_de_passe = ? WHERE id_utilisateur = ?")
        ->execute([password_hash($new_pass, PASSWORD_DEFAULT), $uid]);

    header("Location: mon-compte.php?tab=securite&success=" . urlencode("Mot de passe modifié avec succès."));
} catch (PDOException $e) {
    header("Location: mon-compte.php?tab=securite&error=" . urlencode("Une erreur est survenue, veuillez réessayer."));
}
exit;
