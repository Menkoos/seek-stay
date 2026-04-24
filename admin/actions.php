<?php
require __DIR__ . '/_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$action = $_POST['action'] ?? '';
$id     = trim($_POST['id'] ?? '');
$retour = $_POST['retour'] ?? '';

try {
    switch ($action) {

        // ── Signalements ──
        case 'signalement_statut':
            $nouveauStatut = in_array($_POST['nouveau_statut'] ?? '', ['traite','rejete','en_attente']) ? $_POST['nouveau_statut'] : null;
            if ($id && $nouveauStatut) {
                $pdo->prepare("UPDATE signalements SET statut = ? WHERE id_signalement = ?")
                    ->execute([$nouveauStatut, $id]);
            }
            header('Location: signalements.php?succes=' . urlencode('Signalement mis à jour.'));
            exit;

        // ── Annonces ──
        case 'annonce_statut':
            $nouveauStatut = in_array($_POST['nouveau_statut'] ?? '', ['actif','inactif','archive']) ? $_POST['nouveau_statut'] : null;
            if ($id && $nouveauStatut) {
                $pdo->prepare("UPDATE annonces SET statut = ? WHERE id_annonce = ?")
                    ->execute([$nouveauStatut, $id]);
            }
            header('Location: ' . ($retour ?: 'annonces.php') . '?succes=' . urlencode('Statut modifié.'));
            exit;

        case 'annonce_supprimer':
            if ($id) {
                // Récupérer les images pour les supprimer du disque
                $s = $pdo->prepare("SELECT image_principale, liste_images FROM annonces WHERE id_annonce = ?");
                $s->execute([$id]);
                if ($row = $s->fetch()) {
                    $images = json_decode($row['liste_images'] ?? '[]', true) ?: [];
                    if (!empty($row['image_principale'])) $images[] = $row['image_principale'];
                    foreach ($images as $img) {
                        $p = __DIR__ . '/../' . $img;
                        if (file_exists($p)) @unlink($p);
                    }
                }
                // Supprimer les signalements liés
                $pdo->prepare("DELETE FROM signalements WHERE cible_type='annonce' AND cible_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM annonces WHERE id_annonce = ?")->execute([$id]);
            }
            header('Location: ' . ($retour ?: 'annonces.php') . '?succes=' . urlencode('Annonce supprimée.'));
            exit;

        // ── Utilisateurs ──
        case 'user_toggle_admin':
            if ($id && $id !== $_SESSION['user_id']) {
                $pdo->prepare("UPDATE utilisateur_ SET is_admin = 1 - is_admin WHERE id_utilisateur = ?")
                    ->execute([$id]);
            }
            header('Location: users.php?succes=' . urlencode('Rôle admin mis à jour.'));
            exit;

        case 'user_supprimer':
            if ($id && $id !== $_SESSION['user_id']) {
                // Supprimer photo de profil
                $s = $pdo->prepare("SELECT photo_profil FROM utilisateur_ WHERE id_utilisateur = ?");
                $s->execute([$id]);
                if ($photo = $s->fetchColumn()) {
                    $p = __DIR__ . '/../' . $photo;
                    if (file_exists($p)) @unlink($p);
                }
                // Supprimer données liées
                $pdo->prepare("DELETE FROM messagerie   WHERE emetteur_id = ? OR recepteur_id = ?")->execute([$id, $id]);
                $pdo->prepare("DELETE FROM signalements WHERE signaleur_id = ? OR (cible_type='utilisateur' AND cible_id = ?)")->execute([$id, $id]);
                // Annonces + images
                $s = $pdo->prepare("SELECT id_annonce, image_principale, liste_images FROM annonces WHERE utilisateur_id = ?");
                $s->execute([$id]);
                foreach ($s->fetchAll() as $a) {
                    $imgs = json_decode($a['liste_images'] ?? '[]', true) ?: [];
                    if (!empty($a['image_principale'])) $imgs[] = $a['image_principale'];
                    foreach ($imgs as $img) {
                        $p = __DIR__ . '/../' . $img;
                        if (file_exists($p)) @unlink($p);
                    }
                    $pdo->prepare("DELETE FROM signalements WHERE cible_type='annonce' AND cible_id = ?")->execute([$a['id_annonce']]);
                }
                $pdo->prepare("DELETE FROM annonces WHERE utilisateur_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM utilisateur_ WHERE id_utilisateur = ?")->execute([$id]);
            }
            header('Location: users.php?succes=' . urlencode('Utilisateur supprimé.'));
            exit;

        default:
            header('Location: dashboard.php');
            exit;
    }
} catch (PDOException $e) {
    header('Location: dashboard.php?erreur=' . urlencode('Erreur DB: ' . $e->getMessage()));
    exit;
}
