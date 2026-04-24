<?php
/**
 * gestion_annonce.php
 * ────────────────────
 * Endpoint pour que le propriétaire d'une annonce puisse :
 *   - la masquer (statut 'actif' → 'inactif') : locataire trouvé
 *   - la republier (statut 'inactif' → 'actif') : location terminée
 *   - la supprimer (soft delete : statut → 'archive') : suppression définitive
 *
 * Vérifie systématiquement que l'utilisateur connecté est bien propriétaire
 * de l'annonce avant toute opération.
 */

require 'session.php';

// ── Authentification ──────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header("Location: Authentification.html?error=" . urlencode("Connectez-vous pour gérer vos annonces."));
    exit;
}

if (($_SESSION['role_type'] ?? '') !== 'proprietaire') {
    header("Location: mon-compte.php?error=" . urlencode("Seuls les propriétaires peuvent gérer des annonces."));
    exit;
}

// ── Méthode ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: mon-compte.php?tab=annonces');
    exit;
}

// ── Récupération des paramètres ───────────────────────────────
$action      = $_POST['action']      ?? '';
$id_annonce  = trim($_POST['id_annonce'] ?? '');
$utilisateur = $_SESSION['user_id'];

// Actions autorisées
$actionsValides = ['masquer', 'republier', 'supprimer'];
if (!in_array($action, $actionsValides, true) || $id_annonce === '') {
    header("Location: mon-compte.php?tab=annonces&error=" . urlencode("Requête invalide."));
    exit;
}

// ── Vérifier que l'annonce existe ET appartient à l'utilisateur ──
try {
    $stmt = $pdo->prepare("
        SELECT id_annonce, utilisateur_id, statut
        FROM annonces
        WHERE id_annonce = ?
        LIMIT 1
    ");
    $stmt->execute([$id_annonce]);
    $annonce = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    header("Location: mon-compte.php?tab=annonces&error=" . urlencode("Erreur base de données."));
    exit;
}

if (!$annonce) {
    header("Location: mon-compte.php?tab=annonces&error=" . urlencode("Annonce introuvable."));
    exit;
}

// ⚠️ Vérification cruciale : seul le propriétaire peut agir sur son annonce
if ($annonce['utilisateur_id'] !== $utilisateur) {
    header("Location: mon-compte.php?tab=annonces&error=" . urlencode("Vous n'avez pas les droits sur cette annonce."));
    exit;
}

// Déjà archivée → on ne fait rien (protection contre actions répétées)
if ($annonce['statut'] === 'archive') {
    header("Location: mon-compte.php?tab=annonces&error=" . urlencode("Cette annonce a déjà été supprimée."));
    exit;
}

// ── Exécution de l'action ─────────────────────────────────────
try {
    switch ($action) {

        case 'masquer':
            // Active → Inactive (masquée des recherches, visible dans "Mes annonces")
            if ($annonce['statut'] !== 'actif') {
                header("Location: mon-compte.php?tab=annonces&error=" . urlencode("Cette annonce est déjà masquée."));
                exit;
            }
            $stmt = $pdo->prepare("UPDATE annonces SET statut = 'inactif' WHERE id_annonce = ? AND utilisateur_id = ?");
            $stmt->execute([$id_annonce, $utilisateur]);
            $msg = "Annonce masquée. Elle n'apparaît plus dans les recherches.";
            break;

        case 'republier':
            // Inactive → Active (réaffichée publiquement)
            if ($annonce['statut'] !== 'inactif') {
                header("Location: mon-compte.php?tab=annonces&error=" . urlencode("Cette annonce est déjà active."));
                exit;
            }
            $stmt = $pdo->prepare("UPDATE annonces SET statut = 'actif' WHERE id_annonce = ? AND utilisateur_id = ?");
            $stmt->execute([$id_annonce, $utilisateur]);
            $msg = "Annonce republiée ! Elle est à nouveau visible publiquement.";
            break;

        case 'supprimer':
            // Soft delete : on passe en 'archive'. Les images restent sur le disque
            // (ne pas casser d'éventuelles références depuis la messagerie ou les favoris).
            $stmt = $pdo->prepare("UPDATE annonces SET statut = 'archive' WHERE id_annonce = ? AND utilisateur_id = ?");
            $stmt->execute([$id_annonce, $utilisateur]);
            $msg = "Annonce supprimée.";
            break;
    }

    header("Location: mon-compte.php?tab=annonces&success=" . urlencode($msg));
    exit;

} catch (PDOException $e) {
    header("Location: mon-compte.php?tab=annonces&error=" . urlencode("Erreur lors de l'opération."));
    exit;
}
