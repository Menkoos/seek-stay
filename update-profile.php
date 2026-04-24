<?php
require 'session.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: Authentification.html");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: mon-compte.php");
    exit;
}

$uid       = $_SESSION['user_id'];
$nom       = trim($_POST['nom']       ?? '');
$lastname  = trim($_POST['lastname']  ?? '');
$telephone = trim($_POST['telephone'] ?? '');
$bio       = trim($_POST['bio']       ?? '');
$role_type = in_array($_POST['role_type'] ?? '', ['proprietaire','loueur'])
             ? $_POST['role_type'] : 'loueur';

if (empty($nom) || empty($lastname)) {
    header("Location: mon-compte.php?tab=profil&error=" . urlencode("Le nom et le prénom sont obligatoires."));
    exit;
}

$photo_path = null;
if (!empty($_FILES['photo']['name'])) {
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    $finfo   = new finfo(FILEINFO_MIME_TYPE);
    $mime    = $finfo->file($_FILES['photo']['tmp_name']);

    if (!in_array($mime, $allowed)) {
        header("Location: mon-compte.php?tab=profil&error=" . urlencode("Format d'image non supporté (JPG, PNG, GIF, WEBP uniquement)."));
        exit;
    }
    if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
        header("Location: mon-compte.php?tab=profil&error=" . urlencode("L'image ne doit pas dépasser 2 Mo."));
        exit;
    }

    $ext      = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $filename = $uid . '_' . time() . '.' . strtolower($ext);
    $dir      = __DIR__ . '/uploads/profil/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $dir . $filename)) {
        header("Location: mon-compte.php?tab=profil&error=" . urlencode("Impossible d'enregistrer la photo."));
        exit;
    }
    $photo_path = 'uploads/profil/' . $filename;

    // Supprimer l'ancienne photo
    $old = $pdo->prepare("SELECT photo_profil FROM utilisateur_ WHERE id_utilisateur = ?");
    $old->execute([$uid]);
    $oldPhoto = $old->fetchColumn();
    if ($oldPhoto && file_exists(__DIR__ . '/' . $oldPhoto)) {
        unlink(__DIR__ . '/' . $oldPhoto);
    }
}

try {
    if ($photo_path) {
        $pdo->prepare("UPDATE utilisateur_ SET nom=?, lastname=?, telephone=?, bio=?, role_type=?, photo_profil=? WHERE id_utilisateur=?")
            ->execute([$nom, $lastname, $telephone, $bio, $role_type, $photo_path, $uid]);
    } else {
        $pdo->prepare("UPDATE utilisateur_ SET nom=?, lastname=?, telephone=?, bio=?, role_type=? WHERE id_utilisateur=?")
            ->execute([$nom, $lastname, $telephone, $bio, $role_type, $uid]);
    }

    $_SESSION['nom']       = $nom;
    $_SESSION['lastname']  = $lastname;
    $_SESSION['role_type'] = $role_type;
    setcookie('ss_user', $nom, time() + 86400 * 7, '/');
    setcookie('ss_role', $role_type, time() + 86400 * 7, '/');

    header("Location: mon-compte.php?tab=profil&success=" . urlencode("Profil mis à jour avec succès."));
} catch (PDOException $e) {
    header("Location: mon-compte.php?tab=profil&error=" . urlencode("Une erreur est survenue, veuillez réessayer."));
}
exit;
