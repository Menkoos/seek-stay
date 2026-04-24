<?php
require 'session.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: Authentification.html?error=" . urlencode("Connectez-vous pour accéder à votre compte."));
    exit;
}

$uid = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM utilisateur_ WHERE id_utilisateur = ?");
    $stmt->execute([$uid]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $user = [];
}

try {
    $nbAnnonces = $pdo->prepare("SELECT COUNT(*) FROM annonces WHERE utilisateur_id = ?");
    $nbAnnonces->execute([$uid]);
    $countAnnonces = (int)$nbAnnonces->fetchColumn();
} catch (PDOException $e) { $countAnnonces = 0; }

try {
    $nbCandidatures = $pdo->prepare("SELECT COUNT(*) FROM candidatures WHERE utilisateur_id = ?");
    $nbCandidatures->execute([$uid]);
    $countCandidatures = (int)$nbCandidatures->fetchColumn();
} catch (PDOException $e) { $countCandidatures = 0; }

try {
    $nbFavoris = $pdo->prepare("SELECT COUNT(*) FROM favoris WHERE utilisateur_id = ?");
    $nbFavoris->execute([$uid]);
    $countFavoris = (int)$nbFavoris->fetchColumn();
} catch (PDOException $e) { $countFavoris = 0; }

$tab     = $_GET['tab']     ?? 'profil';
$error   = isset($_GET['error'])   ? htmlspecialchars(urldecode($_GET['error']))   : '';
$success = isset($_GET['success']) ? htmlspecialchars(urldecode($_GET['success'])) : '';

$initiales = strtoupper(mb_substr($user['nom'] ?? 'U', 0, 1) . mb_substr($user['lastname'] ?? '', 0, 1));
$nomComplet = htmlspecialchars(($user['nom'] ?? '') . ' ' . ($user['lastname'] ?? ''));
$roleActuel = $user['role_type'] ?? 'loueur';
$roleLabel  = $roleActuel === 'proprietaire' ? 'Propriétaire' : 'Locataire';
$roleClass  = $roleActuel === 'proprietaire' ? 'badge-proprio' : 'badge-etudiant';
$dateInscription = $user['date_inscription'] ? date('d/m/Y', strtotime($user['date_inscription'])) : '—';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mon compte — Seek &amp; Stay</title>
  <link rel="icon" type="image/x-icon" href="img/flavicon.png" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
  <link rel="stylesheet" href="styles/styles.css" />
  <style>
    :root {
      --ss-primary: #244676; --ss-primary-hover: #1a3459; --ss-accent: #30bae6;
      --text: #1e293b; --text-muted: #64748b; --border: #e2e8f0;
      --bg: #f1f5f9; --white: #ffffff; --radius: 8px;
      --error-bg: #fef2f2; --error-color: #dc2626; --error-border: #fecaca;
      --success-bg: #f0fdf4; --success-color: #16a34a; --success-border: #bbf7d0;
      --danger: #dc2626; --danger-hover: #b91c1c; --danger-bg: #fff5f5;
    }

    .compte-page {
      background: var(--bg);
      min-height: calc(100vh - 120px);
      padding: 40px 20px;
    }

    .compte-inner {
      max-width: 760px;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      gap: 24px;
    }

    /* ── Carte profil header ── */
    .profil-header {
      background: var(--white);
      border-radius: 14px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.07), 0 4px 16px rgba(0,0,0,0.07);
      padding: 28px 32px;
      display: flex;
      align-items: center;
      gap: 24px;
    }
    .avatar-wrap { position: relative; flex-shrink: 0; }
    .avatar {
      width: 80px; height: 80px; border-radius: 50%;
      background: var(--ss-primary); color: #fff;
      display: flex; align-items: center; justify-content: center;
      font-size: 28px; font-weight: 700; letter-spacing: 1px;
      overflow: hidden;
    }
    .avatar img { width: 100%; height: 100%; object-fit: cover; }
    .profil-info { flex: 1; min-width: 0; }
    .profil-info h1 {
      font-size: 22px; font-weight: 700; color: var(--text);
      margin: 0 0 4px 0;
    }
    .profil-info .profil-email { font-size: 14px; color: var(--text-muted); margin-bottom: 10px; }
    .profil-meta { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }

    .badge {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 3px 10px; border-radius: 20px;
      font-size: 12px; font-weight: 600;
    }
    .badge-etudiant { background: #eff6ff; color: #1d4ed8; }
    .badge-proprio  { background: #fef3c7; color: #92400e; }
    .badge-date     { background: var(--bg); color: var(--text-muted); }

    .stats-row {
      display: flex; gap: 12px; margin-top: 16px; flex-wrap: wrap;
    }
    .stat-card {
      flex: 1; min-width: 100px; background: var(--bg);
      border-radius: 10px; padding: 12px 16px; text-align: center;
    }
    .stat-card strong { display: block; font-size: 22px; font-weight: 700; color: var(--ss-primary); }
    .stat-card span   { font-size: 12px; color: var(--text-muted); }

    /* ── Onglets ── */
    .compte-tabs {
      display: flex; background: var(--white);
      border-radius: 10px; padding: 6px; gap: 4px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }
    .compte-tab {
      flex: 1; padding: 9px 12px; text-align: center;
      background: none; border: none; border-radius: 7px;
      font-size: 13px; font-weight: 500; color: var(--text-muted);
      cursor: pointer; font-family: inherit; transition: all 0.18s;
      display: flex; align-items: center; justify-content: center; gap: 6px;
    }
    .compte-tab:hover:not(.active) { background: var(--bg); color: var(--text); }
    .compte-tab.active { background: var(--ss-primary); color: #fff; }
    .compte-tab svg { width: 15px; height: 15px; }

    /* ── Carte contenu ── */
    .compte-card {
      background: var(--white); border-radius: 14px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.07), 0 4px 16px rgba(0,0,0,0.07);
      padding: 32px;
    }
    .compte-card h2 {
      font-size: 17px; font-weight: 700; color: var(--text);
      margin: 0 0 6px 0;
    }
    .compte-card .section-desc {
      font-size: 13px; color: var(--text-muted); margin-bottom: 24px;
      padding-bottom: 20px; border-bottom: 1px solid var(--border);
    }
    .compte-panel { display: none; }
    .compte-panel.active { display: block; }

    /* ── Formulaires ── */
    .form-group { margin-bottom: 18px; }
    .form-group label {
      display: block; font-size: 13px; font-weight: 500;
      color: var(--text); margin-bottom: 7px;
    }
    .form-group .label-row {
      display: flex; justify-content: space-between; align-items: center;
      margin-bottom: 7px;
    }
    .form-optional { font-size: 12px; font-weight: 400; color: var(--text-muted); }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

    input[type="text"], input[type="email"], input[type="password"],
    input[type="tel"], select, textarea {
      width: 100%; padding: 10px 13px; border: 1px solid var(--border);
      border-radius: var(--radius); font-size: 14px; color: var(--text);
      background: var(--white); font-family: inherit;
      transition: border-color 0.2s, box-shadow 0.2s; box-sizing: border-box;
    }
    textarea { resize: vertical; min-height: 80px; }
    input::placeholder, textarea::placeholder { color: #b0bec5; }
    input:focus, select:focus, textarea:focus {
      outline: none; border-color: var(--ss-primary);
      box-shadow: 0 0 0 3px rgba(36,70,118,0.1);
    }
    input[readonly], input[disabled] {
      background: var(--bg); color: var(--text-muted); cursor: not-allowed;
    }

    .btn-primary {
      padding: 10px 24px; background: var(--ss-primary); color: white;
      border: none; border-radius: var(--radius); font-size: 14px; font-weight: 600;
      cursor: pointer; font-family: inherit; transition: background 0.2s;
    }
    .btn-primary:hover { background: var(--ss-primary-hover); }

    .btn-danger {
      padding: 10px 24px; background: var(--danger); color: white;
      border: none; border-radius: var(--radius); font-size: 14px; font-weight: 600;
      cursor: pointer; font-family: inherit; transition: background 0.2s;
    }
    .btn-danger:hover { background: var(--danger-hover); }

    /* ── Message alerte ── */
    .alert {
      display: flex; align-items: flex-start; gap: 9px;
      padding: 11px 13px; border-radius: var(--radius);
      font-size: 13px; margin-bottom: 20px; line-height: 1.5;
    }
    .alert svg { flex-shrink: 0; margin-top: 1px; }
    .alert.error   { background: var(--error-bg);   color: var(--error-color);   border: 1px solid var(--error-border); }
    .alert.success { background: var(--success-bg); color: var(--success-color); border: 1px solid var(--success-border); }

    /* ── Photo profil ── */
    .photo-section {
      display: flex; align-items: center; gap: 20px; margin-bottom: 24px;
      padding-bottom: 24px; border-bottom: 1px solid var(--border);
    }
    .photo-preview {
      width: 72px; height: 72px; border-radius: 50%;
      background: var(--ss-primary); color: #fff;
      display: flex; align-items: center; justify-content: center;
      font-size: 24px; font-weight: 700; overflow: hidden; flex-shrink: 0;
    }
    .photo-preview img { width: 100%; height: 100%; object-fit: cover; }
    .photo-actions { flex: 1; }
    .photo-actions p { font-size: 13px; color: var(--text-muted); margin-bottom: 8px; }
    .photo-label {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 7px 14px; background: var(--bg); border: 1px solid var(--border);
      border-radius: var(--radius); font-size: 13px; font-weight: 500;
      color: var(--text); cursor: pointer; transition: border-color 0.2s;
    }
    .photo-label:hover { border-color: var(--ss-primary); color: var(--ss-primary); }
    .photo-label svg { width: 15px; height: 15px; }
    #photo-input { display: none; }
    #photo-name { font-size: 12px; color: var(--text-muted); margin-top: 5px; }

    /* ── Danger zone ── */
    .danger-zone {
      border: 1px solid #fecaca; border-radius: 10px;
      padding: 20px 24px; background: var(--danger-bg);
    }
    .danger-zone h3 {
      font-size: 15px; font-weight: 700; color: var(--danger);
      margin: 0 0 8px 0;
    }
    .danger-zone p { font-size: 13px; color: #7f1d1d; margin-bottom: 16px; line-height: 1.55; }
    .danger-confirm { display: none; margin-top: 16px; }
    .danger-confirm.open { display: block; }

    /* ── Section séparateur ── */
    .section-sep {
      border: none; border-top: 1px solid var(--border); margin: 28px 0;
    }

    @media (max-width: 600px) {
      .profil-header { flex-direction: column; text-align: center; }
      .profil-meta   { justify-content: center; }
      .stats-row     { justify-content: center; }
      .form-row      { grid-template-columns: 1fr; }
      .compte-card   { padding: 20px; }
      .compte-tab    { font-size: 12px; }
    }
  </style>
</head>
<body>

<header>
  <div class="flex">
    <a href="Accueil.php" class="header-logo">
      <img src="img/iconSite.png" class="header-logo" alt="logo Seek &amp; Stay" />
    </a>
    <ul class="header-menu">
      <li><a href="Accueil.php">Accueil</a></li>
      <li><a href="Annonces.html">Annonces</a></li>
      <li><a href="Favoris.php">Favoris</a></li>
      <?php if (!isset($_SESSION['user_id']) || ($_SESSION['role_type'] ?? '') === 'proprietaire'): ?>
      <li><a href="Publier.php">Publier</a></li>
      <?php endif; ?>
      <li><a href="Contact.html">Contact</a></li>
      <li><a href="messagerie.php">Messages <span id="nav-unread" style="display:none;background:#ef4444;color:#fff;border-radius:10px;font-size:11px;font-weight:700;padding:1px 6px;margin-left:2px;vertical-align:middle;">0</span></a></li>
      <li><a href="mon-compte.php" class="active" style="font-weight:600;">Mon compte</a></li>
      <li><a href="FAQ.html">FAQ</a></li>
      <li><a href="logout.php">Déconnexion</a></li>
    </ul>
  </div>
</header>

<div class="compte-page">
  <div class="compte-inner">

    <!-- ── Header profil ── -->
    <div class="profil-header">
      <div class="avatar-wrap">
        <div class="avatar">
          <?php if (!empty($user['photo_profil']) && file_exists(__DIR__ . '/' . $user['photo_profil'])): ?>
            <img src="<?php echo htmlspecialchars($user['photo_profil']); ?>?v=<?php echo time(); ?>" alt="Photo de profil" />
          <?php else: ?>
            <?php echo $initiales; ?>
          <?php endif; ?>
        </div>
      </div>
      <div class="profil-info">
        <h1><?php echo $nomComplet; ?></h1>
        <div class="profil-email"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
        <div class="profil-meta">
          <span class="badge <?php echo $roleClass; ?>"><?php echo $roleLabel; ?></span>
          <span class="badge badge-date">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            Membre depuis le <?php echo $dateInscription; ?>
          </span>
        </div>
        <div class="stats-row">
          <div class="stat-card"><strong><?php echo $countAnnonces; ?></strong><span>Annonce<?php echo $countAnnonces > 1 ? 's' : ''; ?></span></div>
          <div class="stat-card"><strong><?php echo $countCandidatures; ?></strong><span>Candidature<?php echo $countCandidatures > 1 ? 's' : ''; ?></span></div>
          <div class="stat-card"><strong><?php echo $countFavoris; ?></strong><span>Favori<?php echo $countFavoris > 1 ? 's' : ''; ?></span></div>
        </div>
      </div>
    </div>

    <!-- ── Onglets ── -->
    <div class="compte-tabs">
      <button class="compte-tab <?php echo $tab === 'profil'   ? 'active' : ''; ?>" onclick="switchTab('profil')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Mon profil
      </button>
      <button class="compte-tab <?php echo $tab === 'securite' ? 'active' : ''; ?>" onclick="switchTab('securite')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Sécurité
      </button>
      <button class="compte-tab <?php echo $tab === 'danger'   ? 'active' : ''; ?>" onclick="switchTab('danger')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
        Supprimer le compte
      </button>
    </div>

    <!-- ── Contenu ── -->
    <div class="compte-card">

      <!-- ── Message global ── -->
      <?php if ($error): ?>
      <div class="alert error">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <?php echo $error; ?>
      </div>
      <?php endif; ?>
      <?php if ($success): ?>
      <div class="alert success">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
        <?php echo $success; ?>
      </div>
      <?php endif; ?>

      <!-- ── Onglet Profil ── -->
      <div class="compte-panel <?php echo $tab === 'profil' ? 'active' : ''; ?>" id="panel-profil">
        <h2>Informations personnelles</h2>
        <p class="section-desc">Modifiez vos informations de profil visibles sur la plateforme.</p>

        <form action="update-profile.php" method="POST" enctype="multipart/form-data" novalidate>

          <!-- Photo de profil -->
          <div class="photo-section">
            <div class="photo-preview" id="photo-preview-small">
              <?php if (!empty($user['photo_profil']) && file_exists(__DIR__ . '/' . $user['photo_profil'])): ?>
                <img id="photo-img-preview" src="<?php echo htmlspecialchars($user['photo_profil']); ?>?v=<?php echo time(); ?>" alt="Photo de profil" />
              <?php else: ?>
                <span id="photo-initials-preview"><?php echo $initiales; ?></span>
              <?php endif; ?>
            </div>
            <div class="photo-actions">
              <p>JPG, PNG, GIF ou WEBP — 2 Mo maximum</p>
              <label class="photo-label" for="photo-input">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Choisir une photo
              </label>
              <input type="file" id="photo-input" name="photo" accept="image/*" onchange="previewPhoto(this)">
              <div id="photo-name"></div>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="nom">Nom</label>
              <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($user['nom'] ?? ''); ?>" placeholder="Dupont" required>
            </div>
            <div class="form-group">
              <label for="lastname">Prénom</label>
              <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($user['lastname'] ?? ''); ?>" placeholder="Jean" required>
            </div>
          </div>

          <div class="form-group">
            <div class="label-row">
              <label for="email-display">Adresse email</label>
            </div>
            <input type="email" id="email-display" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" readonly title="L'email ne peut pas être modifié ici.">
          </div>

          <div class="form-group">
            <div class="label-row">
              <label for="telephone">Téléphone</label>
              <span class="form-optional">Facultatif</span>
            </div>
            <input type="tel" id="telephone" name="telephone" value="<?php echo htmlspecialchars($user['telephone'] ?? ''); ?>" placeholder="06 00 00 00 00">
          </div>

          <div class="form-group">
            <div class="label-row">
              <label for="bio">Bio</label>
              <span class="form-optional">Facultatif</span>
            </div>
            <textarea id="bio" name="bio" placeholder="Quelques mots sur vous..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
          </div>

          <div class="form-group">
            <label for="role_type">Je suis</label>
            <select id="role_type" name="role_type">
              <option value="loueur"       <?php echo $roleActuel === 'loueur'       ? 'selected' : ''; ?>>Locataire — je cherche un logement</option>
              <option value="proprietaire" <?php echo $roleActuel === 'proprietaire' ? 'selected' : ''; ?>>Propriétaire — je propose un logement</option>
            </select>
            <p style="font-size:12px;color:var(--text-muted);margin-top:6px;">
              <i class="fa-solid fa-circle-info"></i>
              Seuls les propriétaires peuvent publier des annonces.
            </p>
          </div>

          <button type="submit" class="btn-primary">Enregistrer les modifications</button>
        </form>
      </div>

      <!-- ── Onglet Sécurité ── -->
      <div class="compte-panel <?php echo $tab === 'securite' ? 'active' : ''; ?>" id="panel-securite">
        <h2>Mot de passe</h2>
        <p class="section-desc">Choisissez un mot de passe fort d'au moins 8 caractères.</p>

        <form action="change-password.php" method="POST" novalidate>
          <div class="form-group">
            <label for="current_password">Mot de passe actuel</label>
            <input type="password" id="current_password" name="current_password" placeholder="••••••••" autocomplete="current-password" required>
          </div>
          <hr class="section-sep">
          <div class="form-group">
            <label for="new_password">Nouveau mot de passe</label>
            <input type="password" id="new_password" name="new_password" placeholder="Min. 8 caractères" autocomplete="new-password" required oninput="checkPasswordStrength(this.value)">
            <div id="strength-bar" style="margin-top:8px;display:none;">
              <div style="height:4px;border-radius:4px;background:var(--border);overflow:hidden;">
                <div id="strength-fill" style="height:100%;width:0%;transition:width 0.3s,background 0.3s;border-radius:4px;"></div>
              </div>
              <span id="strength-label" style="font-size:11px;color:var(--text-muted);margin-top:4px;display:block;"></span>
            </div>
          </div>
          <div class="form-group">
            <label for="confirm_password">Confirmer le nouveau mot de passe</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" autocomplete="new-password" required>
          </div>
          <button type="submit" class="btn-primary">Changer le mot de passe</button>
        </form>
      </div>

      <!-- ── Onglet Danger ── -->
      <div class="compte-panel <?php echo $tab === 'danger' ? 'active' : ''; ?>" id="panel-danger">
        <h2>Supprimer mon compte</h2>
        <p class="section-desc">Cette action est irréversible. Toutes vos données seront supprimées définitivement.</p>

        <div class="danger-zone">
          <h3>Zone de danger</h3>
          <p>
            La suppression de votre compte entraîne la perte permanente de :
            vos annonces publiées, vos candidatures, vos favoris et toutes vos données personnelles.
            <strong>Cette action ne peut pas être annulée.</strong>
          </p>
          <button type="button" class="btn-danger" onclick="toggleDangerConfirm()">
            Supprimer définitivement mon compte
          </button>

          <div class="danger-confirm" id="danger-confirm">
            <hr class="section-sep" style="margin:16px 0;">
            <p style="font-size:13px;color:#7f1d1d;margin-bottom:12px;">
              Pour confirmer, entrez votre mot de passe actuel :
            </p>
            <form action="delete-account.php" method="POST" onsubmit="return confirmDelete()">
              <div class="form-group" style="margin-bottom:12px;">
                <input type="password" name="confirm_password" id="delete-password"
                       placeholder="Votre mot de passe" autocomplete="current-password" required
                       style="border-color:#fecaca;">
              </div>
              <button type="submit" class="btn-danger">
                Oui, supprimer mon compte
              </button>
              <button type="button" onclick="toggleDangerConfirm()" style="margin-left:10px;background:none;border:none;font-size:13px;color:var(--text-muted);cursor:pointer;font-family:inherit;">
                Annuler
              </button>
            </form>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<footer>
  <div class="footer-top">
    <div class="footer-left">
      <img src="img/iconSite_WhiteText.png" class="logo-footer" alt="Icône du site" />
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

<script>
  function switchTab(name) {
    document.querySelectorAll('.compte-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.compte-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('panel-' + name).classList.add('active');
    document.querySelectorAll('.compte-tab')[['profil','securite','danger'].indexOf(name)].classList.add('active');
    const url = new URL(window.location);
    url.searchParams.set('tab', name);
    url.searchParams.delete('error');
    url.searchParams.delete('success');
    window.history.replaceState({}, '', url);
  }

  function previewPhoto(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    document.getElementById('photo-name').textContent = file.name;
    const reader = new FileReader();
    reader.onload = e => {
      const big   = document.querySelector('.avatar');
      const small = document.getElementById('photo-preview-small');
      [big, small].forEach(el => {
        el.innerHTML = '<img src="' + e.target.result + '" alt="Aperçu" style="width:100%;height:100%;object-fit:cover;">';
      });
    };
    reader.readAsDataURL(file);
  }

  function checkPasswordStrength(val) {
    const bar = document.getElementById('strength-bar');
    const fill = document.getElementById('strength-fill');
    const label = document.getElementById('strength-label');
    bar.style.display = val.length > 0 ? 'block' : 'none';
    let score = 0;
    if (val.length >= 8)  score++;
    if (val.length >= 12) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const levels = [
      { color: '#ef4444', label: 'Très faible', width: '20%' },
      { color: '#f97316', label: 'Faible',      width: '40%' },
      { color: '#eab308', label: 'Moyen',        width: '60%' },
      { color: '#22c55e', label: 'Fort',         width: '80%' },
      { color: '#16a34a', label: 'Très fort',    width: '100%' },
    ];
    const lv = levels[Math.max(0, score - 1)] || levels[0];
    fill.style.width = lv.width;
    fill.style.background = lv.color;
    label.textContent = lv.label;
    label.style.color = lv.color;
  }

  function toggleDangerConfirm() {
    const el = document.getElementById('danger-confirm');
    el.classList.toggle('open');
    if (el.classList.contains('open')) {
      document.getElementById('delete-password').focus();
    }
  }

  function confirmDelete() {
    return confirm("Êtes-vous absolument certain(e) de vouloir supprimer votre compte ? Cette action est irréversible.");
  }

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
</script>
</body>
</html>
