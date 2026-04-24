<?php
require 'session.php';

$id = trim($_GET['id'] ?? '');
if (empty($id)) {
    header('Location: Accueil.php');
    exit;
}

// Paramètres de retour (filtres conservés)
$retour_params = [];
foreach (['ville','type','budget','superficie_min','superficie_max','type_offre','type_proprio','apl'] as $k) {
    if (!empty($_GET[$k])) $retour_params[$k] = $_GET[$k];
}
if (!empty($_GET['equipements']) && is_array($_GET['equipements'])) {
    $retour_params['equipements'] = $_GET['equipements'];
}
$retour_url = 'Accueil.php' . ($retour_params ? '?' . http_build_query($retour_params) : '');

try {
    $stmt = $pdo->prepare("
        SELECT a.*, u.nom, u.lastname, u.photo_profil, u.id_utilisateur AS proprio_id
        FROM annonces a
        LEFT JOIN utilisateur_ u ON u.id_utilisateur = a.utilisateur_id
        WHERE a.id_annonce = ? AND a.statut = 'actif'
    ");
    $stmt->execute([$id]);
    $a = $stmt->fetch();
} catch (PDOException $e) { $a = null; }

if (!$a) {
    header('Location: Accueil.php');
    exit;
}

// Incrémenter nb_vues
try {
    $pdo->prepare("UPDATE annonces SET nb_vues = nb_vues + 1 WHERE id_annonce = ?")->execute([$id]);
} catch (PDOException $e) {}

$images = json_decode($a['liste_images'] ?? '[]', true) ?: [];
if (empty($images) && !empty($a['image_principale'])) $images = [$a['image_principale']];

$proprio_init = strtoupper(mb_substr($a['nom'] ?? 'P', 0, 1) . mb_substr($a['lastname'] ?? '', 0, 1));
$proprio_nom  = trim(($a['nom'] ?? '') . ' ' . ($a['lastname'] ?? '')) ?: 'Propriétaire';

// Déjà en favori ?
$estFavori = false;
if (isset($_SESSION['user_id'])) {
    try {
        $s = $pdo->prepare("SELECT 1 FROM favoris WHERE utilisateur_id = ? AND annonce_id = ?");
        $s->execute([$_SESSION['user_id'], $id]);
        $estFavori = (bool)$s->fetchColumn();
    } catch (PDOException $e) {}
}

$equipements_liste = json_decode($a['equipements'] ?? '[]', true) ?: [];
$equip_labels = [
    'wifi'             => ['fa-wifi',                 'Wi-Fi'],
    'machine_a_laver'  => ['fa-shirt',                'Lave-linge'],
    'lave_vaisselle'   => ['fa-jug-detergent',        'Lave-vaisselle'],
    'micro_onde'       => ['fa-square',               'Micro-ondes'],
    'four'             => ['fa-fire',                 'Four'],
    'frigo'            => ['fa-snowflake',            'Réfrigérateur'],
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars(ucfirst($a['type_immeuble']) . ' — ' . $a['ville']); ?> — Seek &amp; Stay</title>
  <link rel="icon" type="image/x-icon" href="img/flavicon.png" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
  <link rel="stylesheet" href="styles/styles.css" />
  <link href="https://api.mapbox.com/mapbox-gl-js/v3.20.0/mapbox-gl.css" rel="stylesheet" />
  <script src="https://api.mapbox.com/mapbox-gl-js/v3.20.0/mapbox-gl.js"></script>
  <style>
    #annonce-map { height: 300px; border-radius: 12px; overflow: hidden; }
    :root {
      --primary: #244676; --primary-h: #1a3459; --accent: #30bae6;
      --text: #1e293b; --muted: #64748b; --border: #e2e8f0;
      --bg: #f1f5f9; --white: #fff; --radius: 12px;
    }
    body { background: var(--bg); font-family: 'Inter', sans-serif; }

    /* ── Fil d'Ariane ── */
    .breadcrumb {
      padding: 14px 40px;
      font-size: 13px; color: var(--muted);
      display: flex; align-items: center; gap: 6px;
      background: var(--white); border-bottom: 1px solid var(--border);
    }
    .breadcrumb a { color: var(--muted); text-decoration: none; }
    .breadcrumb a:hover { color: var(--primary); }
    .breadcrumb .sep { color: #cbd5e1; }

    /* ── Layout ── */
    .detail-page {
      max-width: 1100px; margin: 32px auto; padding: 0 24px 60px;
      display: grid; grid-template-columns: 1fr 340px; gap: 28px;
    }

    /* ── Galerie ── */
    .gallery { border-radius: var(--radius); overflow: hidden; background: #000; }
    .gallery-main {
      width: 100%; height: 420px; object-fit: cover;
      display: block; cursor: zoom-in; transition: opacity .2s;
    }
    .gallery-main:hover { opacity: .92; }
    .gallery-thumbs {
      display: flex; gap: 6px; padding: 6px; background: #111; flex-wrap: wrap;
    }
    .gallery-thumb {
      width: 72px; height: 54px; object-fit: cover;
      border-radius: 6px; cursor: pointer; opacity: .6;
      transition: opacity .15s; border: 2px solid transparent;
    }
    .gallery-thumb.active, .gallery-thumb:hover { opacity: 1; border-color: var(--accent); }

    /* ── Infos principales ── */
    .detail-main { display: flex; flex-direction: column; gap: 20px; }

    .detail-card {
      background: var(--white); border-radius: var(--radius);
      padding: 24px 28px;
      box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 4px 14px rgba(0,0,0,.06);
    }

    .detail-titre {
      display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
      margin-bottom: 6px;
    }
    .detail-titre h1 {
      font-size: 1.5rem; font-weight: 800; color: var(--text); margin: 0;
    }
    .badge-offre {
      background: var(--accent); color: #fff;
      font-size: 11px; font-weight: 700; padding: 4px 12px;
      border-radius: 20px; white-space: nowrap; flex-shrink: 0;
      text-transform: capitalize;
    }

    .detail-ville {
      font-size: 14px; color: var(--muted);
      display: flex; align-items: center; gap: 5px; margin-bottom: 16px;
    }

    .detail-prix {
      font-size: 2rem; font-weight: 800; color: var(--primary); margin-bottom: 4px;
    }
    .detail-prix span { font-size: 14px; font-weight: 400; color: var(--muted); }

    .detail-chips {
      display: flex; flex-wrap: wrap; gap: 8px; margin-top: 16px;
    }
    .chip {
      background: var(--bg); border: 1px solid var(--border);
      border-radius: 20px; padding: 5px 14px;
      font-size: 13px; color: var(--text);
      display: flex; align-items: center; gap: 6px;
    }
    .chip i { color: var(--accent); font-size: 12px; }

    .detail-section-title {
      font-size: 15px; font-weight: 700; color: var(--text);
      margin: 0 0 12px;
    }
    .detail-desc {
      font-size: 14px; color: #475569; line-height: 1.75;
      white-space: pre-line;
    }

    /* ── Sidebar ── */
    .detail-sidebar { display: flex; flex-direction: column; gap: 16px; }

    .proprio-card {
      background: var(--white); border-radius: var(--radius);
      padding: 22px; text-align: center;
      box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 4px 14px rgba(0,0,0,.06);
    }
    .proprio-avatar {
      width: 64px; height: 64px; border-radius: 50%;
      background: var(--primary); color: #fff;
      display: flex; align-items: center; justify-content: center;
      font-size: 22px; font-weight: 700; margin: 0 auto 12px;
      overflow: hidden;
    }
    .proprio-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .proprio-nom { font-size: 15px; font-weight: 700; color: var(--text); margin-bottom: 4px; }
    .proprio-label { font-size: 12px; color: var(--muted); margin-bottom: 16px; }

    .btn-contact {
      display: block; width: 100%; padding: 12px;
      background: var(--primary); color: #fff;
      border: none; border-radius: var(--radius);
      font-size: 14px; font-weight: 700; cursor: pointer;
      font-family: inherit; text-align: center; text-decoration: none;
      transition: background .2s; margin-bottom: 8px;
    }
    .btn-contact:hover { background: var(--primary-h); }
    .btn-contact.outline {
      background: none; border: 1px solid var(--border);
      color: var(--text); font-weight: 500;
    }
    .btn-contact.outline:hover { border-color: var(--primary); color: var(--primary); }

    .info-card {
      background: var(--white); border-radius: var(--radius);
      padding: 20px 22px;
      box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 4px 14px rgba(0,0,0,.06);
    }
    .info-row {
      display: flex; justify-content: space-between; align-items: center;
      font-size: 13px; padding: 8px 0; border-bottom: 1px solid #f8fafc;
    }
    .info-row:last-child { border-bottom: none; }
    .info-row .label { color: var(--muted); }
    .info-row .value { font-weight: 600; color: var(--text); }

    /* ── Lightbox ── */
    .lightbox {
      display: none; position: fixed; inset: 0; z-index: 9999;
      background: rgba(0,0,0,.92); align-items: center; justify-content: center;
    }
    .lightbox.open { display: flex; }
    .lightbox img {
      max-width: 90vw; max-height: 90vh;
      object-fit: contain; border-radius: 8px;
    }
    .lightbox-close {
      position: absolute; top: 20px; right: 28px;
      color: #fff; font-size: 28px; cursor: pointer; background: none; border: none;
    }

    @media (max-width: 860px) {
      .detail-page { grid-template-columns: 1fr; }
      .detail-sidebar { order: -1; }
      .breadcrumb { padding: 12px 16px; }
      .gallery-main { height: 260px; }
    }
  </style>
</head>
<body>

<header>
  <div class="flex">
    <a href="Accueil.php" class="header-logo">
      <img src="img/iconSite.png" class="header-logo" alt="logo" />
    </a>
    <ul class="header-menu">
      <li><a href="Accueil.php">Accueil</a></li>
      <li><a href="Annonces.php">Annonces</a></li>
      <li><a href="Favoris.php">Favoris</a></li>
      <?php if (!isset($_SESSION['user_id']) || ($_SESSION['role_type'] ?? '') === 'proprietaire'): ?>
      <li><a href="Publier.php">Publier</a></li>
      <?php endif; ?>
      <li><a href="Contact.html">Contact</a></li>
      <?php if (isset($_SESSION['user_id'])): ?>
      <li><a href="messagerie.php">Messages</a></li>
      <li><a href="mon-compte.php" style="font-weight:600;">Mon compte</a></li>
      <li><a href="FAQ.html">FAQ</a></li>
      <?php else: ?>
      <li><a href="FAQ.html">FAQ</a></li>
      <li><a href="Authentification.html">Inscription / Connexion</a></li>
      <?php endif; ?>
    </ul>
  </div>
</header>

<!-- Fil d'Ariane -->
<div class="breadcrumb">
  <a href="Accueil.php"><i class="fa-solid fa-house"></i></a>
  <span class="sep">›</span>
  <a href="<?php echo htmlspecialchars($retour_url); ?>">Annonces</a>
  <span class="sep">›</span>
  <span><?php echo htmlspecialchars(ucfirst($a['type_immeuble']) . ' — ' . $a['ville']); ?></span>
</div>

<div class="detail-page">

  <!-- ══ Colonne principale ══ -->
  <div class="detail-main">

    <!-- Galerie photos -->
    <div class="gallery">
      <?php
        $mainImg = (!empty($images[0]) && file_exists(__DIR__.'/'.$images[0]))
                   ? htmlspecialchars($images[0]) : 'img/studio2.jpg';
      ?>
      <img class="gallery-main" id="gallery-main" src="<?php echo $mainImg; ?>"
           alt="Photo principale" onclick="openLightbox(this.src)">
      <?php if (count($images) > 1): ?>
      <div class="gallery-thumbs">
        <?php foreach ($images as $i => $img):
          if (!file_exists(__DIR__.'/'.$img)) continue;
        ?>
        <img class="gallery-thumb <?php echo $i===0?'active':''; ?>"
             src="<?php echo htmlspecialchars($img); ?>"
             alt="Photo <?php echo $i+1; ?>"
             onclick="switchImage(this, '<?php echo htmlspecialchars($img); ?>')">
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Titre & prix -->
    <div class="detail-card">
      <div class="detail-titre">
        <h1><?php echo htmlspecialchars(ucfirst($a['type_immeuble'])); ?></h1>
        <span class="badge-offre"><?php echo htmlspecialchars($a['type_offre']); ?></span>
      </div>
      <p class="detail-ville">
        <i class="fa-solid fa-location-dot"></i>
        <?php echo htmlspecialchars($a['adresse'] . ', ' . $a['code_postal'] . ' ' . $a['ville']); ?>
      </p>
      <p class="detail-prix">
        <?php echo number_format($a['prix'], 0, ',', ' '); ?> €
        <span>/ mois</span>
      </p>
      <div class="detail-chips">
        <span class="chip"><i class="fa-solid fa-vector-square"></i> <?php echo $a['superficie']; ?> m²</span>
        <span class="chip"><i class="fa-solid fa-building"></i> <?php echo htmlspecialchars(ucfirst($a['type_immeuble'])); ?></span>
        <span class="chip"><i class="fa-solid fa-key"></i> <?php echo htmlspecialchars(ucfirst($a['type_offre'])); ?></span>
        <?php if (!empty($a['type_proprio'])): ?>
        <span class="chip"><i class="fa-solid <?php echo $a['type_proprio']==='agence'?'fa-building-columns':'fa-user'; ?>"></i>
          <?php echo ucfirst(htmlspecialchars($a['type_proprio'])); ?>
        </span>
        <?php endif; ?>
        <?php if (!empty($a['apl_accepte'])): ?>
        <span class="chip" style="background:#ecfdf5;border-color:#a7f3d0;color:#065f46">
          <i class="fa-solid fa-hand-holding-heart" style="color:#10b981"></i> APL accepté
        </span>
        <?php endif; ?>
        <span class="chip"><i class="fa-solid fa-eye"></i> <?php echo (int)$a['nb_vues']; ?> vue<?php echo $a['nb_vues']>1?'s':''; ?></span>
        <?php if (!empty($a['date_publication'])): ?>
        <span class="chip"><i class="fa-solid fa-calendar"></i> <?php echo date('d/m/Y', strtotime($a['date_publication'])); ?></span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Description -->
    <div class="detail-card">
      <p class="detail-section-title">Description</p>
      <p class="detail-desc"><?php echo htmlspecialchars($a['description']); ?></p>
    </div>

    <!-- Équipements -->
    <?php if (!empty($equipements_liste)): ?>
    <div class="detail-card">
      <p class="detail-section-title">Équipements</p>
      <div class="detail-chips">
        <?php foreach ($equipements_liste as $eq):
          [$icon, $lbl] = $equip_labels[$eq] ?? ['fa-check', ucfirst($eq)];
        ?>
        <span class="chip"><i class="fa-solid <?php echo $icon; ?>"></i> <?php echo htmlspecialchars($lbl); ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Localisation carte (avec géocodage de secours si lat/lng manquent) -->
    <div class="detail-card">
      <p class="detail-section-title">Localisation</p>
      <div id="annonce-map"></div>
      <p style="font-size:12px;color:var(--muted);margin:10px 0 0;">
        <i class="fa-solid fa-location-dot" style="color:var(--accent)"></i>
        <?php echo htmlspecialchars($a['adresse'] . ', ' . $a['code_postal'] . ' ' . $a['ville']); ?>
      </p>
    </div>

  </div>

  <!-- ══ Sidebar ══ -->
  <aside class="detail-sidebar">

    <!-- Propriétaire -->
    <div class="proprio-card">
      <div class="proprio-avatar">
        <?php if (!empty($a['photo_profil']) && file_exists(__DIR__.'/'.$a['photo_profil'])): ?>
          <img src="<?php echo htmlspecialchars($a['photo_profil']); ?>" alt="">
        <?php else: ?>
          <?php echo $proprio_init; ?>
        <?php endif; ?>
      </div>
      <?php if (!empty($a['proprio_id'])): ?>
      <a class="proprio-nom" href="profil.php?id=<?php echo urlencode($a['proprio_id']); ?>"
         style="color:var(--primary);text-decoration:none;display:block;">
        <?php echo htmlspecialchars($proprio_nom); ?>
      </a>
      <?php else: ?>
      <p class="proprio-nom"><?php echo htmlspecialchars($proprio_nom); ?></p>
      <?php endif; ?>
      <p class="proprio-label">
        <?php echo !empty($a['type_proprio']) ? ucfirst(htmlspecialchars($a['type_proprio'])) : 'Propriétaire'; ?>
      </p>

      <?php if (isset($_SESSION['user_id']) && !empty($a['proprio_id']) && $a['proprio_id'] !== $_SESSION['user_id']): ?>
        <a class="btn-contact" href="messagerie.php?contact=<?php echo urlencode($a['proprio_id']); ?>">
          <i class="fa-solid fa-comment" style="margin-right:7px"></i>Contacter
        </a>
        <a class="btn-contact outline" href="profil.php?id=<?php echo urlencode($a['proprio_id']); ?>">
          <i class="fa-solid fa-user" style="margin-right:7px"></i>Voir le profil
        </a>
      <?php elseif (!isset($_SESSION['user_id'])): ?>
        <a class="btn-contact" href="Authentification.html?error=<?php echo urlencode('Connectez-vous pour contacter le propriétaire.'); ?>">
          <i class="fa-solid fa-comment" style="margin-right:7px"></i>Contacter
        </a>
      <?php endif; ?>

      <?php if (isset($_SESSION['user_id'])): ?>
      <button class="btn-contact outline" id="btn-fav-annonce"
              onclick="toggleAnnonceFavori(this)"
              style="<?php echo $estFavori ? 'background:#ef4444;border-color:#ef4444;color:#fff;' : 'border-color:#fecaca;color:#ef4444;'; ?>">
        <i class="<?php echo $estFavori ? 'fa-solid' : 'fa-regular'; ?> fa-heart" style="margin-right:7px"></i>
        <span><?php echo $estFavori ? 'Retirer des favoris' : 'Ajouter aux favoris'; ?></span>
      </button>
      <?php endif; ?>

      <a class="btn-contact outline" href="<?php echo htmlspecialchars($retour_url); ?>">
        <i class="fa-solid fa-arrow-left" style="margin-right:7px"></i>Retour aux annonces
      </a>

      <?php
        // Bouton Signaler : affiché pour tout utilisateur connecté qui n'est pas le propriétaire
        $peutSignaler = isset($_SESSION['user_id'])
                     && (empty($a['proprio_id']) || $a['proprio_id'] !== $_SESSION['user_id']);
      ?>
      <?php if ($peutSignaler): ?>
      <button class="btn-contact outline" onclick="openReport()"
              style="border-color:#fecaca;color:#dc2626;margin-top:4px;">
        <i class="fa-solid fa-flag" style="margin-right:7px"></i>Signaler l'annonce
      </button>
      <?php elseif (!isset($_SESSION['user_id'])): ?>
      <a class="btn-contact outline" href="Authentification.html"
         style="border-color:#fecaca;color:#dc2626;margin-top:4px;">
        <i class="fa-solid fa-flag" style="margin-right:7px"></i>Signaler l'annonce
      </a>
      <?php endif; ?>
    </div>

    <!-- Récap infos -->
    <div class="info-card">
      <p class="detail-section-title" style="margin-bottom:4px">Détails</p>
      <div class="info-row">
        <span class="label">Type</span>
        <span class="value"><?php echo htmlspecialchars(ucfirst($a['type_immeuble'])); ?></span>
      </div>
      <div class="info-row">
        <span class="label">Offre</span>
        <span class="value"><?php echo htmlspecialchars(ucfirst($a['type_offre'])); ?></span>
      </div>
      <div class="info-row">
        <span class="label">Superficie</span>
        <span class="value"><?php echo $a['superficie']; ?> m²</span>
      </div>
      <div class="info-row">
        <span class="label">Loyer</span>
        <span class="value"><?php echo number_format($a['prix'], 0, ',', ' '); ?> €/mois</span>
      </div>
      <div class="info-row">
        <span class="label">Ville</span>
        <span class="value"><?php echo htmlspecialchars($a['ville']); ?></span>
      </div>
      <div class="info-row">
        <span class="label">Code postal</span>
        <span class="value"><?php echo htmlspecialchars($a['code_postal']); ?></span>
      </div>
      <?php if (!empty($a['type_proprio'])): ?>
      <div class="info-row">
        <span class="label">Propriétaire</span>
        <span class="value"><?php echo ucfirst(htmlspecialchars($a['type_proprio'])); ?></span>
      </div>
      <?php endif; ?>
      <?php if (!empty($a['date_disponible'])): ?>
      <div class="info-row">
        <span class="label">Disponible dès</span>
        <span class="value"><?php echo date('d/m/Y', strtotime($a['date_disponible'])); ?></span>
      </div>
      <?php endif; ?>
      <?php if (!empty($a['duree_min'])): ?>
      <div class="info-row">
        <span class="label">Durée minimum</span>
        <span class="value"><?php echo htmlspecialchars($a['duree_min']); ?></span>
      </div>
      <?php endif; ?>
      <?php if (!empty($a['nb_pieces'])): ?>
      <div class="info-row">
        <span class="label">Pièces</span>
        <span class="value"><?php echo (int)$a['nb_pieces']; ?></span>
      </div>
      <?php endif; ?>
      <div class="info-row">
        <span class="label">Ameublement</span>
        <span class="value"><?php echo !empty($a['meuble']) ? 'Meublé' : 'Non meublé'; ?></span>
      </div>
      <div class="info-row">
        <span class="label">Charges</span>
        <span class="value" style="color:<?php echo !empty($a['charges_incluses']) ? '#059669' : '#94a3b8'; ?>">
          <?php echo !empty($a['charges_incluses']) ? '✓ Incluses' : 'En sus'; ?>
        </span>
      </div>
      <div class="info-row">
        <span class="label">APL</span>
        <span class="value" style="color:<?php echo !empty($a['apl_accepte']) ? '#059669' : '#94a3b8'; ?>">
          <?php echo !empty($a['apl_accepte']) ? '✓ Accepté' : 'Non accepté'; ?>
        </span>
      </div>
      <?php if (!empty($a['animaux_acceptes']) || !empty($a['fumeur_autorise']) || !empty($a['accessible_pmr'])): ?>
      <div class="info-row" style="flex-direction:column;align-items:flex-start;gap:6px">
        <span class="label">Règles</span>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <?php if (!empty($a['animaux_acceptes'])): ?>
            <span class="chip" style="font-size:11px"><i class="fa-solid fa-paw"></i> Animaux</span>
          <?php endif; ?>
          <?php if (!empty($a['fumeur_autorise'])): ?>
            <span class="chip" style="font-size:11px"><i class="fa-solid fa-smoking"></i> Fumeur</span>
          <?php endif; ?>
          <?php if (!empty($a['accessible_pmr'])): ?>
            <span class="chip" style="font-size:11px"><i class="fa-solid fa-wheelchair"></i> PMR</span>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

  </aside>
</div>

<!-- ══ Modal Signaler ══ -->
<?php if ($peutSignaler): ?>
<div id="report-modal" onclick="if(event.target===this) closeReport()"
     style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.6);align-items:center;justify-content:center;padding:20px;">
  <div style="background:#fff;border-radius:14px;width:100%;max-width:460px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.3);">
    <div style="padding:18px 22px 14px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;gap:10px;">
      <i class="fa-solid fa-flag" style="color:#dc2626"></i>
      <h3 style="margin:0;font-size:16px;font-weight:700;">Signaler cette annonce</h3>
    </div>
    <div style="padding:18px 22px;">
      <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Raison</label>
      <select id="report-reason" style="width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;margin-bottom:14px;">
        <option value="">— Choisir —</option>
        <option value="spam">Spam ou publicité</option>
        <option value="fausse_annonce">Fausse annonce</option>
        <option value="arnaque">Tentative d'arnaque</option>
        <option value="contenu_inapproprie">Contenu inapproprié</option>
        <option value="autre">Autre raison</option>
      </select>
      <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Commentaire (facultatif)</label>
      <textarea id="report-comment" maxlength="1000"
                style="width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;resize:vertical;min-height:80px;"
                placeholder="Précisez le contexte…"></textarea>
      <div id="report-msg" style="font-size:12px;margin-top:8px;padding:8px 10px;border-radius:6px;display:none;"></div>
    </div>
    <div style="display:flex;justify-content:flex-end;gap:8px;padding:12px 22px 18px;">
      <button onclick="closeReport()" style="padding:10px 18px;border-radius:10px;font-size:13px;font-weight:600;font-family:inherit;cursor:pointer;background:transparent;border:1px solid #e2e8f0;color:#1e293b;">Annuler</button>
      <button id="report-submit" onclick="submitReport()"
              style="padding:10px 18px;border-radius:10px;font-size:13px;font-weight:600;font-family:inherit;cursor:pointer;background:transparent;border:1px solid #fecaca;color:#dc2626;">
        <i class="fa-solid fa-flag" style="margin-right:5px"></i>Envoyer
      </button>
    </div>
  </div>
</div>

<script>
const REPORT_CIBLE_ID = <?php echo json_encode($a['id_annonce']); ?>;
function openReport() {
  const m = document.getElementById('report-modal');
  m.style.display = 'flex';
  document.getElementById('report-reason').value  = '';
  document.getElementById('report-comment').value = '';
  document.getElementById('report-msg').style.display = 'none';
  document.getElementById('report-submit').disabled = false;
}
function closeReport() {
  document.getElementById('report-modal').style.display = 'none';
}
async function submitReport() {
  const raison      = document.getElementById('report-reason').value;
  const commentaire = document.getElementById('report-comment').value.trim();
  const msg = document.getElementById('report-msg');
  const btn = document.getElementById('report-submit');
  if (!raison) {
    msg.style.cssText = 'font-size:12px;margin-top:8px;padding:8px 10px;border-radius:6px;display:block;background:#fef2f2;color:#dc2626;';
    msg.textContent = 'Veuillez choisir une raison.';
    return;
  }
  btn.disabled = true;
  try {
    const r = await fetch('signaler.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        cible_type: 'annonce',
        cible_id:   REPORT_CIBLE_ID,
        raison:     raison,
        commentaire: commentaire,
      }),
    });
    const d = await r.json();
    if (d.ok) {
      msg.style.cssText = 'font-size:12px;margin-top:8px;padding:8px 10px;border-radius:6px;display:block;background:#ecfdf5;color:#065f46;';
      msg.textContent = 'Merci, votre signalement a été envoyé.';
      setTimeout(closeReport, 1800);
    } else {
      msg.style.cssText = 'font-size:12px;margin-top:8px;padding:8px 10px;border-radius:6px;display:block;background:#fef2f2;color:#dc2626;';
      msg.textContent = ({
        deja_signale:'Vous avez déjà signalé cette annonce.',
        non_connecte:'Vous devez être connecté.',
      })[d.error] || 'Erreur, réessayez.';
      btn.disabled = false;
    }
  } catch(e) {
    msg.style.cssText = 'font-size:12px;margin-top:8px;padding:8px 10px;border-radius:6px;display:block;background:#fef2f2;color:#dc2626;';
    msg.textContent = 'Erreur réseau.';
    btn.disabled = false;
  }
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeReport(); });
</script>
<?php endif; ?>

<!-- Lightbox -->
<div class="lightbox" id="lightbox" onclick="closeLightbox()">
  <button class="lightbox-close" onclick="closeLightbox()">✕</button>
  <img id="lightbox-img" src="" alt="Photo agrandie">
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

<script>
  function switchImage(thumb, src) {
    document.getElementById('gallery-main').src = src;
    document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
    thumb.classList.add('active');
  }
  function openLightbox(src) {
    document.getElementById('lightbox-img').src = src;
    document.getElementById('lightbox').classList.add('open');
  }
  function closeLightbox() {
    document.getElementById('lightbox').classList.remove('open');
  }
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });

  <?php if (isset($_SESSION['user_id'])): ?>
  const FAV_ANNONCE_ID = <?php echo json_encode($id); ?>;
  async function toggleAnnonceFavori(btn) {
    try {
      const r = await fetch('api/favoris.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ annonce_id: FAV_ANNONCE_ID }),
      });
      const d = await r.json();
      if (d.ok) {
        const icon = btn.querySelector('i');
        const label = btn.querySelector('span');
        if (d.favori) {
          btn.style.cssText = 'background:#ef4444;border-color:#ef4444;color:#fff;';
          icon.classList.remove('fa-regular'); icon.classList.add('fa-solid');
          label.textContent = 'Retirer des favoris';
        } else {
          btn.style.cssText = 'border-color:#fecaca;color:#ef4444;';
          icon.classList.remove('fa-solid'); icon.classList.add('fa-regular');
          label.textContent = 'Ajouter aux favoris';
        }
      }
    } catch(e) {}
  }
  <?php endif; ?>

  // ── Carte Mapbox de l'annonce ──────────────────────────────────────
  const MAPBOX_TOKEN = 'pk.eyJ1IjoibWVua29vcyIsImEiOiJjbW5zeHkycXIwZTk2Mm9zOWptcmtkdjh2In0.v-2w8GRPwZaHopaowVlsFA';
  mapboxgl.accessToken = MAPBOX_TOKEN;

  <?php $hasCoords = !empty($a['lat']) && !empty($a['lng']); ?>
  const initialCoords = <?php echo $hasCoords
      ? '[' . (float)$a['lng'] . ', ' . (float)$a['lat'] . ']'
      : 'null'; ?>;
  const addressQuery = <?php echo json_encode(
      trim($a['adresse'] . ', ' . $a['code_postal'] . ' ' . $a['ville'] . ', France')
  ); ?>;

  const annonceMap = new mapboxgl.Map({
    container: 'annonce-map',
    style: 'mapbox://styles/mapbox/streets-v12',
    center: initialCoords || [2.3522, 46.8],
    zoom: initialCoords ? 15 : 5,
  });
  annonceMap.addControl(new mapboxgl.NavigationControl(), 'top-right');

  let annonceMarker = null;
  if (initialCoords) {
    annonceMarker = new mapboxgl.Marker({ color: '#244676' })
      .setLngLat(initialCoords).addTo(annonceMap);
  } else {
    // Géocodage de secours (annonces créées avant l'ajout lat/lng)
    fetch('https://api.mapbox.com/geocoding/v5/mapbox.places/'
          + encodeURIComponent(addressQuery)
          + '.json?access_token=' + MAPBOX_TOKEN
          + '&country=FR&language=fr&limit=1')
      .then(r => r.json())
      .then(data => {
        if (data.features && data.features.length > 0) {
          const [lng, lat] = data.features[0].center;
          annonceMap.flyTo({ center: [lng, lat], zoom: 15, duration: 800 });
          annonceMarker = new mapboxgl.Marker({ color: '#244676' })
            .setLngLat([lng, lat]).addTo(annonceMap);
        }
      })
      .catch(() => {});
  }
</script>
</body>
</html>
