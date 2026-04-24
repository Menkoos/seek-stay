<?php
require 'config.php';
session_start();

$token   = trim($_GET['token'] ?? '');
$error   = '';
$success = '';
$valid   = false;
$uid     = null;

if (empty($token)) {
    header("Location: Authentification.html?tab=forgot&error=" . urlencode("Lien invalide."));
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id_utilisateur FROM utilisateur_ WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $row = $stmt->fetch();

    if ($row) {
        $valid = true;
        $uid   = $row['id_utilisateur'];
    } else {
        $error = "Ce lien est invalide ou a expiré. Faites une nouvelle demande.";
    }
} catch (PDOException $e) {
    $error = "Une erreur est survenue.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid) {
    $new_pass = $_POST['password']  ?? '';
    $confirm  = $_POST['confirm']   ?? '';

    if (strlen($new_pass) < 8) {
        $error = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif ($new_pass !== $confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        try {
            $pdo->prepare("UPDATE utilisateur_ SET mot_de_passe = ?, reset_token = NULL, reset_expires = NULL WHERE id_utilisateur = ?")
                ->execute([password_hash($new_pass, PASSWORD_DEFAULT), $uid]);
            $success = "Mot de passe réinitialisé avec succès. Vous pouvez vous connecter.";
            $valid   = false;
        } catch (PDOException $e) {
            $error = "Une erreur est survenue, veuillez réessayer.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Nouveau mot de passe — Seek &amp; Stay</title>
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
    }
    .auth-section {
      background: var(--bg); min-height: calc(100vh - 120px);
      display: flex; align-items: center; justify-content: center; padding: 48px 20px;
    }
    .auth-card {
      background: var(--white); border-radius: 14px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.07), 0 6px 24px rgba(0,0,0,0.09);
      padding: 44px 40px; width: 100%; max-width: 420px;
    }
    .auth-logo { text-align: center; margin-bottom: 28px; }
    .auth-logo-icon {
      width: 52px; height: 52px; background: var(--ss-primary); border-radius: 14px;
      display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;
    }
    .auth-logo-icon svg { width: 28px; height: 28px; fill: white; }
    .auth-logo h2 { font-size: 20px; font-weight: 700; color: var(--text); margin: 0; }
    .auth-logo p  { color: var(--text-muted); font-size: 13px; margin-top: 5px; }
    .auth-form-group { margin-bottom: 16px; }
    .auth-form-group label { display: block; font-size: 13px; font-weight: 500; color: var(--text); margin-bottom: 7px; }
    .auth-card input[type="password"] {
      width: 100%; padding: 10px 13px; border: 1px solid var(--border);
      border-radius: var(--radius); font-size: 14px; color: var(--text);
      font-family: inherit; transition: border-color 0.2s, box-shadow 0.2s; box-sizing: border-box;
    }
    .auth-card input:focus { outline: none; border-color: var(--ss-primary); box-shadow: 0 0 0 3px rgba(36,70,118,0.1); }
    .auth-btn {
      width: 100%; padding: 11px; background: var(--ss-primary); color: white;
      border: none; border-radius: var(--radius); font-size: 14px; font-weight: 600;
      cursor: pointer; margin-top: 6px; font-family: inherit; transition: background 0.2s;
    }
    .auth-btn:hover { background: var(--ss-primary-hover); }
    .auth-btn-secondary {
      display: inline-block; margin-top: 12px; text-align: center; width: 100%;
      padding: 10px; border: 1px solid var(--border); border-radius: var(--radius);
      font-size: 14px; color: var(--text-muted); text-decoration: none; font-family: inherit;
      transition: border-color 0.2s, color 0.2s;
    }
    .auth-btn-secondary:hover { border-color: var(--ss-primary); color: var(--ss-primary); }
    .auth-message {
      display: flex; align-items: flex-start; gap: 9px;
      padding: 11px 13px; border-radius: var(--radius);
      font-size: 13px; margin-bottom: 20px; line-height: 1.5;
    }
    .auth-message svg { flex-shrink: 0; margin-top: 1px; }
    .auth-message.error   { background: var(--error-bg);   color: var(--error-color);   border: 1px solid var(--error-border); }
    .auth-message.success { background: var(--success-bg); color: var(--success-color); border: 1px solid var(--success-border); }
    @media (max-width: 500px) { .auth-card { padding: 30px 20px; } }
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
        <li><a href="Authentification.html">Connexion</a></li>
      </ul>
    </div>
  </header>

  <section class="auth-section">
    <div class="auth-card">
      <div class="auth-logo">
        <div class="auth-logo-icon">
          <svg viewBox="0 0 24 24"><path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z"/></svg>
        </div>
        <h2>Nouveau mot de passe</h2>
        <p>Choisissez un mot de passe sécurisé</p>
      </div>

      <?php if ($error): ?>
      <div class="auth-message error">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <?php echo htmlspecialchars($error); ?>
      </div>
      <?php endif; ?>

      <?php if ($success): ?>
      <div class="auth-message success">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
        <?php echo htmlspecialchars($success); ?>
      </div>
      <a href="Authentification.html" class="auth-btn-secondary">Se connecter</a>
      <?php endif; ?>

      <?php if ($valid): ?>
      <form action="reset-password.php?token=<?php echo urlencode($token); ?>" method="POST" novalidate>
        <div class="auth-form-group">
          <label for="password">Nouveau mot de passe</label>
          <input type="password" id="password" name="password" placeholder="Min. 8 caractères" autocomplete="new-password" required>
        </div>
        <div class="auth-form-group">
          <label for="confirm">Confirmer le mot de passe</label>
          <input type="password" id="confirm" name="confirm" placeholder="••••••••" autocomplete="new-password" required>
        </div>
        <button type="submit" class="auth-btn">Réinitialiser le mot de passe</button>
      </form>
      <?php elseif (!$success): ?>
      <a href="Authentification.html?tab=forgot" class="auth-btn-secondary">Faire une nouvelle demande</a>
      <?php endif; ?>
    </div>
  </section>

  <footer>
    <div class="footer-top">
      <div class="footer-left"><img src="img/iconSite_WhiteText.png" class="logo-footer" alt="logo" /></div>
      <div class="footer-center">
        <p class="footer-text">Ce site a été conçu et développé par les élèves de l'ISEP du groupe G9A 2025/2026 — Tous droits réservés</p>
      </div>
    </div>
    <div class="footer-bottom">
      <a href="Mentionlegales.php">Mentions légales et CGU</a><p>-</p><a href="GestionCookies.html">Gestion des cookies</a>
    </div>
  </footer>
</body>
</html>
