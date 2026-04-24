<?php
require 'session.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: Authentification.html");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: mon-compte.php?tab=danger");
    exit;
}

$uid      = $_SESSION['user_id'];
$password = $_POST['confirm_password'] ?? '';

if (empty($password)) {
    header("Location: mon-compte.php?tab=danger&error=" . urlencode("Veuillez confirmer votre mot de passe."));
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT mot_de_passe, photo_profil FROM utilisateur_ WHERE id_utilisateur = ?");
    $stmt->execute([$uid]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['mot_de_passe'])) {
        header("Location: mon-compte.php?tab=danger&error=" . urlencode("Mot de passe incorrect."));
        exit;
    }

    // Supprimer dans l'ordre pour respecter les FK
    $annonces = $pdo->prepare("SELECT id_annonce FROM annonces WHERE utilisateur_id = ?");
    $annonces->execute([$uid]);
    $ids = $annonces->fetchAll(PDO::FETCH_COLUMN);

    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("DELETE FROM favoris              WHERE annonce_id IN ($placeholders)")->execute($ids);
        $pdo->prepare("DELETE FROM avis                 WHERE annonce_id IN ($placeholders)")->execute($ids);
        $pdo->prepare("DELETE FROM candidatures         WHERE annonce_id IN ($placeholders)")->execute($ids);
        $pdo->prepare("DELETE FROM signalements_annonces WHERE annonce_id IN ($placeholders)")->execute($ids);
        $pdo->prepare("DELETE FROM annonces             WHERE utilisateur_id = ?")->execute([$uid]);
    }

    $pdo->prepare("DELETE FROM favoris      WHERE utilisateur_id = ?")->execute([$uid]);
    $pdo->prepare("DELETE FROM avis         WHERE utilisateur_id = ?")->execute([$uid]);
    $pdo->prepare("DELETE FROM candidatures WHERE utilisateur_id = ?")->execute([$uid]);
    $pdo->prepare("DELETE FROM notifications WHERE utilisateur_id = ?")->execute([$uid]);
    $pdo->prepare("DELETE FROM messagerie   WHERE emetteur_id = ? OR recepteur_id = ?")->execute([$uid, $uid]);
    $pdo->prepare("DELETE FROM signalements_annonces WHERE utilisateur_id = ?")->execute([$uid]);
    $pdo->prepare("DELETE FROM utilisateur_ WHERE id_utilisateur = ?")->execute([$uid]);

    // Supprimer la photo de profil
    if ($user['photo_profil'] && file_exists(__DIR__ . '/' . $user['photo_profil'])) {
        unlink(__DIR__ . '/' . $user['photo_profil']);
    }

    session_destroy();
    setcookie('ss_user',     '', time() - 3600, '/');
    setcookie('ss_remember', '', time() - 3600, '/');

    header("Location: Authentification.html?success=" . urlencode("Votre compte a été supprimé définitivement."));
} catch (PDOException $e) {
    header("Location: mon-compte.php?tab=danger&error=" . urlencode("Une erreur est survenue, veuillez réessayer."));
}
exit;
