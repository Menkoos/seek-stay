<?php
require 'session.php';

// ── Filtres (mêmes paramètres que l'accueil) ──────────────────────────────
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
$tri              = $_GET['tri'] ?? 'recent';

try {
    $where  = ["statut = 'actif'"];
    $params = [];
    if ($f_ville)          { $where[] = "ville LIKE ?";         $params[] = '%'.$f_ville.'%'; }
    if ($f_type)           { $where[] = "type_immeuble = ?";    $params[] = $f_type; }
    if ($f_budget)         { $where[] = "prix <= ?";            $params[] = $f_budget; }
    if ($f_superficie_min) { $where[] = "superficie >= ?";      $params[] = $f_superficie_min; }
    if ($f_superficie_max) { $where[] = "superficie <= ?";      $params[] = $f_superficie_max; }
    if ($f_type_offre)     { $where[] = "type_offre = ?";       $params[] = $f_type_offre; }
    if ($f_type_proprio)   { $where[] = "type_proprio = ?";     $params[] = $f_type_proprio; }
    if ($f_apl)            { $where[] = "apl_accepte = 1"; }
    if ($f_meuble === '1') { $where[] = "meuble = 1"; }
    if ($f_meuble === '0') { $where[] = "(meuble = 0 OR meuble IS NULL)"; }
    if ($f_nb_pieces)      { $where[] = "nb_pieces >= ?"; $params[] = $f_nb_pieces; }
    if ($f_duree)          { $where[] = "duree_min = ?"; $params[] = $f_duree; }
    if ($f_charges)        { $where[] = "charges_incluses = 1"; }
    if ($f_animaux)        { $where[] = "animaux_acceptes = 1"; }
    if ($f_fumeur)         { $where[] = "fumeur_autorise = 1"; }
    if ($f_pmr)            { $where[] = "accessible_pmr = 1"; }
    foreach ($f_equipements as $eq) {
        $eq = trim($eq);
        if ($eq) { $where[] = "equipements LIKE ?"; $params[] = '%'.$eq.'%'; }
    }

    $orderBy = match ($tri) {
        'prix_asc'  => 'prix ASC',
        'prix_desc' => 'prix DESC',
        'surface'   => 'superficie DESC',
        default     => 'date_publication DESC',
    };

    $sql  = "SELECT * FROM annonces WHERE " . implode(' AND ', $where) . " ORDER BY $orderBy";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $annonces = $stmt->fetchAll();
} catch (PDOException $e) { $annonces = []; }

$filtresActifs = $f_ville || $f_type || $f_budget || $f_superficie_min || $f_superficie_max
              || $f_type_offre || $f_type_proprio || $f_apl || !empty($f_equipements)
              || $f_meuble !== '' || $f_nb_pieces || $f_duree || $f_charges
              || $f_animaux || $f_fumeur || $f_pmr;

// Favoris de l'utilisateur connecté
$mesFavoris = [];
if (isset($_SESSION['user_id'])) {
    try {
        $s = $pdo->prepare("SELECT annonce_id FROM favoris WHERE utilisateur_id = ?");
        $s->execute([$_SESSION['user_id']]);
        $mesFavoris = $s->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {}
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Annonces — Seek &amp; Stay</title>
  <link rel="icon" type="image/x-icon" href="img/flavicon.png" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
  <link rel="stylesheet" href="styles/styles.css" />
  <style>
    body { background: #f1f5f9; font-family: 'Inter', sans-serif; margin: 0; }

    .annonces-page { max-width: 1200px; margin: 28px auto; padding: 0 24px 60px; }

    .page-title {
      display: flex; justify-content: space-between; align-items: center;
      flex-wrap: wrap; gap: 12px; margin-bottom: 22px;
    }
    .page-title h1 { font-size: 1.8rem; font-weight: 800; color: #0f1c2e; margin: 0; }
    .page-title .count { font-size: 14px; color: #64748b; font-weight: 500; }

    /* ── Filtres ── */
    .filters {
      background: #fff; border-radius: 14px; padding: 22px 24px;
      box-shadow: 0 1px 3px rgba(0,0,0,.05); margin-bottom: 24px;
    }
    .filter-row {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 12px; margin-bottom: 14px;
    }
    .filter-group label {
      display: block; font-size: 11px; font-weight: 700; color: #475569;
      text-transform: uppercase; letter-spacing: .4px; margin-bottom: 5px;
    }
    .filter-group input,
    .filter-group select {
      width: 100%; box-sizing: border-box; padding: 9px 12px;
      border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px;
      font-family: inherit; background: #fff; outline: none;
      transition: border-color .15s;
    }
    .filter-group input:focus,
    .filter-group select:focus { border-color: #30bae6; box-shadow: 0 0 0 2px rgba(48,186,230,.15); }

    .toggle-adv {
      background: none; border: 1px dashed #cbd5e1; color: #475569;
      padding: 8px 16px; border-radius: 8px; font-size: 12px;
      font-weight: 600; cursor: pointer; font-family: inherit;
      display: inline-flex; align-items: center; gap: 6px;
      margin-top: 4px; transition: border-color .15s, color .15s;
    }
    .toggle-adv:hover { border-color: #30bae6; color: #30bae6; }

    .adv-wrap {
      display: none; margin-top: 18px; padding-top: 18px;
      border-top: 1px solid #f1f5f9;
    }
    .adv-wrap.open { display: block; }
    .adv-divider {
      font-size: 11px; font-weight: 700; color: #94a3b8;
      text-transform: uppercase; letter-spacing: .5px; margin: 14px 0 10px;
    }
    .adv-divider:first-child { margin-top: 0; }
    .adv-checks {
      display: grid; grid-template-columns: repeat(auto-fill, minmax(145px, 1fr));
      gap: 8px;
    }
    .adv-check-item {
      display: flex; align-items: center; gap: 7px; font-size: 13px;
      color: #334155; padding: 7px 10px; border: 1px solid #e2e8f0;
      background: #f8fafc; border-radius: 8px; cursor: pointer; user-select: none;
      transition: border-color .15s, background .15s;
    }
    .adv-check-item:hover { border-color: #30bae6; background: #f0fbff; }
    .adv-check-item input { accent-color: #30bae6; width: 15px; height: 15px; flex-shrink: 0; }

    .filter-actions {
      display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px;
      padding-top: 16px; border-top: 1px solid #f1f5f9;
    }
    .btn-reset, .btn-apply {
      padding: 9px 22px; border-radius: 8px; font-size: 13px; font-weight: 700;
      font-family: inherit; cursor: pointer; border: 1px solid transparent;
      text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-reset { background: transparent; border-color: #e2e8f0; color: #64748b; }
    .btn-reset:hover { border-color: #94a3b8; color: #334155; }
    .btn-apply { background: #244676; color: #fff; border: none; }
    .btn-apply:hover { background: #1a3459; }

    /* ── Cards grid (copié de l'accueil) ── */
    .cards-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; }
    .annonce-card {
      border-radius: 16px; overflow: hidden; background: #fff;
      box-shadow: 0 2px 8px rgba(0,0,0,.08);
      transition: transform .18s, box-shadow .18s;
      cursor: pointer; display: flex; flex-direction: column;
    }
    .annonce-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,.13); }
    .annonce-card-img { width: 100%; height: 190px; overflow: hidden; position: relative; }
    .annonce-card-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .3s; }
    .annonce-card:hover .annonce-card-img img { transform: scale(1.04); }
    .annonce-type-badge {
      position: absolute; top: 12px; left: 12px;
      background: #244676; color: #fff; font-size: 11px; font-weight: 700;
      padding: 4px 10px; border-radius: 20px; text-transform: capitalize;
    }
    .annonce-card-actions {
      position: absolute; top: 10px; right: 10px;
      display: flex; gap: 6px; opacity: 0; transition: opacity .2s;
    }
    .annonce-card:hover .annonce-card-actions { opacity: 1; }
    .annonce-card-action-btn {
      width: 32px; height: 32px; border-radius: 50%; border: none;
      background: rgba(255,255,255,.95); color: #244676; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      font-size: 13px; box-shadow: 0 2px 6px rgba(0,0,0,.15);
      transition: background .15s, color .15s, transform .1s; text-decoration: none;
    }
    .annonce-card-action-btn:hover { background: #fff; transform: scale(1.08); }
    .annonce-card-action-btn.flag:hover { color: #dc2626; }
    .annonce-card-action-btn.heart { color: #ef4444; }
    .annonce-card-action-btn.heart.is-fav { background: #ef4444; color: #fff; }

    .annonce-card-body { padding: 14px 16px; flex: 1; }
    .annonce-card-prix { font-size: 1.1rem; font-weight: 800; color: #244676; margin: 0 0 4px; }
    .annonce-card-prix span { font-size: 12px; font-weight: 400; color: #64748b; }
    .annonce-card-ville { font-size: 12px; color: #64748b; display: flex; align-items: center; gap: 5px; margin-bottom: 6px; }
    .annonce-card-desc {
      font-size: 12px; color: #475569; line-height: 1.5;
      display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .annonce-card-footer {
      padding: 8px 16px 12px; display: flex; align-items: center; gap: 12px;
      font-size: 11px; color: #94a3b8; border-top: 1px solid #f1f5f9;
    }
    .annonce-card-footer span { display: flex; align-items: center; gap: 3px; }

    .no-results {
      grid-column: 1/-1; background: #fff; border-radius: 14px;
      padding: 60px 30px; text-align: center; color: #64748b;
    }
    .no-results i { font-size: 42px; color: #cbd5e1; display: block; margin-bottom: 12px; }

    @media (max-width: 900px) { .cards-grid { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 600px) { .cards-grid { grid-template-columns: 1fr; } }
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
      <li><a href="Annonces.php" class="active">Annonces</a></li>
      <li><a href="Favoris.php">Favoris</a></li>
      <?php if (!isset($_SESSION['user_id']) || ($_SESSION['role_type'] ?? '') === 'proprietaire'): ?>
      <li><a href="Publier.php">Publier</a></li>
      <?php endif; ?>
      <li><a href="Contact.html">Contact</a></li>
      <li><a href="FAQ.html">FAQ</a></li>
      <?php if (isset($_SESSION['user_id'])): ?>
      <li><a href="messagerie.php">Messages</a></li>
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

<div class="annonces-page">

  <div class="page-title">
    <h1>Toutes les annonces</h1>
    <span class="count"><?php echo count($annonces); ?> résultat<?php echo count($annonces)>1?'s':''; ?></span>
  </div>

  <!-- ══ Filtres ══ -->
  <form class="filters" method="GET" action="Annonces.php" id="filters-form">

    <!-- Ligne 1 : filtres principaux -->
    <div class="filter-row">
      <div class="filter-group">
        <label>Ville</label>
        <input type="text" name="ville" placeholder="Paris, Lyon…" value="<?php echo htmlspecialchars($f_ville); ?>">
      </div>
      <div class="filter-group">
        <label>Type de logement</label>
        <select name="type">
          <option value="">Tous</option>
          <?php foreach (['studio','appartement','chambre','maison','residence'] as $t): ?>
          <option value="<?php echo $t; ?>" <?php echo $f_type===$t?'selected':''; ?>><?php echo ucfirst($t); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="filter-group">
        <label>Budget max (€)</label>
        <input type="number" name="budget" min="0" placeholder="ex: 700" value="<?php echo $f_budget ?: ''; ?>">
      </div>
      <div class="filter-group">
        <label>Trier par</label>
        <select name="tri">
          <option value="recent"    <?php echo $tri==='recent'   ?'selected':''; ?>>Plus récent</option>
          <option value="prix_asc"  <?php echo $tri==='prix_asc' ?'selected':''; ?>>Prix croissant</option>
          <option value="prix_desc" <?php echo $tri==='prix_desc'?'selected':''; ?>>Prix décroissant</option>
          <option value="surface"   <?php echo $tri==='surface'  ?'selected':''; ?>>Plus grande surface</option>
        </select>
      </div>
    </div>

    <button type="button" class="toggle-adv" onclick="document.getElementById('adv-wrap').classList.toggle('open');this.querySelector('i').classList.toggle('fa-chevron-down');this.querySelector('i').classList.toggle('fa-chevron-up');">
      <i class="fa-solid <?php
        $advOpen = $f_superficie_min || $f_superficie_max || $f_type_offre || $f_type_proprio
                || $f_apl || !empty($f_equipements) || $f_meuble !== '' || $f_nb_pieces
                || $f_duree || $f_charges || $f_animaux || $f_fumeur || $f_pmr;
        echo $advOpen ? 'fa-chevron-up' : 'fa-chevron-down';
      ?>"></i>
      Filtres avancés
    </button>

    <div class="adv-wrap <?php echo $advOpen ? 'open' : ''; ?>" id="adv-wrap">

      <p class="adv-divider">Surface &amp; Caractéristiques</p>
      <div class="filter-row">
        <div class="filter-group">
          <label>Superficie min (m²)</label>
          <input type="number" name="superficie_min" min="0" value="<?php echo $f_superficie_min ?: ''; ?>">
        </div>
        <div class="filter-group">
          <label>Superficie max (m²)</label>
          <input type="number" name="superficie_max" min="0" value="<?php echo $f_superficie_max ?: ''; ?>">
        </div>
        <div class="filter-group">
          <label>Pièces minimum</label>
          <select name="nb_pieces">
            <option value="">Indifférent</option>
            <?php for ($p = 1; $p <= 5; $p++): ?>
            <option value="<?php echo $p; ?>" <?php echo $f_nb_pieces===$p?'selected':''; ?>><?php echo $p . ($p===5?'+':''); ?> pièce<?php echo $p>1?'s':''; ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="filter-group">
          <label>Ameublement</label>
          <select name="meuble">
            <option value="">Indifférent</option>
            <option value="1" <?php echo $f_meuble==='1'?'selected':''; ?>>Meublé</option>
            <option value="0" <?php echo $f_meuble==='0'?'selected':''; ?>>Non meublé</option>
          </select>
        </div>
      </div>

      <p class="adv-divider">Type d'offre &amp; Durée</p>
      <div class="filter-row">
        <div class="filter-group">
          <label>Type d'offre</label>
          <select name="type_offre">
            <option value="">Tous</option>
            <option value="location"   <?php echo $f_type_offre==='location'  ?'selected':''; ?>>Location</option>
            <option value="colocation" <?php echo $f_type_offre==='colocation'?'selected':''; ?>>Colocation</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Propriétaire</label>
          <select name="type_proprio">
            <option value="">Tous</option>
            <option value="particulier" <?php echo $f_type_proprio==='particulier'?'selected':''; ?>>Particulier</option>
            <option value="agence"      <?php echo $f_type_proprio==='agence'     ?'selected':''; ?>>Agence</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Durée min</label>
          <select name="duree">
            <option value="">Indifférente</option>
            <?php foreach (['1 mois','3 mois','6 mois','9 mois','1 an','2 ans'] as $d): ?>
            <option value="<?php echo $d; ?>" <?php echo $f_duree===$d?'selected':''; ?>><?php echo $d; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <p class="adv-divider">Équipements</p>
      <div class="adv-checks">
        <?php
        $equip_map = [
            'wifi'             => ['fa-wifi','Wi-Fi'],
            'machine_a_laver'  => ['fa-shirt','Lave-linge'],
            'lave_vaisselle'   => ['fa-jug-detergent','Lave-vaisselle'],
            'micro_onde'       => ['fa-square','Micro-ondes'],
            'four'             => ['fa-fire','Four'],
            'frigo'            => ['fa-snowflake','Réfrigérateur'],
            'cuisine_equipee'  => ['fa-kitchen-set','Cuisine équipée'],
            'ustensiles'       => ['fa-utensils','Ustensiles'],
            'chauffage'        => ['fa-temperature-high','Chauffage'],
            'climatisation'    => ['fa-fan','Climatisation'],
            'balcon'           => ['fa-house-chimney-window','Balcon'],
            'terrasse'         => ['fa-umbrella-beach','Terrasse'],
            'jardin'           => ['fa-tree','Jardin'],
            'cave'             => ['fa-box-archive','Cave'],
            'ascenseur'        => ['fa-elevator','Ascenseur'],
            'parking'          => ['fa-square-parking','Parking'],
        ];
        foreach ($equip_map as $val => [$icon, $lbl]):
        ?>
        <label class="adv-check-item">
          <input type="checkbox" name="equipements[]" value="<?php echo $val; ?>" <?php echo in_array($val,$f_equipements,true)?'checked':''; ?>>
          <i class="fa-solid <?php echo $icon; ?>" style="color:#30bae6;font-size:12px;"></i>
          <?php echo $lbl; ?>
        </label>
        <?php endforeach; ?>
      </div>

      <p class="adv-divider">Règles &amp; Aides</p>
      <div class="adv-checks">
        <label class="adv-check-item"><input type="checkbox" name="apl" value="1" <?php echo $f_apl?'checked':''; ?>><i class="fa-solid fa-hand-holding-heart" style="color:#30bae6"></i> APL accepté</label>
        <label class="adv-check-item"><input type="checkbox" name="charges" value="1" <?php echo $f_charges?'checked':''; ?>><i class="fa-solid fa-receipt" style="color:#30bae6"></i> Charges incluses</label>
        <label class="adv-check-item"><input type="checkbox" name="animaux" value="1" <?php echo $f_animaux?'checked':''; ?>><i class="fa-solid fa-paw" style="color:#30bae6"></i> Animaux OK</label>
        <label class="adv-check-item"><input type="checkbox" name="fumeur" value="1" <?php echo $f_fumeur?'checked':''; ?>><i class="fa-solid fa-smoking" style="color:#30bae6"></i> Fumeur OK</label>
        <label class="adv-check-item"><input type="checkbox" name="pmr" value="1" <?php echo $f_pmr?'checked':''; ?>><i class="fa-solid fa-wheelchair" style="color:#30bae6"></i> Accessible PMR</label>
      </div>
    </div>

    <div class="filter-actions">
      <?php if ($filtresActifs): ?><a href="Annonces.php" class="btn-reset"><i class="fa-solid fa-rotate-left"></i> Réinitialiser</a><?php endif; ?>
      <button type="submit" class="btn-apply"><i class="fa-solid fa-magnifying-glass"></i> Rechercher</button>
    </div>
  </form>

  <!-- ══ Résultats ══ -->
  <div class="cards-grid">
    <?php if (empty($annonces)): ?>
    <div class="no-results">
      <i class="fa-solid fa-house-circle-xmark"></i>
      <h3 style="margin:0 0 8px;color:#334155;">Aucune annonce trouvée</h3>
      <p style="margin:0;font-size:13px;">Essayez d'élargir vos critères de recherche.</p>
    </div>
    <?php else: ?>
    <?php foreach ($annonces as $a):
      $img = (!empty($a['image_principale']) && file_exists(__DIR__.'/'.$a['image_principale']))
             ? htmlspecialchars($a['image_principale']) : 'img/studio2.jpg';
      $url = 'annonce.php?id=' . urlencode($a['id_annonce']);
      $isFav = in_array($a['id_annonce'], $mesFavoris, true);
    ?>
    <div class="annonce-card" onclick="location.href='<?php echo $url; ?>'">
      <div class="annonce-card-img">
        <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($a['ville']); ?>" loading="lazy">
        <span class="annonce-type-badge"><?php echo htmlspecialchars(ucfirst($a['type_immeuble'])); ?></span>
        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="annonce-card-actions" onclick="event.stopPropagation()">
          <button type="button" class="annonce-card-action-btn heart <?php echo $isFav?'is-fav':''; ?>"
                  onclick="toggleFavori('<?php echo htmlspecialchars($a['id_annonce']); ?>', this)"
                  title="<?php echo $isFav?'Retirer des favoris':'Ajouter aux favoris'; ?>">
            <i class="<?php echo $isFav?'fa-solid':'fa-regular'; ?> fa-heart"></i>
          </button>
          <?php if (!empty($a['utilisateur_id']) && $a['utilisateur_id'] !== $_SESSION['user_id']): ?>
          <a class="annonce-card-action-btn" href="profil.php?id=<?php echo urlencode($a['utilisateur_id']); ?>" title="Voir le profil">
            <i class="fa-solid fa-user"></i>
          </a>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
      <div class="annonce-card-body">
        <p class="annonce-card-prix"><?php echo number_format($a['prix'],0,',',' '); ?> € <span>/ mois</span></p>
        <p class="annonce-card-ville"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($a['ville']); ?> · <?php echo htmlspecialchars($a['code_postal']); ?></p>
        <p class="annonce-card-desc"><?php echo htmlspecialchars($a['description']); ?></p>
      </div>
      <div class="annonce-card-footer">
        <span><i class="fa-solid fa-vector-square"></i> <?php echo $a['superficie']; ?> m²</span>
        <span><i class="fa-solid fa-key"></i> <?php echo ucfirst($a['type_offre']); ?></span>
        <?php if (!empty($a['nb_pieces'])): ?><span><i class="fa-solid fa-door-open"></i> <?php echo (int)$a['nb_pieces']; ?> p.</span><?php endif; ?>
        <?php if (!empty($a['date_publication'])): ?><span style="margin-left:auto"><?php echo date('d/m/Y', strtotime($a['date_publication'])); ?></span><?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
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

<script>
async function toggleFavori(annonceId, btn) {
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
        icon.classList.remove('fa-regular'); icon.classList.add('fa-solid');
        btn.title = 'Retirer des favoris';
      } else {
        btn.classList.remove('is-fav');
        icon.classList.remove('fa-solid'); icon.classList.add('fa-regular');
        btn.title = 'Ajouter aux favoris';
      }
    } else if (d.error === 'non_connecte') {
      location.href = 'Authentification.html';
    }
  } catch(e) {}
}
</script>
</body>
</html>
