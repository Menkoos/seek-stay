<?php
// Connexion à la BDD
require 'config.php';

// Accepte uniquement les soumissions POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Publier.html');
    exit;
}

// ===== VALIDATION DES CHAMPS TEXTE =====
$champs = ['adresse', 'ville', 'code_postal', 'prix', 'description', 'superficie', 'type_immeuble', 'type_offre'];
foreach ($champs as $champ) {
    if (empty(trim($_POST[$champ] ?? ''))) {
        header('Location: Publier.html?erreur=champ_manquant&champ=' . $champ);
        exit;
    }
}

// ===== DOSSIER DE STOCKAGE DES IMAGES =====
// Les images uploadées sont sauvegardées dans img/annonces/
$dossier = __DIR__ . '/img/annonces/';
if (!is_dir($dossier)) {
    mkdir($dossier, 0755, true);
}

$indexPrincipale = intval($_POST['image_principale_index'] ?? 0);
$imagePrincipale = null;
$listeImages     = [];

// ===== UPLOAD DES IMAGES =====
if (!empty($_FILES['images']['name'][0])) {
    foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {

        // Ignore les fichiers en erreur
        if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;

        // Vérifie que c'est bien une image
        $typeAutorise = ['image/jpeg', 'image/png', 'image/webp'];
        $typeFichier  = mime_content_type($tmp);
        if (!in_array($typeFichier, $typeAutorise)) continue;

        // Génère un nom unique pour éviter les conflits
        $extension = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
        $nomFichier = uniqid('annonce_') . '.' . strtolower($extension);

        // Déplace le fichier dans le dossier de destination
        move_uploaded_file($tmp, $dossier . $nomFichier);

        $cheminRelatif = 'img/annonces/' . $nomFichier;
        $listeImages[] = $cheminRelatif;

        // La première image de l'index choisi devient l'image principale
        if ($i === $indexPrincipale) {
            $imagePrincipale = $cheminRelatif;
        }
    }

    // Si l'index principale n'a pas matché (ex: suppression), prend la 1ère image
    if ($imagePrincipale === null && !empty($listeImages)) {
        $imagePrincipale = $listeImages[0];
    }
}

// ===== INSERTION EN BASE DE DONNÉES =====
// liste_images est stocké en JSON (tableau de chemins)
$stmt = $pdo->prepare("
    INSERT INTO annonces
        (id_annonce, adresse, ville, code_postal, prix, description, superficie, type_immeuble, type_offre, image_principale, liste_images, date_publication, statut)
    VALUES
        (UUID(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'actif')
");

$stmt->execute([
    trim($_POST['adresse']),
    trim($_POST['ville']),
    trim($_POST['code_postal']),
    floatval($_POST['prix']),
    trim($_POST['description']),
    floatval($_POST['superficie']),
    $_POST['type_immeuble'],
    $_POST['type_offre'],
    $imagePrincipale,
    json_encode($listeImages)
]);

// ===== REDIRECTION APRÈS SUCCÈS =====
header('Location: Publier.html?succes=1');
exit;
?>
