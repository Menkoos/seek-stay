<?php
require 'session.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: Authentification.html?error=" . urlencode("Connectez-vous pour publier une annonce."));
    exit;
}
if (($_SESSION['role_type'] ?? '') !== 'proprietaire') {
    header("Location: Accueil.php?erreur=" . urlencode("Seuls les propriétaires peuvent publier une annonce."));
    exit;
}
$succes = isset($_GET['succes']);
$erreur = $_GET['erreur'] ?? '';

// ── Mode édition ─────────────────────────────────────────────
// Si ?id=XXX est passé, on vérifie que l'utilisateur est bien propriétaire
// de l'annonce, puis on charge ses données pour pré-remplir le formulaire.
$idEdit       = trim($_GET['id'] ?? '');
$annonceEdit  = null;

if ($idEdit !== '') {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM annonces
            WHERE id_annonce = ? AND utilisateur_id = ? AND statut != 'archive'
            LIMIT 1
        ");
        $stmt->execute([$idEdit, $_SESSION['user_id']]);
        $annonceEdit = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $annonceEdit = null; }

    if (!$annonceEdit) {
        header("Location: mon-compte.php?tab=annonces&error=" . urlencode("Annonce introuvable ou vous n'en êtes pas propriétaire."));
        exit;
    }
}
$isEdit = (bool)$annonceEdit;

// Récupérer les données saisies précédemment (si erreur de validation)
$old = $_SESSION['form_data_annonce'] ?? [];
unset($_SESSION['form_data_annonce']);

// En mode édition : pré-remplir avec les données de l'annonce si pas de données
// de session (pas d'erreur de validation récente).
if ($isEdit && empty($old)) {
    $old = [
        'adresse'          => $annonceEdit['adresse'] ?? '',
        'ville'            => $annonceEdit['ville'] ?? '',
        'code_postal'      => $annonceEdit['code_postal'] ?? '',
        'prix'             => $annonceEdit['prix'] ?? '',
        'superficie'       => $annonceEdit['superficie'] ?? '',
        'description'      => $annonceEdit['description'] ?? '',
        'type_immeuble'    => $annonceEdit['type_immeuble'] ?? '',
        'type_offre'       => $annonceEdit['type_offre'] ?? '',
        'date_disponible'  => $annonceEdit['date_disponible'] ?? '',
        'duree_min'        => $annonceEdit['duree_min'] ?? '',
        'lat'              => $annonceEdit['lat'] ?? '',
        'lng'              => $annonceEdit['lng'] ?? '',
        'meuble'           => (string)($annonceEdit['meuble'] ?? '1'),
        'nb_pieces'        => $annonceEdit['nb_pieces'] ?? '',
        'charges_incluses' => $annonceEdit['charges_incluses'] ? '1' : '',
        'animaux_acceptes' => $annonceEdit['animaux_acceptes'] ? '1' : '',
        'fumeur_autorise'  => $annonceEdit['fumeur_autorise']  ? '1' : '',
        'accessible_pmr'   => $annonceEdit['accessible_pmr']   ? '1' : '',
        'apl_accepte'      => $annonceEdit['apl_accepte']      ? '1' : '',
        'type_proprio'     => $annonceEdit['type_proprio'] ?? 'particulier',
        'equipements'      => json_decode($annonceEdit['equipements'] ?? '[]', true) ?: [],
    ];
}

// Images existantes (mode édition uniquement)
$imagesExistantes = [];
if ($isEdit) {
    $imagesExistantes = json_decode($annonceEdit['liste_images'] ?? '[]', true) ?: [];
}

// Helpers pour pré-remplir
function oldVal($key, $default = '') {
    global $old;
    return htmlspecialchars($old[$key] ?? $default, ENT_QUOTES);
}
function oldChecked($key, $value) {
    global $old;
    $v = $old[$key] ?? null;
    return (string)$v === (string)$value ? 'checked' : '';
}
function oldSelected($key, $value) {
    global $old;
    return ($old[$key] ?? '') === $value ? 'selected' : '';
}
function oldEquip($slug) {
    global $old;
    return in_array($slug, $old['equipements'] ?? [], true) ? 'checked' : '';
}
?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <title>Seek &amp; Stay | Publier une annonce</title>
    <meta name="description" content="Publiez votre annonce de logement étudiant sur Seek &amp; Stay" />
    <link rel="icon" type="image/x-icon" href="img/flavicon.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="styles/styles.css" />
    <link href="https://api.mapbox.com/mapbox-gl-js/v3.20.0/mapbox-gl.css" rel="stylesheet" />
    <script src="https://api.mapbox.com/mapbox-gl-js/v3.20.0/mapbox-gl.js"></script>
    <style>
      #map-preview {
        height: 280px; border-radius: 12px;
        margin-top: 16px; overflow: hidden;
        border: 1px solid #e2e8f0;
      }
      .map-info {
        font-size: 12px; color: #64748b;
        margin-top: 8px; display: flex; align-items: center; gap: 6px;
      }
      .map-info.found { color: #059669; }
      .map-info.loading { color: #d97706; }
    </style>
  </head>

  <body class="page-publier">
    <header>
      <div class="flex">
        <a href="Accueil.php" class="header-logo">
          <img src="img/iconSite.png" class="header-logo" alt="logo" />
        </a>
        <ul class="header-menu">
          <li><a href="Accueil.php">Accueil</a></li>
          <li><a href="Annonces.php">Annonces</a></li>
          <li><a href="Favoris.php">Favoris</a></li>
          <li><a href="Publier.php" class="active">Publier</a></li>
          <li><a href="Contact.html">Contact</a></li>
          <li><a href="messagerie.php">Messages</a></li>
          <li><a href="mon-compte.php" style="font-weight:600;">Mon compte</a></li>
          <li><a href="FAQ.html">FAQ</a></li>
        </ul>
      </div>
    </header>

    <div class="publier-page">
      <div class="publier-container">
        <div class="publier-header">
          <?php if ($isEdit): ?>
            <h2>Modifier l'annonce</h2>
            <p>Mettez à jour les informations de votre annonce. Les modifications seront visibles immédiatement.</p>
          <?php else: ?>
            <h2>Publier une annonce</h2>
            <p>Remplissez les informations ci-dessous pour mettre votre logement en ligne.</p>
          <?php endif; ?>
        </div>

        <?php if ($succes): ?>
        <div style="background:#e6f9ee;border:1px solid #2a9d5c;color:#2a9d5c;padding:16px 20px;border-radius:12px;font-weight:600;margin-bottom:24px;">
          <?php echo $isEdit ? '✅ Votre annonce a bien été modifiée !' : '✅ Votre annonce a bien été publiée !'; ?>
        </div>
        <?php endif; ?>

        <?php if ($erreur): ?>
        <div style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:16px 20px;border-radius:12px;font-weight:500;margin-bottom:24px;">
          <?php if ($erreur === 'description_trop_courte'):
              $motsSaisi = intval($_GET['mots'] ?? 0); ?>
            ⚠️ La description doit contenir <strong>au moins 100 mots</strong>
            (vous en aviez <?php echo $motsSaisi; ?>). Décrivez le logement,
            le quartier, les transports, l'état général, les règles de vie…
          <?php elseif ($erreur === 'photos_insuffisantes'):
              $nbPhotos = intval($_GET['nb'] ?? 0); ?>
            ⚠️ Vous devez ajouter <strong>au moins 3 photos</strong> du logement
            (actuellement <?php echo $nbPhotos; ?>). Ajoutez des photos du salon,
            de la cuisine, de la chambre, de la salle de bain…
          <?php else: ?>
            ⚠️ Veuillez remplir tous les champs obligatoires.
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <form id="form-publier" action="traitement_annonce.php" method="POST" enctype="multipart/form-data" novalidate>
          <input type="hidden" id="index-principale" name="image_principale_index" value="0" />
          <?php if ($isEdit): ?>
            <input type="hidden" name="id_annonce" value="<?php echo htmlspecialchars($annonceEdit['id_annonce']); ?>" />
          <?php endif; ?>

          <!-- LOCALISATION -->
          <div class="form-card">
            <h3><i class="fa-solid fa-location-dot"></i> Localisation</h3>
            <div class="form-row full">
              <div class="form-group">
                <label for="adresse">Adresse <span class="required">*</span></label>
                <input type="text" id="adresse" name="adresse" placeholder="Ex : 12 rue des Étudiants" required value="<?php echo oldVal('adresse'); ?>" />
              </div>
            </div>
            <div class="form-row" style="margin-top:18px">
              <div class="form-group">
                <label for="ville">Ville <span class="required">*</span></label>
                <input type="text" id="ville" name="ville" placeholder="Ex : Paris" required value="<?php echo oldVal('ville'); ?>" />
              </div>
              <div class="form-group">
                <label for="code-postal">Code postal <span class="required">*</span></label>
                <input type="text" id="code-postal" name="code_postal" placeholder="Ex : 75001" maxlength="5" required value="<?php echo oldVal('code_postal'); ?>" />
              </div>
            </div>

            <div id="map-preview"></div>
            <p class="map-info" id="map-info">
              <i class="fa-solid fa-circle-info"></i>
              <span>Saisissez l'adresse ci-dessus, la position apparaîtra automatiquement sur la carte.</span>
            </p>
            <input type="hidden" id="lat" name="lat" value="<?php echo oldVal('lat'); ?>" />
            <input type="hidden" id="lng" name="lng" value="<?php echo oldVal('lng'); ?>" />
          </div>

          <!-- DISPONIBILITÉ & DURÉE -->
          <div class="form-card">
            <h3><i class="fa-solid fa-calendar-check"></i> Disponibilité &amp; Durée</h3>
            <div class="form-row">
              <div class="form-group">
                <label for="date_disponible">Disponible à partir du</label>
                <input type="date" id="date_disponible" name="date_disponible"
                       min="<?php echo date('Y-m-d'); ?>" value="<?php echo oldVal('date_disponible'); ?>" />
              </div>
              <div class="form-group">
                <label for="duree_min">Durée minimum de location</label>
                <select id="duree_min" name="duree_min"
                        style="width:100%;padding:10px 13px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;font-family:inherit;background:#fff;">
                  <option value=""        <?php echo oldSelected('duree_min',''); ?>>Non définie</option>
                  <option value="1 mois"  <?php echo oldSelected('duree_min','1 mois'); ?>>1 mois minimum</option>
                  <option value="3 mois"  <?php echo oldSelected('duree_min','3 mois'); ?>>3 mois minimum</option>
                  <option value="6 mois"  <?php echo oldSelected('duree_min','6 mois'); ?>>6 mois minimum</option>
                  <option value="9 mois"  <?php echo oldSelected('duree_min','9 mois'); ?>>9 mois (année scolaire)</option>
                  <option value="1 an"    <?php echo oldSelected('duree_min','1 an'); ?>>1 an minimum</option>
                  <option value="2 ans"   <?php echo oldSelected('duree_min','2 ans'); ?>>2 ans minimum</option>
                </select>
              </div>
            </div>
          </div>

          <!-- TYPE DE LOGEMENT -->
          <div class="form-card">
            <h3><i class="fa-solid fa-building"></i> Type de logement</h3>
            <div class="form-group" style="margin-bottom:24px">
              <label>Type d'immeuble <span class="required">*</span></label>
              <div class="type-grid">
                <div class="type-option">
                  <input type="radio" name="type_immeuble" id="ti-appartement" value="appartement" required <?php echo oldChecked('type_immeuble','appartement'); ?> />
                  <label for="ti-appartement"><i class="fa-solid fa-building"></i> Appartement</label>
                </div>
                <div class="type-option">
                  <input type="radio" name="type_immeuble" id="ti-maison" value="maison" <?php echo oldChecked('type_immeuble','maison'); ?> />
                  <label for="ti-maison"><i class="fa-solid fa-house"></i> Maison</label>
                </div>
                <div class="type-option">
                  <input type="radio" name="type_immeuble" id="ti-chambre" value="chambre" <?php echo oldChecked('type_immeuble','chambre'); ?> />
                  <label for="ti-chambre"><i class="fa-solid fa-bed"></i> Chambre</label>
                </div>
                <div class="type-option">
                  <input type="radio" name="type_immeuble" id="ti-residence" value="residence" <?php echo oldChecked('type_immeuble','residence'); ?> />
                  <label for="ti-residence"><i class="fa-solid fa-city"></i> Résidence</label>
                </div>
                <div class="type-option">
                  <input type="radio" name="type_immeuble" id="ti-studio" value="studio" <?php echo oldChecked('type_immeuble','studio'); ?> />
                  <label for="ti-studio"><i class="fa-solid fa-door-closed"></i> Studio</label>
                </div>
              </div>
            </div>
            <div class="form-group">
              <label>Type d'offre <span class="required">*</span></label>
              <div class="type-grid">
                <div class="type-option">
                  <input type="radio" name="type_offre" id="to-location" value="location" required <?php echo oldChecked('type_offre','location'); ?> />
                  <label for="to-location"><i class="fa-solid fa-key"></i> Location</label>
                </div>
                <div class="type-option">
                  <input type="radio" name="type_offre" id="to-colocation" value="colocation" <?php echo oldChecked('type_offre','colocation'); ?> />
                  <label for="to-colocation"><i class="fa-solid fa-people-group"></i> Colocation</label>
                </div>
              </div>
            </div>
          </div>

          <!-- PRIX & SURFACE -->
          <div class="form-card">
            <h3><i class="fa-solid fa-euro-sign"></i> Prix &amp; Surface</h3>
            <div class="form-row">
              <div class="form-group">
                <label for="prix">Loyer mensuel <span class="required">*</span></label>
                <div class="input-with-symbol">
                  <input type="number" id="prix" name="prix" placeholder="Ex : 650" min="0" required value="<?php echo oldVal('prix'); ?>" />
                  <span class="symbol">€</span>
                </div>
              </div>
              <div class="form-group">
                <label for="superficie">Superficie <span class="required">*</span></label>
                <div class="input-with-symbol">
                  <input type="number" id="superficie" name="superficie" placeholder="Ex : 28" min="1" required value="<?php echo oldVal('superficie'); ?>" />
                  <span class="symbol">m²</span>
                </div>
              </div>
            </div>
          </div>

          <!-- CARACTÉRISTIQUES -->
          <div class="form-card">
            <h3><i class="fa-solid fa-house-chimney-user"></i> Caractéristiques du logement</h3>
            <div class="form-row">
              <div class="form-group">
                <label for="nb_pieces">Nombre de pièces</label>
                <input type="number" id="nb_pieces" name="nb_pieces" min="1" max="20" placeholder="Ex : 2" value="<?php echo oldVal('nb_pieces'); ?>" />
              </div>
              <div class="form-group">
                <label for="meuble-select">Ameublement</label>
                <select id="meuble-select" name="meuble"
                        style="width:100%;padding:10px 13px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;font-family:inherit;background:#fff;">
                  <option value="1" <?php echo oldSelected('meuble','1'); ?>>Meublé</option>
                  <option value="0" <?php echo oldSelected('meuble','0'); ?>>Non meublé</option>
                </select>
              </div>
            </div>

            <div class="form-group" style="margin-top:18px">
              <label>Règles &amp; services</label>
              <div class="type-grid" style="grid-template-columns:repeat(auto-fill,minmax(170px,1fr))">
                <div class="type-option">
                  <input type="checkbox" name="charges_incluses" id="ch-charges" value="1" <?php echo oldChecked('charges_incluses','1'); ?> />
                  <label for="ch-charges"><i class="fa-solid fa-receipt"></i> Charges incluses</label>
                </div>
                <div class="type-option">
                  <input type="checkbox" name="animaux_acceptes" id="ch-animaux" value="1" <?php echo oldChecked('animaux_acceptes','1'); ?> />
                  <label for="ch-animaux"><i class="fa-solid fa-paw"></i> Animaux acceptés</label>
                </div>
                <div class="type-option">
                  <input type="checkbox" name="fumeur_autorise" id="ch-fumeur" value="1" <?php echo oldChecked('fumeur_autorise','1'); ?> />
                  <label for="ch-fumeur"><i class="fa-solid fa-smoking"></i> Fumeur autorisé</label>
                </div>
                <div class="type-option">
                  <input type="checkbox" name="accessible_pmr" id="ch-pmr" value="1" <?php echo oldChecked('accessible_pmr','1'); ?> />
                  <label for="ch-pmr"><i class="fa-solid fa-wheelchair"></i> Accessible PMR</label>
                </div>
              </div>
            </div>
          </div>

          <!-- ÉQUIPEMENTS & OPTIONS -->
          <div class="form-card">
            <h3><i class="fa-solid fa-list-check"></i> Équipements &amp; Options</h3>

            <div class="form-group">
              <label>Équipements disponibles</label>
              <div class="type-grid" style="grid-template-columns:repeat(auto-fill,minmax(170px,1fr))">
                <?php
                  $equips = [
                    'wifi'             => ['fa-wifi',                 'Wi-Fi'],
                    'machine_a_laver'  => ['fa-shirt',                'Lave-linge'],
                    'lave_vaisselle'   => ['fa-jug-detergent',        'Lave-vaisselle'],
                    'micro_onde'       => ['fa-square',               'Micro-ondes'],
                    'four'             => ['fa-fire',                 'Four'],
                    'frigo'             => ['fa-snowflake',            'Réfrigérateur'],
                    'cuisine_equipee'  => ['fa-kitchen-set',          'Cuisine équipée'],
                    'ustensiles'       => ['fa-utensils',             'Ustensiles cuisine'],
                    'chauffage'        => ['fa-temperature-high',     'Chauffage'],
                    'climatisation'    => ['fa-fan',                  'Climatisation'],
                    'balcon'           => ['fa-house-chimney-window', 'Balcon'],
                    'terrasse'         => ['fa-umbrella-beach',       'Terrasse'],
                    'jardin'           => ['fa-tree',                 'Jardin'],
                    'cave'             => ['fa-box-archive',          'Cave'],
                    'ascenseur'        => ['fa-elevator',             'Ascenseur'],
                    'parking'          => ['fa-square-parking',       'Parking'],
                  ];
                  foreach ($equips as $val => [$icon, $label]):
                ?>
                <div class="type-option">
                  <input type="checkbox" name="equipements[]" id="eq-<?php echo $val; ?>" value="<?php echo $val; ?>" <?php echo oldEquip($val); ?> />
                  <label for="eq-<?php echo $val; ?>"><i class="fa-solid <?php echo $icon; ?>"></i> <?php echo $label; ?></label>
                </div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="form-group" style="margin-top:20px">
              <label>Type de propriétaire <span class="required">*</span></label>
              <div class="type-grid">
                <?php $tp = $old['type_proprio'] ?? 'particulier'; ?>
                <div class="type-option">
                  <input type="radio" name="type_proprio" id="tp-particulier" value="particulier" <?php echo $tp==='particulier'?'checked':''; ?> />
                  <label for="tp-particulier"><i class="fa-solid fa-user"></i> Particulier</label>
                </div>
                <div class="type-option">
                  <input type="radio" name="type_proprio" id="tp-agence" value="agence" <?php echo $tp==='agence'?'checked':''; ?> />
                  <label for="tp-agence"><i class="fa-solid fa-building-columns"></i> Agence</label>
                </div>
              </div>
            </div>

            <div class="form-group" style="margin-top:20px">
              <div class="type-option" style="display:inline-flex">
                <input type="checkbox" name="apl_accepte" id="apl" value="1" <?php echo oldChecked('apl_accepte','1'); ?> />
                <label for="apl"><i class="fa-solid fa-hand-holding-heart"></i> APL accepté</label>
              </div>
            </div>
          </div>

          <!-- DESCRIPTION -->
          <div class="form-card">
            <h3><i class="fa-solid fa-align-left"></i> Description</h3>
            <div class="form-group">
              <label for="description">
                Description de l'annonce <span class="required">*</span>
                <span style="font-weight:400;color:#64748b;font-size:12px;margin-left:6px;">
                  (100 mots minimum)
                </span>
              </label>
              <textarea id="description" name="description" required minlength="100"
                        placeholder="Décrivez votre logement en détail : équipements, proximité des transports, état général, règles de vie, quartier, voisinage, orientation, luminosité… (100 mots minimum)"
                        maxlength="3000"><?php echo oldVal('description'); ?></textarea>
              <div class="char-counter" id="desc-counter"
                   style="display:flex;justify-content:space-between;font-size:12px;margin-top:6px;">
                <span id="desc-words-info" style="color:#dc2626;font-weight:600;">
                  <i class="fa-solid fa-circle-exclamation"></i>
                  <span id="desc-words">0</span> / 100 mots
                </span>
                <span style="color:#94a3b8;">
                  <span id="nb-chars">0</span> caractères
                </span>
              </div>
            </div>
          </div>

          <!-- IMAGES -->
          <div class="form-card">
            <h3><i class="fa-solid fa-images"></i> Photos du logement</h3>

            <?php if ($isEdit && !empty($imagesExistantes)): ?>
              <div style="margin-bottom:16px;padding:14px;background:#f1f5f9;border-radius:8px;border-left:3px solid #244676;">
                <p style="font-size:13px;margin:0 0 10px 0;color:#1e293b;">
                  <i class="fa-solid fa-circle-info"></i>
                  <strong>Photos actuelles</strong> (<?php echo count($imagesExistantes); ?>) :
                </p>
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                  <?php foreach ($imagesExistantes as $imgPath): ?>
                    <?php if (file_exists(__DIR__ . '/' . $imgPath)): ?>
                      <img src="<?php echo htmlspecialchars($imgPath); ?>" alt=""
                           style="width:90px;height:90px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0;" />
                    <?php endif; ?>
                  <?php endforeach; ?>
                </div>
                <p style="font-size:12px;color:#64748b;margin:10px 0 0 0;">
                  Uploadez de nouvelles photos ci-dessous pour les <strong>remplacer</strong>,
                  ou laissez vide pour <strong>garder</strong> les photos actuelles.
                </p>
              </div>
            <?php endif; ?>

            <div class="upload-zone" id="upload-zone" onclick="document.getElementById('input-images').click()">
              <i class="fa-solid fa-cloud-arrow-up"></i>
              <p><strong>Cliquez pour ajouter des photos</strong></p>
              <p class="upload-hint">
                <?php if ($isEdit): ?>
                  <strong style="color:#244676;">Optionnel en modification</strong> — si vous ajoutez des photos, minimum 3 — JPG, PNG ou WEBP — 5 Mo max
                <?php else: ?>
                  <strong style="color:#dc2626;">Minimum 3 photos</strong> — JPG, PNG ou WEBP — 5 Mo max par image
                <?php endif; ?>
              </p>
            </div>
            <input type="file" id="input-images" name="images[]" accept="image/*" multiple />
            <div class="preview-grid" id="preview-grid"></div>
            <p class="upload-tip">Cliquez sur une photo pour la définir comme image principale.</p>
          </div>

          <button type="submit" class="btn-publier">
            <?php if ($isEdit): ?>
              <i class="fa-solid fa-floppy-disk" style="margin-right:8px"></i>
              Enregistrer les modifications
            <?php else: ?>
              <i class="fa-solid fa-paper-plane" style="margin-right:8px"></i>
              Publier l'annonce
            <?php endif; ?>
          </button>

          <?php if ($isEdit): ?>
            <a href="mon-compte.php?tab=annonces" style="display:inline-block;margin-left:12px;padding:10px 20px;color:#64748b;text-decoration:none;font-size:14px;">
              Annuler
            </a>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <footer>
      <div class="footer-top">
        <div class="footer-left">
          <img src="img/iconSite_WhiteText.png" class="logo-footer" alt="logo" />
        </div>
        <div class="footer-center">
          <p class="footer-text">Ce site a été conçu et développé par les élèves de l'ISEP du groupe G9A 2025/2026 — Tous droits réservés</p>
        </div>
      </div>
      <div class="footer-bottom">
        <a href="Mentionlegales.php">Mentions légales et CGU</a><p>-</p><a href="GestionCookies.html">Gestion des cookies</a>
      </div>
    </footer>

    <script src="js/publier.js"></script>
    <script>
      (function () {
        const MAPBOX_TOKEN = 'pk.eyJ1IjoibWVua29vcyIsImEiOiJjbW5zeHkycXIwZTk2Mm9zOWptcmtkdjh2In0.v-2w8GRPwZaHopaowVlsFA';
        mapboxgl.accessToken = MAPBOX_TOKEN;

        const map = new mapboxgl.Map({
          container: 'map-preview',
          style: 'mapbox://styles/mapbox/streets-v12',
          center: [2.3522, 46.8],
          zoom: 4.5,
        });
        map.addControl(new mapboxgl.NavigationControl(), 'top-right');

        let marker = null;
        let timer  = null;
        const info = document.getElementById('map-info');

        function setInfo(text, state) {
          info.className = 'map-info' + (state ? ' ' + state : '');
          const icon = state === 'found' ? 'fa-circle-check'
                     : state === 'loading' ? 'fa-spinner fa-spin'
                     : 'fa-circle-info';
          info.innerHTML = '<i class="fa-solid ' + icon + '"></i><span>' + text + '</span>';
        }

        async function geocode() {
          const adresse = document.getElementById('adresse').value.trim();
          const ville   = document.getElementById('ville').value.trim();
          const cp      = document.getElementById('code-postal').value.trim();

          if (!ville) return;

          const query = [adresse, cp, ville, 'France'].filter(Boolean).join(', ');
          setInfo('Recherche de l\'adresse…', 'loading');

          try {
            const url = 'https://api.mapbox.com/geocoding/v5/mapbox.places/'
                      + encodeURIComponent(query)
                      + '.json?access_token=' + MAPBOX_TOKEN
                      + '&country=FR&language=fr&limit=1';
            const r = await fetch(url);
            const d = await r.json();

            if (d.features && d.features.length > 0) {
              const feat = d.features[0];
              const [lng, lat] = feat.center;
              document.getElementById('lat').value = lat;
              document.getElementById('lng').value = lng;

              map.flyTo({ center: [lng, lat], zoom: 15, duration: 900 });
              if (marker) marker.setLngLat([lng, lat]);
              else marker = new mapboxgl.Marker({ color: '#244676' })
                             .setLngLat([lng, lat]).addTo(map);

              setInfo('Position trouvée : ' + feat.place_name, 'found');
            } else {
              setInfo('Adresse introuvable. Affinez la saisie.', null);
            }
          } catch (e) {
            setInfo('Erreur de géocodage.', null);
          }
        }

        ['adresse', 'ville', 'code-postal'].forEach(function (id) {
          document.getElementById(id).addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(geocode, 800);
          });
        });
      })();
    </script>
  </body>
</html>
