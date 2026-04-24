<?php
require_once __DIR__ . '/../session.php';

// Vérifie que l'utilisateur est connecté ET admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../Authentification.html?error=' . urlencode("Connectez-vous en tant qu'administrateur."));
    exit;
}

try {
    $s = $pdo->prepare("SELECT is_admin FROM utilisateur_ WHERE id_utilisateur = ?");
    $s->execute([$_SESSION['user_id']]);
    $isAdmin = (int)$s->fetchColumn();
} catch (PDOException $e) {
    $isAdmin = 0;
}

if (!$isAdmin) {
    header('Location: ../Accueil.php?erreur=' . urlencode("Accès administrateur requis."));
    exit;
}
