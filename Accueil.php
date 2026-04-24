<?php
require 'session.php';

// ── Filtres depuis le hero ────────────────────────────────────────────────
$f_ville          = trim($_GET['ville']            ?? '');
$f_type           = trim($_GET['type']             ?? '');
$f_budget         = intval($_GET['budget']         ?? 0);
$f_superficie_min = intval($_GET['superficie_min'] ?? 0);
$f_superficie_max = intval($_GET['superficie_max'] ?? 0);
$f_equipements    = is_array($_GET['equipements']  ?? null) ? $_GET['equipements'] : [];
$f_type_offre     = trim($_GET['type_offre']       ?? '');
$f_type_proprio   = trim($_GET['type_proprio']     ?? '');
$f_apl            = !empty($_GET['apl'])           ? 1 : 0;
$f_meuble         = trim($_GET['meuble']           ?? '');
$f_nb_pieces      = intval($_GET['nb_pieces']      ?? 0);
$f_duree          = trim($_GET['duree']            ?? '');
$f_charges        = !empty($_GET['charges'])       ? 1 : 0;
$f_animaux        = !empty($_GET['animaux'])       ? 1 : 0;
$f_fumeur         = !empty($_GET['fumeur'])        ? 1 : 0;
$f_pmr            = !empty($_GET['pmr'])           ? 1 : 0;

// ── Stats dynamiques ──────────────────────────────────────────────────────
try {
    $totalAnnonces  = (int)$pdo->query("SELECT COUNT(*) FROM annonces WHERE statut='actif'")->fetchColumn();
    $totalVilles    = (int)$pdo->query("SELECT COUNT(DISTINCT ville) FROM annonces WHERE statut='actif'")->fetchColumn();
    $totalEtudiants = (int)$pdo->query("SELECT COUNT(*) FROM utilisateur_")->fetchColumn();
} catch (PDOException $e) {
    $totalAnnonces = $totalVilles = $totalEtudiants = 0;
}

// ── 6 dernières annonces (pour la section vedette) ────────────────────────
try {
    $dernieres = $pdo->query("SELECT * FROM annonces WHERE statut='actif' ORDER BY date_publication DESC LIMIT 6")->fetchAll();
} catch (PDOException $e) { $dernieres = []; }

// ── Annonces filtrées (pour la liste complète) ────────────────────────────
try {
    $where  = ["statut = 'actif'"];
    $params = [];
    if ($f_ville)          { $where[] = "ville LIKE ?";         $params[] = '%' . $f_ville . '%'; }
    if ($f_type)           { $where[] = "type_immeuble = ?";    $params[] = $f_type; }
    if ($f_budget)         { $where[] = "prix <= ?";            $params[] = $f_budget; }
    if ($f_superficie_min) { $where[] = "superficie >= ?";      $params[] = $f_superficie_min; }
    if ($f_superficie_max) { $where[] = "superficie <= ?";      $params[] = $f_superficie_max; }
    if ($f_type_offre)     { $where[] = "type_offre = ?";       $params[] = $f_type_offre; }
    if ($f_type_proprio)   { $where[] = "type_proprio = ?";     $params[] = $f_type_proprio; }
    if ($f_apl)            { $where[] = "apl_accepte = 1"; }
    if ($f_meuble === '1')  { $where[] = "meuble = 1"; }
    if ($f_meuble === '0')  { $where[] = "(meuble = 0 OR meuble IS NULL)"; }
    if ($f_nb_pieces)       { $where[] = "nb_pieces >= ?"; $params[] = $f_nb_pieces; }
    if ($f_duree)           { $where[] = "duree_min = ?"; $params[] = $f_duree; }
    if ($f_charges)         { $where[] = "charges_incluses = 1"; }
    if ($f_animaux)         { $where[] = "animaux_acceptes = 1"; }
    if ($f_fumeur)          { $where[] = "fumeur_autorise = 1"; }
    if ($f_pmr)             { $where[] = "accessible_pmr = 1"; }
    foreach ($f_equipements as $eq) {
        $eq = trim($eq);
        if ($eq) { $where[] = "equipements LIKE ?"; $params[] = '%' . $eq . '%'; }
    }

    $sql    = "SELECT * FROM annonces WHERE " . implode(' AND ', $where) . " ORDER BY date_publication DESC";
    $stmt   = $pdo->prepare($sql);
    $stmt->execute($params);
    $annonces = $stmt->fetchAll();
} catch (PDOException $e) { $annonces = []; }

$filtresActifs = $f_ville || $f_type || $f_budget || $f_superficie_min || $f_superficie_max
              || $f_type_offre || $f_type_proprio || $f_apl || !empty($f_equipements)
              || $f_meuble !== '' || $f_nb_pieces || $f_duree || $f_charges
              || $f_animaux || $f_fumeur || $f_pmr;

$erreur_acces = htmlspecialchars(urldecode($_GET['erreur'] ?? ''));

// IDs des annonces en favori pour l'utilisateur connecté
$mesFavoris = [];
if (isset($_SESSION['user_id'])) {
    try {
        $s = $pdo->prepare("SELECT annonce_id FROM favoris WHERE utilisateur_id = ?");
        $s->execute([$_SESSION['user_id']]);
        $mesFavoris = $s->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {}
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <title>Seek &amp; Stay | Location de Logements pour étudiants</title>
  <meta name="description" content="Trouvez votre logement étudiant sur Seek &amp; Stay" />
  <link rel="icon" type="image/x-icon" href="img/flavicon.png" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
  <link rel="stylesheet" href="styles/styles.css" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://api.mapbox.com/mapbox-gl-js/v3.20.0/mapbox-gl.css" rel="stylesheet">
  <script src="https://api.mapbox.com/mapbox-gl-js/v3.20.0/mapbox-gl.js"></script>
  <script src="js/vue-map.js" defer></script>
  <link rel="stylesheet" href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.css" type="text/css" />
  <script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.min.js"></script>
  <style>
    /* ── Hero ────────────────────────────────────────────────────── */
    .hero {
      position: relative;
      width: 100%;
      min-height: 480px;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }
    .hero-bg {
      position: absolute; inset: 0;
      background: url('img/banniere3.jpg') center/cover no-repeat;
      filter: brightness(0.45);
    }
    .hero-content {
      position: relative;
      text-align: center;
      padding: 60px 20px;
      width: 100%;
      max-width: 780px;
    }
    .hero-content h1 {
      font-size: 2.6rem; font-weight: 800; color: #fff;
      margin: 0 0 10px; line-height: 1.2;
    }
    .hero-content p {
      font-size: 1.05rem; color: rgba(255,255,255,.82);
      margin: 0 0 36px;
    }
    .hero-form {
      background: rgba(255,255,255,.12);
      backdrop-filter: blur(6px);
      border: 1px solid rgba(255,255,255,.22);
      border-radius: 16px;
      padding: 14px 16px;
    }
    /* Ligne principale : champs + boutons */
    .hero-form-row {
      display: flex; gap: 10px; flex-wrap: wrap; align-items: center;
    }
    .hero-form-row input,
    .hero-form-row select {
      flex: 1; min-width: 140px;
      padding: 12px 16px;
      border: none; border-radius: 10px;
      font-size: 14px; font-family: inherit;
      background: #fff; color: #1e293b;
      outline: none;
    }
    .hero-form-row input:focus,
    .hero-form-row select:focus { box-shadow: 0 0 0 3px rgba(48,186,230,.4); }
    .hero-btn {
      padding: 12px 24px;
      background: #30bae6; color: #fff;
      border: none; border-radius: 10px;
      font-size: 14px; font-weight: 700;
      cursor: pointer; font-family: inherit;
      transition: background .2s;
      white-space: nowrap; flex-shrink: 0;
    }
    .hero-btn:hover { background: #1aa3ce; }

    /* ── Stats ───────────────────────────────────────────────────── */
    .stats-section {
      display: flex; justify-content: center; gap: 24px;
      padding: 44px 40px;
      background: #fff;
      flex-wrap: wrap;
    }
    .stat-box {
      background: #f8fafc; border: 1px solid #e2e8f0;
      border-radius: 16px;
      padding: 28px 40px; text-align: center;
      min-width: 180px; flex: 1; max-width: 240px;
    }
    .stat-box .stat-num {
      font-size: 2.4rem; font-weight: 800;
      color: #244676; display: block; line-height: 1;
    }
    .stat-box .stat-label {
      font-size: 13px; color: #64748b;
      margin-top: 8px; display: block;
    }
    .stat-box .stat-icon {
      font-size: 22px; color: #30bae6;
      margin-bottom: 10px; display: block;
    }

    /* ── Dernières annonces ──────────────────────────────────────── */
    .section-dernieres {
      padding: 48px 40px 20px;
      background: #fff;
    }
    .section-title {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 28px;
    }
    .section-title h2 {
      font-size: 1.4rem; font-weight: 800; color: #0f1c2e; margin: 0;
    }
    .section-title a {
      font-size: 13px; color: #30bae6; text-decoration: none; font-weight: 600;
    }
    .section-title a:hover { text-decoration: underline; }

    .cards-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 24px;
    }
    .annonce-card {
      border-radius: 16px; overflow: hidden;
      background: #f7f6fb;
      box-shadow: 0 2px 8px rgba(0,0,0,.08);
      transition: transform .18s, box-shadow .18s;
      cursor: pointer;
      display: flex; flex-direction: column;
    }
    .annonce-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 24px rgba(0,0,0,.13);
    }
    .annonce-card-img {
      width: 100%; height: 200px; overflow: hidden; position: relative;
    }
    .annonce-card-img img {
      width: 100%; height: 100%; object-fit: cover; display: block;
      transition: transform .3s;
    }
    .annonce-card:hover .annonce-card-img img { transform: scale(1.04); }
    .annonce-type-badge {
      position: absolute; top: 12px; left: 12px;
      background: #244676; color: #fff;
      font-size: 11px; font-weight: 700;
      padding: 4px 10px; border-radius: 20px;
      text-transform: capitalize;
    }
    .annonce-card-actions {
      position: absolute; top: 10px; right: 10px;
      display: flex; gap: 6px; opacity: 0; transition: opacity .2s;
    }
    .annonce-card:hover .annonce-card-actions { opacity: 1; }
    .annonce-card-action-btn {
      width: 32px; height: 32px; border-radius: 50%;
      border: none; background: rgba(255,255,255,.95);
      color: #244676; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      font-size: 13px; box-shadow: 0 2px 6px rgba(0,0,0,.15);
      transition: background .15s, color .15s, transform .1s;
    }
    .annonce-card-action-btn:hover { background: #fff; transform: scale(1.08); }
    .annonce-card-action-btn.flag:hover { color: #dc2626; }
    .annonce-card-action-btn.heart { color: #ef4444; }
    .annonce-card-action-btn.heart.is-fav { background: #ef4444; color: #fff; }
    .annonce-card-action-btn.heart.is-fav:hover { background: #dc2626; }
    .annonce-card-body { padding: 16px 18px; flex: 1; }
    .annonce-card-prix {
      font-size: 1.15rem; font-weight: 800; color: #244676; margin: 0 0 6px;
    }
    .annonce-card-prix span {
      font-size: 13px; font-weight: 400; color: #64748b;
    }
    .annonce-card-ville {
      font-size: 13px; color: #64748b;
      display: flex; align-items: center; gap: 5px; margin-bottom: 8px;
    }
    .annonce-card-desc {
      font-size: 13px; color: #475569; line-height: 1.5;
      display: -webkit-box; -webkit-line-clamp: 2;
      -webkit-box-orient: vertical; overflow: hidden;
    }
    .annonce-card-footer {
      padding: 10px 18px 14px;
      display: flex; align-items: center; gap: 14px;
      font-size: 12px; color: #94a3b8;
      border-top: 1px solid #f1f5f9;
    }
    .annonce-card-footer span { display: flex; align-items: center; gap: 4px; }

    .no-annonces {
      text-align: center; padding: 48px 20px;
      color: #94a3b8; grid-column: 1/-1;
    }
    .no-annonces i { font-size: 40px; display: block; margin-bottom: 12px; }

    /* ── Filtre actif banner ─────────────────────────────────────── */
    .filter-banner {
      background: #eff6ff; border: 1px solid #bfdbfe;
      border-radius: 10px; padding: 10px 18px;
      display: flex; align-items: center; gap: 10px;
      font-size: 13px; color: #1d4ed8; margin-bottom: 20px;
      flex-wrap: wrap;
    }
    .filter-banner a {
      margin-left: auto; color: #64748b; text-decoration: none; font-size: 12px;
    }
    .filter-banner a:hover { color: #dc2626; }

    /* ── Filtres avancés ─────────────────────────────────────── */
    .hero-adv-toggle-row {
      display: flex; justify-content: center;
      margin-top: 12px;
    }
    .hero-adv-toggle {
      background: rgba(255,255,255,.2);
      border: 1px solid rgba(255,255,255,.35); color: #fff;
      border-radius: 10px; padding: 10px 22px;
      font-size: 13px; font-weight: 600; cursor: pointer;
      font-family: inherit; transition: background .2s, border-color .2s;
      display: inline-flex; align-items: center; gap: 8px;
      white-space: nowrap;
    }
    .hero-adv-toggle:hover { background: rgba(255,255,255,.3); }
    .hero-adv-toggle.is-open { background: rgba(255,255,255,.28); }

    .hero-adv-panel {
      display: none;
      background: #fff;
      border-radius: 12px;
      padding: 20px 20px 16px;
      margin-top: 12px;
      text-align: left;
    }
    .hero-adv-panel.open { display: block; }

    .adv-row {
      display: grid; grid-template-columns: 1fr 1fr;
      gap: 12px; margin-bottom: 14px;
    }
    .adv-group label {
      display: block; font-size: 12px; font-weight: 600;
      color: #244676; margin-bottom: 5px;
    }
    /* Isoler les inputs/selects du panneau avancé du flex du hero-form-row */
    .adv-group input[type="number"],
    .adv-group select {
      flex: none !important;
      width: 100%; padding: 9px 12px;
      border: 1px solid #e2e8f0; border-radius: 8px;
      font-size: 13px; font-family: inherit; color: #1e293b;
      background: #fff; outline: none; box-sizing: border-box;
      min-width: 0;
    }
    .adv-group input[type="number"]:focus,
    .adv-group select:focus {
      border-color: #30bae6; box-shadow: 0 0 0 2px rgba(48,186,230,.2);
    }
    .adv-checks {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
      gap: 8px;
    }
    .adv-check-item {
      display: flex; align-items: center; gap: 6px;
      font-size: 13px; color: #334155; cursor: pointer;
      padding: 6px 8px; border-radius: 8px;
      border: 1px solid #e2e8f0; background: #f8fafc;
      transition: border-color .15s, background .15s;
      user-select: none;
    }
    .adv-check-item:hover { border-color: #30bae6; background: #f0fbff; }
    .adv-check-item input[type="checkbox"] {
      accent-color: #30bae6; flex-shrink: 0;
      width: 15px; height: 15px;
    }

    .adv-divider {
      font-size: 11px; font-weight: 700; color: #94a3b8;
      text-transform: uppercase; letter-spacing: .6px;
      margin: 0 0 10px; border-bottom: 1px solid #f1f5f9; padding-bottom: 6px;
    }
    .adv-divider:not(:first-child) { margin-top: 16px; }

    .adv-apl-row {
      margin-top: 14px; padding-top: 14px;
      border-top: 1px solid #f1f5f9;
    }
    .adv-submit-row {
      display: flex; gap: 10px; margin-top: 16px;
      justify-content: flex-end;
    }
    .adv-reset-btn {
      padding: 9px 18px; background: none;
      border: 1px solid #e2e8f0; border-radius: 8px;
      font-size: 13px; color: #64748b; cursor: pointer;
      font-family: inherit; transition: border-color .15s, color .15s;
    }
    .adv-reset-btn:hover { border-color: #94a3b8; color: #334155; }
    .adv-apply-btn {
      padding: 9px 22px; background: #244676; color: #fff;
      border: none; border-radius: 8px;
      font-size: 13px; font-weight: 700; cursor: pointer;
      font-family: inherit; transition: background .15s;
    }
    .adv-apply-btn:hover { background: #1a3459; }

    @media (max-width: 900px) {
      .cards-grid { grid-template-columns: 1fr 1fr; }
      .hero-content h1 { font-size: 1.8rem; }
    }
    @media (max-width: 600px) {
      .cards-grid { grid-template-columns: 1fr; }
      .stats-section { padding: 28px 20px; }
      .section-dernieres { padding: 32px 16px 16px; }
      .hero-form { flex-direction: column; }
      .adv-row { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<!-- ===== HEADER ===== -->
<header>
  <div class="flex">
    <a href="Accueil.php" class="header-logo">
      <img src="img/iconSite.png" class="header-logo" alt="logo Seek &amp; Stay" />
    </a>
    <ul class="header-menu">
      <li><a href="Accueil.php" class="active">Accueil</a></li>
      <li><a href="Annonces.html">Annonces</a></li>
      <li><a href="Favoris.php">Favoris</a></li>
      <?php if (!isset($_SESSION['user_id']) || ($_SESSION['role_type'] ?? '') === 'proprietaire'): ?>
      <li><a href="Publier.php">Publier</a></li>
      <?php endif; ?>
      <li><a href="Contact.html">Contact</a></li>
      <li><a href="FAQ.html">FAQ</a></li>
      <?php if (isset($_SESSION['user_id'])): ?>
      <li><a href="messagerie.php">Messages <span id="nav-unread" style="display:none;background:#ef4444;color:#fff;border-radius:10px;font-size:11px;font-weight:700;padding:1px 6px;margin-left:2px;vertical-align:middle;">0</span></a></li>
      <li><a href="mon-compte.php" style="font-weight:600;">Mon compte</a></li>
      <?php if (!empty($_SESSION['is_admin'])): ?>
      <li><a href="admin/dashboard.php" style="background:#9f1239;color:#fff;padding:6px 14px;border-radius:15px;"><i class="fa-solid fa-shield-halved"></i> Admin</a></li>
      <?php endif; ?>
      <?php else: ?>
      <li><a href="Authentification.html">Inscription / Connexion</a></li>
      <?php endif; ?>
    </ul>
  </div>
</header>

<!-- ===== 1. HERO ===== -->
<section class="hero">
  <div class="hero-bg"></div>
  <div class="hero-content">
    <h1>Trouvez votre logement étudiant</h1>
    <p>Des milliers d'annonces partout en France — studios, appartements, colocations</p>
    <form class="hero-form" method="GET" action="Accueil.php" id="hero-search-form">

      <!-- Ligne principale -->
      <div class="hero-form-row">
        <input type="text" name="ville" placeholder="🏙 Ville (Paris, Lyon…)"
               value="<?php echo htmlspecialchars($f_ville); ?>">
        <select name="type">
          <option value="">Tous les types</option>
          <?php foreach (['studio','appartement','chambre','maison','residence'] as $t): ?>
          <option value="<?php echo $t; ?>" <?php echo $f_type===$t?'selected':''; ?>>
            <?php echo ucfirst($t); ?>
          </option>
          <?php endforeach; ?>
        </select>
        <input type="number" name="budget" placeholder="💶 Budget max €" min="0"
               value="<?php echo $f_budget ?: ''; ?>">
        <button type="submit" class="hero-btn">
          <i class="fa-solid fa-magnifying-glass" style="margin-right:6px"></i>Rechercher
        </button>
      </div>

      <?php $advOpen = $f_superficie_min || $f_superficie_max || $f_type_offre || $f_type_proprio
                    || $f_apl || !empty($f_equipements) || $f_meuble !== '' || $f_nb_pieces
                    || $f_duree || $f_charges || $f_animaux || $f_fumeur || $f_pmr; ?>
      <!-- Toggle centré, sur sa propre ligne -->
      <div class="hero-adv-toggle-row">
        <button type="button" class="hero-adv-toggle <?php echo $advOpen ? 'is-open' : ''; ?>"
                id="adv-toggle" onclick="toggleAdv()">
          <i class="fa-solid <?php echo $advOpen ? 'fa-chevron-up' : 'fa-sliders'; ?>" id="adv-icon"></i>
          <span id="adv-label"><?php echo $advOpen ? 'Masquer les filtres' : 'Filtres avancés'; ?></span>
          <?php if ($advOpen): ?>
            <span style="background:#30bae6;border-radius:10px;font-size:10px;padding:1px 7px;margin-left:2px">actifs</span>
          <?php endif; ?>
        </button>
      </div>

      <!-- Panneau avancé (hors du flex row) -->
      <div class="hero-adv-panel <?php echo $advOpen ? 'open' : ''; ?>" id="adv-panel">

        <p class="adv-divider">Surface</p>
        <div class="adv-row">
          <div class="adv-group">
            <label for="adv-surf-min">Superficie min (m²)</label>
            <input type="number" id="adv-surf-min" name="superficie_min" min="0" placeholder="Ex : 15"
                   value="<?php echo $f_superficie_min ?: ''; ?>">
          </div>
          <div class="adv-group">
            <label for="adv-surf-max">Superficie max (m²)</label>
            <input type="number" id="adv-surf-max" name="superficie_max" min="0" placeholder="Ex : 80"
                   value="<?php echo $f_superficie_max ?: ''; ?>">
          </div>
        </div>

        <p class="adv-divider">Type d'offre &amp; Propriétaire</p>
        <div class="adv-row">
          <div class="adv-group">
            <label for="adv-offre">Type d'offre</label>
            <select id="adv-offre" name="type_offre">
              <option value="">Tous</option>
              <option value="location"   <?php echo $f_type_offre==='location'  ?'selected':''; ?>>Location</option>
              <option value="colocation" <?php echo $f_type_offre==='colocation'?'selected':''; ?>>Colocation</option>
            </select>
          </div>
          <div class="adv-group">
            <label for="adv-proprio">Type de propriétaire</label>
            <select id="adv-proprio" name="type_proprio">
              <option value="">Tous</option>
              <option value="particulier" <?php echo $f_type_proprio==='particulier'?'selected':''; ?>>Particulier</option>
              <option value="agence"      <?php echo $f_type_proprio==='agence'     ?'selected':''; ?>>Agence</option>
            </select>
          </div>
        </div>

        <p class="adv-divider">Meublé &amp; Pièces</p>
        <div class="adv-row">
          <div class="adv-group">
            <label for="adv-meuble">Ameublement</label>
            <select id="adv-meuble" name="meuble">
              <option value="">Indifférent</option>
              <option value="1" <?php echo $f_meuble==='1'?'selected':''; ?>>Meublé</option>
              <option value="0" <?php echo $f_meuble==='0'?'selected':''; ?>>Non meublé</option>
            </select>
          </div>
          <div class="adv-group">
            <label for="adv-pieces">Nombre de pièces (min)</label>
            <select id="adv-pieces" name="nb_pieces">
              <option value="">Indifférent</option>
              <?php for ($p = 1; $p <= 5; $p++): ?>
              <option value="<?php echo $p; ?>" <?php echo $f_nb_pieces===$p?'selected':''; ?>>
                <?php echo $p . ($p === 5 ? '+' : '') . ' pièce' . ($p > 1 ? 's' : ''); ?>
              </option>
              <?php endfor; ?>
            </select>
          </div>
        </div>

        <p class="adv-divider">Durée de location</p>
        <div class="adv-row">
          <div class="adv-group" style="grid-column: 1 / -1;">
            <label for="adv-duree">Durée minimum</label>
            <select id="adv-duree" name="duree">
              <option value="">Indifférente</option>
              <?php foreach (['1 mois','3 mois','6 mois','9 mois','1 an','2 ans'] as $d): ?>
              <option value="<?php echo $d; ?>" <?php echo $f_duree===$d?'selected':''; ?>>
                <?php echo $d; ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <p class="adv-divider">Règles du logement</p>
        <div class="adv-checks">
          <label class="adv-check-item">
            <input type="checkbox" name="charges" value="1" <?php echo $f_charges?'checked':''; ?>>
            <i class="fa-solid fa-receipt" style="color:#30bae6"></i>
            Charges incluses
          </label>
          <label class="adv-check-item">
            <input type="checkbox" name="animaux" value="1" <?php echo $f_animaux?'checked':''; ?>>
            <i class="fa-solid fa-paw" style="color:#30bae6"></i>
            Animaux acceptés
          </label>
          <label class="adv-check-item">
            <input type="checkbox" name="fumeur" value="1" <?php echo $f_fumeur?'checked':''; ?>>
            <i class="fa-solid fa-smoking" style="color:#30bae6"></i>
            Fumeur autorisé
          </label>
          <label class="adv-check-item">
            <input type="checkbox" name="pmr" value="1" <?php echo $f_pmr?'checked':''; ?>>
            <i class="fa-solid fa-wheelchair" style="color:#30bae6"></i>
            Accessible PMR
          </label>
        </div>

        <p class="adv-divider">Équipements</p>
        <div class="adv-checks">
          <?php
          $equip_map = [
            'wifi'             => ['fa-wifi',           'Wi-Fi'],
            'machine_a_laver'  => ['fa-shirt',          'Lave-linge'],
            'lave_vaisselle'   => ['fa-jug-detergent',  'Lave-vaisselle'],
            'micro_onde'       => ['fa-square',         'Micro-ondes'],
            'four'             => ['fa-fire',           'Four'],
            'frigo'            => ['fa-snowflake',      'Réfrigérateur'],
            'cuisine_equipee'  => ['fa-kitchen-set',    'Cuisine équipée'],
            'ustensiles'       => ['fa-utensils',       'Ustensiles'],
            'chauffage'        => ['fa-temperature-high','Chauffage'],
            'climatisation'    => ['fa-fan',            'Climatisation'],
            'balcon'           => ['fa-house-chimney-window', 'Balcon'],
            'terrasse'         => ['fa-umbrella-beach', 'Terrasse'],
            'jardin'           => ['fa-tree',           'Jardin'],
            'cave'             => ['fa-box-archive',    'Cave'],
            'ascenseur'        => ['fa-elevator',       'Ascenseur'],
            'parking'          => ['fa-square-parking', 'Parking'],
          ];
          foreach ($equip_map as $val => [$icon, $lbl]):
            $checked = in_array($val, $f_equipements) ? 'checked' : '';
          ?>
          <label class="adv-check-item">
            <input type="checkbox" name="equipements[]" value="<?php echo $val; ?>" <?php echo $checked; ?>>
            <i class="fa-solid <?php echo $icon; ?>" style="color:#30bae6"></i>
            <?php echo $lbl; ?>
          </label>
          <?php endforeach; ?>
        </div>

        <div class="adv-apl-row">
          <label class="adv-check-item" style="display:inline-flex;border:none;background:none;padding:0">
            <input type="checkbox" name="apl" value="1" <?php echo $f_apl ? 'checked' : ''; ?>>
            <i class="fa-solid fa-hand-holding-heart" style="color:#30bae6"></i>
            APL accepté uniquement
          </label>
        </div>

        <div class="adv-submit-row">
          <a href="Accueil.php" class="adv-reset-btn">Effacer tout</a>
          <button type="submit" class="adv-apply-btn">
            <i class="fa-solid fa-magnifying-glass" style="margin-right:6px"></i>Appliquer
          </button>
        </div>
      </div>
    </form>
  </div>
</section>

<?php if ($erreur_acces): ?>
<div style="background:#fef2f2;border-left:4px solid #ef4444;padding:12px 40px;font-size:14px;color:#b91c1c;display:flex;align-items:center;gap:10px;">
  <i class="fa-solid fa-circle-exclamation"></i> <?php echo $erreur_acces; ?>
</div>
<?php endif; ?>

<!-- ===== 2. STATS ===== -->
<section class="stats-section">
  <div class="stat-box">
    <span class="stat-icon"><i class="fa-solid fa-house-chimney"></i></span>
    <span class="stat-num"><?php echo number_format($totalAnnonces, 0, ',', ' '); ?></span>
    <span class="stat-label">Annonces actives</span>
  </div>
  <div class="stat-box">
    <span class="stat-icon"><i class="fa-solid fa-location-dot"></i></span>
    <span class="stat-num"><?php echo $totalVilles; ?></span>
    <span class="stat-label">Villes disponibles</span>
  </div>
  <div class="stat-box">
    <span class="stat-icon"><i class="fa-solid fa-user-graduate"></i></span>
    <span class="stat-num"><?php echo number_format($totalEtudiants, 0, ',', ' '); ?></span>
    <span class="stat-label">Étudiants inscrits</span>
  </div>
</section>

<!-- ===== 3. DERNIÈRES ANNONCES ===== -->
<section class="section-dernieres">
  <div class="section-title">
    <h2>
      <?php echo $filtresActifs ? 'Résultats de votre recherche' : 'Dernières annonces'; ?>
    </h2>
    <?php if ($filtresActifs): ?>
      <a href="Accueil.php">Réinitialiser les filtres</a>
    <?php else: ?>
      <a href="Annonces.html">Voir toutes les annonces →</a>
    <?php endif; ?>
  </div>

  <?php if ($filtresActifs): ?>
  <div class="filter-banner">
    <i class="fa-solid fa-filter"></i>
    <?php
      $tags = [];
      if ($f_ville)          $tags[] = '📍 ' . htmlspecialchars($f_ville);
      if ($f_type)           $tags[] = '🏠 ' . ucfirst($f_type);
      if ($f_budget)         $tags[] = '💶 Max ' . number_format($f_budget, 0, ',', ' ') . ' €';
      if ($f_superficie_min) $tags[] = '📐 Min ' . $f_superficie_min . ' m²';
      if ($f_superficie_max) $tags[] = '📐 Max ' . $f_superficie_max . ' m²';
      if ($f_type_offre)     $tags[] = '🔑 ' . ucfirst($f_type_offre);
      if ($f_type_proprio)   $tags[] = '👤 ' . ucfirst($f_type_proprio);
      if ($f_apl)            $tags[] = '🤝 APL accepté';
      if ($f_meuble === '1') $tags[] = '🛋 Meublé';
      if ($f_meuble === '0') $tags[] = '📦 Non meublé';
      if ($f_nb_pieces)      $tags[] = '🚪 ≥ ' . $f_nb_pieces . ' pièce' . ($f_nb_pieces>1?'s':'');
      if ($f_duree)          $tags[] = '📅 ' . $f_duree;
      if ($f_charges)        $tags[] = '🧾 Charges incluses';
      if ($f_animaux)        $tags[] = '🐾 Animaux OK';
      if ($f_fumeur)         $tags[] = '🚬 Fumeur OK';
      if ($f_pmr)            $tags[] = '♿ PMR';
      foreach ($f_equipements as $eq) {
          $equip_map_labels = [
              'wifi'=>'Wi-Fi','machine_a_laver'=>'Lave-linge','lave_vaisselle'=>'Lave-vaisselle',
              'micro_onde'=>'Micro-ondes','four'=>'Four','frigo'=>'Frigo',
              'cuisine_equipee'=>'Cuisine équipée','ustensiles'=>'Ustensiles',
              'chauffage'=>'Chauffage','climatisation'=>'Clim',
              'balcon'=>'Balcon','terrasse'=>'Terrasse','jardin'=>'Jardin','cave'=>'Cave',
              'ascenseur'=>'Ascenseur','parking'=>'Parking'
          ];
          $tags[] = '✓ ' . ($equip_map_labels[$eq] ?? $eq);
      }
      echo implode(' &nbsp;·&nbsp; ', $tags);
      echo ' &nbsp;—&nbsp; <strong>' . count($annonces) . ' résultat' . (count($annonces) > 1 ? 's' : '') . '</strong>';
    ?>
    <a href="Accueil.php">✕ Effacer</a>
  </div>
  <?php endif; ?>

  <div class="cards-grid">
    <?php $liste = $filtresActifs ? $annonces : $dernieres; ?>
    <?php if (empty($liste)): ?>
    <div class="no-annonces">
      <i class="fa-solid fa-house-circle-xmark"></i>
      Aucune annonce trouvée.
      <?php if ($filtresActifs): ?>
        <br><a href="Accueil.php" style="color:#30bae6;font-size:13px;">Réinitialiser les filtres</a>
      <?php endif; ?>
    </div>
    <?php else: ?>
    <?php
      $retour = [];
      if ($f_ville)          $retour['ville']          = $f_ville;
      if ($f_type)           $retour['type']           = $f_type;
      if ($f_budget)         $retour['budget']         = $f_budget;
      if ($f_superficie_min) $retour['superficie_min'] = $f_superficie_min;
      if ($f_superficie_max) $retour['superficie_max'] = $f_superficie_max;
      if ($f_type_offre)     $retour['type_offre']     = $f_type_offre;
      if ($f_type_proprio)   $retour['type_proprio']   = $f_type_proprio;
      if ($f_apl)            $retour['apl']            = 1;
      if ($f_meuble !== '')  $retour['meuble']         = $f_meuble;
      if ($f_nb_pieces)      $retour['nb_pieces']      = $f_nb_pieces;
      if ($f_duree)          $retour['duree']          = $f_duree;
      if ($f_charges)        $retour['charges']        = 1;
      if ($f_animaux)        $retour['animaux']        = 1;
      if ($f_fumeur)         $retour['fumeur']         = 1;
      if ($f_pmr)            $retour['pmr']            = 1;
      if (!empty($f_equipements)) $retour['equipements'] = $f_equipements;
      $retour_qs = $retour ? '&' . http_build_query($retour) : '';
    ?>
    <?php foreach ($liste as $a):
      $img  = (!empty($a['image_principale']) && file_exists(__DIR__.'/'.$a['image_principale']))
              ? htmlspecialchars($a['image_principale'])
              : 'img/studio2.jpg';
      $prix = number_format($a['prix'], 0, ',', ' ');
      $url  = 'annonce.php?id=' . urlencode($a['id_annonce']) . $retour_qs;
    ?>
    <div class="annonce-card" onclick="location.href='<?php echo $url; ?>'" style="cursor:pointer">
      <div class="annonce-card-img">
        <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($a['ville']); ?>" loading="lazy">
        <span class="annonce-type-badge"><?php echo htmlspecialchars(ucfirst($a['type_immeuble'])); ?></span>
        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="annonce-card-actions" onclick="event.stopPropagation()">
          <?php $isFav = in_array($a['id_annonce'], $mesFavoris, true); ?>
          <button type="button" class="annonce-card-action-btn heart <?php echo $isFav ? 'is-fav' : ''; ?>"
                  onclick="toggleCardFavori('<?php echo htmlspecialchars($a['id_annonce']); ?>', this, event)"
                  title="<?php echo $isFav ? 'Retirer des favoris' : 'Ajouter aux favoris'; ?>">
            <i class="<?php echo $isFav ? 'fa-solid' : 'fa-regular'; ?> fa-heart"></i>
          </button>
          <?php if (!empty($a['utilisateur_id']) && $a['utilisateur_id'] !== $_SESSION['user_id']): ?>
          <a class="annonce-card-action-btn" href="profil.php?id=<?php echo urlencode($a['utilisateur_id']); ?>"
             title="Voir le profil du propriétaire">
            <i class="fa-solid fa-user"></i>
          </a>
          <button type="button" class="annonce-card-action-btn flag"
                  onclick="openCardReport('<?php echo htmlspecialchars($a['id_annonce']); ?>', event)"
                  title="Signaler l'annonce">
            <i class="fa-solid fa-flag"></i>
          </button>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
      <div class="annonce-card-body">
        <p class="annonce-card-prix"><?php echo $prix; ?> € <span>/ mois</span></p>
        <p class="annonce-card-ville">
          <i class="fa-solid fa-location-dot"></i>
          <?php echo htmlspecialchars($a['ville']); ?> · <?php echo htmlspecialchars($a['code_postal']); ?>
        </p>
        <p class="annonce-card-desc"><?php echo htmlspecialchars($a['description']); ?></p>
      </div>
      <div class="annonce-card-footer">
        <span><i class="fa-solid fa-vector-square"></i> <?php echo $a['superficie']; ?> m²</span>
        <span><i class="fa-solid fa-key"></i> <?php echo ucfirst($a['type_offre']); ?></span>
        <?php if (!empty($a['date_publication'])): ?>
        <span style="margin-left:auto"><?php echo date('d/m/Y', strtotime($a['date_publication'])); ?></span>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>


<!-- ===== FOOTER ===== -->
<footer>
  <div class="footer-top">
    <div class="footer-left">
      <img src="img/iconSite_WhiteText.png" class="logo-footer" alt="logo" />
    </div>
    <div class="footer-center">
      <p class="footer-text">
        Ce site a été conçu et développé par les élèves de l'ISEP du groupe G9A 2025/2026 — Tous droits réservés
      </p>
    </div>
  </div>
  <div class="footer-bottom">
    <a href="Mentionlegales.php">Mentions légales et CGU</a>
    <p>-</p>
    <a href="GestionCookies.html">Gestion des cookies</a>
  </div>
</footer>

<?php if (isset($_SESSION['user_id'])): ?>
<!-- ══ Modal Signaler (annonces des cards) ══ -->
<div id="card-report-modal" onclick="if(event.target===this) closeCardReport()"
     style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.6);align-items:center;justify-content:center;padding:20px;">
  <div style="background:#fff;border-radius:14px;width:100%;max-width:460px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.3);">
    <div style="padding:18px 22px 14px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;gap:10px;">
      <i class="fa-solid fa-flag" style="color:#dc2626"></i>
      <h3 style="margin:0;font-size:16px;font-weight:700;">Signaler cette annonce</h3>
    </div>
    <div style="padding:18px 22px;">
      <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Raison</label>
      <select id="card-report-reason" style="width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;margin-bottom:14px;">
        <option value="">— Choisir —</option>
        <option value="spam">Spam ou publicité</option>
        <option value="fausse_annonce">Fausse annonce</option>
        <option value="arnaque">Tentative d'arnaque</option>
        <option value="contenu_inapproprie">Contenu inapproprié</option>
        <option value="autre">Autre raison</option>
      </select>
      <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Commentaire (facultatif)</label>
      <textarea id="card-report-comment" maxlength="1000"
                style="width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;resize:vertical;min-height:80px;"></textarea>
      <div id="card-report-msg" style="font-size:12px;margin-top:8px;padding:8px 10px;border-radius:6px;display:none;"></div>
    </div>
    <div style="display:flex;justify-content:flex-end;gap:8px;padding:12px 22px 18px;">
      <button onclick="closeCardReport()" style="padding:10px 18px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;background:transparent;border:1px solid #e2e8f0;color:#1e293b;">Annuler</button>
      <button id="card-report-submit" onclick="submitCardReport()"
              style="padding:10px 18px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;background:transparent;border:1px solid #fecaca;color:#dc2626;">
        <i class="fa-solid fa-flag" style="margin-right:5px"></i>Envoyer
      </button>
    </div>
  </div>
</div>
<script>
let currentReportId = null;
function openCardReport(annonceId, ev) {
  if (ev) ev.stopPropagation();
  currentReportId = annonceId;
  document.getElementById('card-report-reason').value  = '';
  document.getElementById('card-report-comment').value = '';
  document.getElementById('card-report-msg').style.display = 'none';
  document.getElementById('card-report-submit').disabled = false;
  document.getElementById('card-report-modal').style.display = 'flex';
}
function closeCardReport() {
  document.getElementById('card-report-modal').style.display = 'none';
  currentReportId = null;
}
async function submitCardReport() {
  const raison = document.getElementById('card-report-reason').value;
  const comm   = document.getElementById('card-report-comment').value.trim();
  const msg    = document.getElementById('card-report-msg');
  const btn    = document.getElementById('card-report-submit');
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
      body: JSON.stringify({ cible_type:'annonce', cible_id: currentReportId, raison, commentaire: comm }),
    });
    const d = await r.json();
    if (d.ok) {
      msg.style.cssText = 'font-size:12px;margin-top:8px;padding:8px 10px;border-radius:6px;display:block;background:#ecfdf5;color:#065f46;';
      msg.textContent = 'Merci, signalement envoyé.';
      setTimeout(closeCardReport, 1600);
    } else {
      msg.style.cssText = 'font-size:12px;margin-top:8px;padding:8px 10px;border-radius:6px;display:block;background:#fef2f2;color:#dc2626;';
      msg.textContent = ({deja_signale:'Déjà signalé.', non_connecte:'Vous devez être connecté.'})[d.error] || 'Erreur, réessayez.';
      btn.disabled = false;
    }
  } catch(e) {
    msg.style.cssText = 'font-size:12px;margin-top:8px;padding:8px 10px;border-radius:6px;display:block;background:#fef2f2;color:#dc2626;';
    msg.textContent = 'Erreur réseau.';
    btn.disabled = false;
  }
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeCardReport(); });
</script>
<?php endif; ?>

<script>
  // Toggle favori depuis une card
  async function toggleCardFavori(annonceId, btn, ev) {
    if (ev) ev.stopPropagation();
    try {
      const r = await fetch('api/favoris.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ annonce_id: annonceId }),
      });
      const d = await r.json();
      if (d.ok) {
        const icon = btn.querySelector('i');
        if (d.favori) {
          btn.classList.add('is-fav');
          icon.classList.remove('fa-regular');
          icon.classList.add('fa-solid');
          btn.title = 'Retirer des favoris';
        } else {
          btn.classList.remove('is-fav');
          icon.classList.remove('fa-solid');
          icon.classList.add('fa-regular');
          btn.title = 'Ajouter aux favoris';
        }
      } else if (d.error === 'non_connecte') {
        location.href = 'Authentification.html';
      }
    } catch(e) {}
  }

  // Toggle filtres avancés
  function toggleAdv() {
    const panel  = document.getElementById('adv-panel');
    const toggle = document.getElementById('adv-toggle');
    const label  = document.getElementById('adv-label');
    const icon   = document.getElementById('adv-icon');
    const open   = panel.classList.toggle('open');
    toggle.classList.toggle('is-open', open);
    label.textContent = open ? 'Masquer les filtres' : 'Filtres avancés';
    icon.className    = open ? 'fa-solid fa-chevron-up' : 'fa-solid fa-sliders';
  }

  // Badge messages non lus
  <?php if (isset($_SESSION['user_id'])): ?>
  async function refreshUnread() {
    try {
      const r = await fetch('api/messages.php?action=unread');
      const d = await r.json();
      const b = document.getElementById('nav-unread');
      if (d.count > 0) { b.textContent = d.count; b.style.display = 'inline'; }
      else b.style.display = 'none';
    } catch(e) {}
  }
  refreshUnread();
  setInterval(refreshUnread, 15000);
  <?php endif; ?>
</script>

</body>
</html>
