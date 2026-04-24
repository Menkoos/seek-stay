<?php
require 'session.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: Authentification.html?error=" . urlencode("Connectez-vous pour publier une annonce."));
    exit;
}
if (($_SESSION['role_type'] ?? '') !== 'proprietaire') {
    header("Location: Accueil.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Publier.php');
    exit;
}

$utilisateur_id = $_SESSION['user_id'];

// ─── Détection mode édition ────────────────────────────────────
// Si id_annonce est fourni en POST, c'est une modification ;
// sinon c'est une création.
$idEdit   = trim($_POST['id_annonce'] ?? '');
$isEdit   = ($idEdit !== '');
$annonceActuelle = null;

if ($isEdit) {
    // Vérifier que l'annonce existe et appartient au propriétaire connecté
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM annonces
            WHERE id_annonce = ? AND utilisateur_id = ? AND statut != 'archive'
            LIMIT 1
        ");
        $stmt->execute([$idEdit, $utilisateur_id]);
        $annonceActuelle = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $annonceActuelle = null; }

    if (!$annonceActuelle) {
        header("Location: mon-compte.php?tab=annonces&error=" . urlencode("Annonce introuvable ou vous n'en êtes pas propriétaire."));
        exit;
    }
}

/**
 * Redirige vers Publier.php (ou Publier.php?id=... en mode édition)
 * en préservant les données saisies.
 */
function redirigerAvecFormulaire(array $params): void {
    global $isEdit, $idEdit;
    $save = $_POST;
    unset($save['image_principale_index']);
    $_SESSION['form_data_annonce'] = $save;
    if ($isEdit) {
        $params['id'] = $idEdit;
    }
    header('Location: Publier.php?' . http_build_query($params));
    exit;
}

// ===== VALIDATION DES CHAMPS TEXTE =====
$champs = ['adresse', 'ville', 'code_postal', 'prix', 'description', 'superficie', 'type_immeuble', 'type_offre'];
foreach ($champs as $champ) {
    if (empty(trim($_POST[$champ] ?? ''))) {
        redirigerAvecFormulaire(['erreur' => 'champ_manquant', 'champ' => $champ]);
    }
}

// Description : 100 mots minimum
$descriptionTxt = trim($_POST['description']);
$nbMots = $descriptionTxt === '' ? 0 : count(preg_split('/\s+/', $descriptionTxt));
if ($nbMots < 100) {
    redirigerAvecFormulaire(['erreur' => 'description_trop_courte', 'mots' => $nbMots]);
}

// ===== DOSSIER DE STOCKAGE DES IMAGES =====
$dossier = __DIR__ . '/img/annonces/';
if (!is_dir($dossier)) {
    mkdir($dossier, 0755, true);
}

$indexPrincipale = intval($_POST['image_principale_index'] ?? 0);
$imagePrincipale = null;
$listeImages     = [];

// ===== UPLOAD DES NOUVELLES IMAGES (optionnel en édition) =====
$nouvellesImagesUploadees = false;
if (!empty($_FILES['images']['name'][0])) {
    $nouvellesImagesUploadees = true;
    foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {

        if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;

        // Vérifie que c'est bien une image
        $typeAutorise = ['image/jpeg', 'image/png', 'image/webp'];
        $typeFichier  = mime_content_type($tmp);
        if (!in_array($typeFichier, $typeAutorise)) continue;

        $extension  = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
        $nomFichier = uniqid('annonce_') . '.' . strtolower($extension);
        move_uploaded_file($tmp, $dossier . $nomFichier);

        $cheminRelatif = 'img/annonces/' . $nomFichier;
        $listeImages[] = $cheminRelatif;

        if ($i === $indexPrincipale) {
            $imagePrincipale = $cheminRelatif;
        }
    }

    if ($imagePrincipale === null && !empty($listeImages)) {
        $imagePrincipale = $listeImages[0];
    }
}

// ===== GESTION DES IMAGES SELON LE MODE =====
if ($isEdit) {
    if ($nouvellesImagesUploadees) {
        // De nouvelles photos ont été envoyées : minimum 3 requises
        if (count($listeImages) < 3) {
            $nbUp = count($listeImages);
            // Supprimer les images qui viennent juste d'être uploadées
            foreach ($listeImages as $img) {
                $p = __DIR__ . '/' . $img;
                if (file_exists($p)) @unlink($p);
            }
            redirigerAvecFormulaire(['erreur' => 'photos_insuffisantes', 'nb' => $nbUp]);
        }

        // Supprimer les anciennes images du disque (elles seront remplacées)
        $anciennesImages = json_decode($annonceActuelle['liste_images'] ?? '[]', true) ?: [];
        if (!empty($annonceActuelle['image_principale'])) {
            $anciennesImages[] = $annonceActuelle['image_principale'];
        }
        foreach (array_unique($anciennesImages) as $img) {
            $p = __DIR__ . '/' . $img;
            if (file_exists($p)) @unlink($p);
        }
    } else {
        // Aucune nouvelle photo : on garde les anciennes
        $imagePrincipale = $annonceActuelle['image_principale'];
        $listeImages     = json_decode($annonceActuelle['liste_images'] ?? '[]', true) ?: [];
    }
} else {
    // Mode création : minimum 3 photos obligatoire
    if (count($listeImages) < 3) {
        $nbUp = count($listeImages);
        foreach ($listeImages as $img) {
            $p = __DIR__ . '/' . $img;
            if (file_exists($p)) @unlink($p);
        }
        redirigerAvecFormulaire(['erreur' => 'photos_insuffisantes', 'nb' => $nbUp]);
    }
}

// ===== PRÉPARATION DES CHAMPS =====
$equipements  = $_POST['equipements'] ?? [];
$type_proprio = in_array($_POST['type_proprio'] ?? '', ['particulier','agence']) ? $_POST['type_proprio'] : 'particulier';
$apl_accepte  = !empty($_POST['apl_accepte']) ? 1 : 0;

$dureesValides   = ['1 mois','3 mois','6 mois','9 mois','1 an','2 ans'];
$duree_min       = in_array($_POST['duree_min'] ?? '', $dureesValides) ? $_POST['duree_min'] : null;
$date_disponible = trim($_POST['date_disponible'] ?? '');
$date_disponible = ($date_disponible && strtotime($date_disponible)) ? $date_disponible : null;

$lat = is_numeric($_POST['lat'] ?? null) ? floatval($_POST['lat']) : null;
$lng = is_numeric($_POST['lng'] ?? null) ? floatval($_POST['lng']) : null;

$meuble           = (string)($_POST['meuble'] ?? '1') === '0' ? 0 : 1;
$nb_pieces        = !empty($_POST['nb_pieces']) ? intval($_POST['nb_pieces']) : null;
$charges_incluses = !empty($_POST['charges_incluses']) ? 1 : 0;
$animaux_acceptes = !empty($_POST['animaux_acceptes']) ? 1 : 0;
$fumeur_autorise  = !empty($_POST['fumeur_autorise'])  ? 1 : 0;
$accessible_pmr   = !empty($_POST['accessible_pmr'])   ? 1 : 0;

// ===== INSERT (création) OU UPDATE (modification) =====
if ($isEdit) {
    $stmt = $pdo->prepare("
        UPDATE annonces SET
            adresse          = ?,
            ville            = ?,
            code_postal      = ?,
            prix             = ?,
            description      = ?,
            superficie       = ?,
            type_immeuble    = ?,
            type_offre       = ?,
            equipements      = ?,
            type_proprio     = ?,
            apl_accepte      = ?,
            date_disponible  = ?,
            duree_min        = ?,
            lat              = ?,
            lng              = ?,
            meuble           = ?,
            nb_pieces        = ?,
            charges_incluses = ?,
            animaux_acceptes = ?,
            fumeur_autorise  = ?,
            accessible_pmr   = ?,
            image_principale = ?,
            liste_images     = ?
        WHERE id_annonce = ? AND utilisateur_id = ?
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
        json_encode($equipements),
        $type_proprio,
        $apl_accepte,
        $date_disponible,
        $duree_min,
        $lat,
        $lng,
        $meuble,
        $nb_pieces,
        $charges_incluses,
        $animaux_acceptes,
        $fumeur_autorise,
        $accessible_pmr,
        $imagePrincipale,
        json_encode($listeImages),
        $idEdit,
        $utilisateur_id
    ]);

    unset($_SESSION['form_data_annonce']);
    header('Location: mon-compte.php?tab=annonces&success=' . urlencode('Annonce modifiée avec succès.'));
    exit;

} else {
    // Mode création classique
    $stmt = $pdo->prepare("
        INSERT INTO annonces
            (id_annonce, utilisateur_id, adresse, ville, code_postal, prix, description, superficie,
             type_immeuble, type_offre, equipements, type_proprio, apl_accepte,
             date_disponible, duree_min, lat, lng,
             meuble, nb_pieces, charges_incluses, animaux_acceptes, fumeur_autorise, accessible_pmr,
             image_principale, liste_images, date_publication, statut)
        VALUES
            (UUID(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'actif')
    ");

    $stmt->execute([
        $utilisateur_id,
        trim($_POST['adresse']),
        trim($_POST['ville']),
        trim($_POST['code_postal']),
        floatval($_POST['prix']),
        trim($_POST['description']),
        floatval($_POST['superficie']),
        $_POST['type_immeuble'],
        $_POST['type_offre'],
        json_encode($equipements),
        $type_proprio,
        $apl_accepte,
        $date_disponible,
        $duree_min,
        $lat,
        $lng,
        $meuble,
        $nb_pieces,
        $charges_incluses,
        $animaux_acceptes,
        $fumeur_autorise,
        $accessible_pmr,
        $imagePrincipale,
        json_encode($listeImages)
    ]);

    unset($_SESSION['form_data_annonce']);
    header('Location: Publier.php?succes=1');
    exit;
}
?>
