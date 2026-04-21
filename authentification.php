<?php
session_start();
require 'config.php';

// Rediriger si déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: Accueil.php');
    exit;
}

$tab     = $_GET['tab'] ?? 'login';
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── CONNEXION ──────────────────────────────────────────────────────────
    if ($action === 'login') {
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {
            $error = 'Veuillez remplir tous les champs.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Adresse email invalide.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM utilisateur_ WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['mot_de_passe'])) {
                $_SESSION['user_id']  = $user['id_utilisateur'];
                $_SESSION['email']    = $user['email'];
                $_SESSION['nom']      = $user['nom'];
                $_SESSION['lastname'] = $user['lastname'];

                if (!empty($_POST['remember'])) {
                    $token   = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                    $pdo->prepare("UPDATE utilisateur_ SET remember_token=?, remember_expires=? WHERE id_utilisateur=?")
                        ->execute([hash('sha256', $token), $expires, $user['id_utilisateur']]);
                    setcookie('ss_remember', $user['id_utilisateur'] . ':' . $token, time() + 86400 * 30, '/', '', false, true);
                }

                header('Location: Accueil.php');
                exit;
            } else {
                $error = 'Email ou mot de passe incorrect.';
            }
        }
        $tab = 'login';

    // ── INSCRIPTION ────────────────────────────────────────────────────────
    } elseif ($action === 'register') {
        $nom       = trim($_POST['nom']       ?? '');
        $lastname  = trim($_POST['lastname']  ?? '');
        $email     = trim($_POST['email']     ?? '');
        $password  = $_POST['password']  ?? '';
        $confirm   = $_POST['confirm']   ?? '';
        $telephone = trim($_POST['telephone'] ?? '');

        if (empty($nom) || empty($lastname) || empty($email) || empty($password) || empty($confirm)) {
            $error = 'Veuillez remplir tous les champs obligatoires.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Adresse email invalide.';
        } elseif (strlen($password) < 8) {
            $error = 'Le mot de passe doit contenir au moins 8 caractères.';
        } elseif ($password !== $confirm) {
            $error = 'Les mots de passe ne correspondent pas.';
        } else {
            try {
                $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare("INSERT INTO utilisateur_ (id_utilisateur, nom, lastname, email, mot_de_passe, telephone, date_inscription) VALUES (?,?,?,?,?,?,NOW())")
                    ->execute([$uuid, $nom, $lastname, $email, $hashed, $telephone]);

                $_SESSION['user_id']  = $uuid;
                $_SESSION['email']    = $email;
                $_SESSION['nom']      = $nom;
                $_SESSION['lastname'] = $lastname;

                header('Location: Accueil.php');
                exit;
            } catch (PDOException $e) {
                $error = 'Cette adresse email est déjà utilisée.';
            }
        }
        $tab = 'register';

    // ── MOT DE PASSE OUBLIÉ ────────────────────────────────────────────────
    } elseif ($action === 'forgot') {
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Adresse email invalide.';
        } else {
            $stmt = $pdo->prepare("SELECT id_utilisateur FROM utilisateur_ WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $token   = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                $pdo->prepare("UPDATE utilisateur_ SET reset_token=?, reset_expires=? WHERE email=?")
                    ->execute([$token, $expires, $email]);
            }
            $success = 'Si cette adresse est connue, un lien vous a été envoyé.';
        }
        $tab = 'forgot';
    }
}

$icon_check = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
$icon_alert = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $tab === 'register' ? 'Inscription' : ($tab === 'forgot' ? 'Mot de passe oublié' : 'Connexion') ?> — Seek &amp; Stay</title>
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
    .auth-section { background: var(--bg); min-height: calc(100vh - 120px); display: flex; align-items: center; justify-content: center; padding: 48px 20px; }
    .auth-card { background: var(--white); border-radius: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.07), 0 6px 24px rgba(0,0,0,0.09); padding: 44px 40px; width: 100%; max-width: 440px; }
    .auth-logo { text-align: center; margin-bottom: 32px; }
    .auth-logo-icon { width: 52px; height: 52px; background: var(--ss-primary); border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 14px; }
    .auth-logo-icon svg { width: 28px; height: 28px; fill: white; }
    .auth-logo h2 { font-size: 22px; font-weight: 700; color: var(--text); margin: 0; }
    .auth-logo p { color: var(--text-muted); font-size: 14px; margin-top: 5px; }
    .auth-tabs { display: flex; border-bottom: 1px solid var(--border); margin-bottom: 28px; }
    .auth-tab { flex: 1; padding: 10px 4px; text-align: center; font-size: 14px; font-weight: 500; color: var(--text-muted); text-decoration: none; border-bottom: 2px solid transparent; margin-bottom: -1px; transition: color 0.2s, border-color 0.2s; }
    .auth-tab.active { color: var(--ss-primary); border-bottom-color: var(--ss-primary); }
    .auth-tab:hover:not(.active) { color: var(--text); text-decoration: none; }
    .auth-form-group { margin-bottom: 16px; }
    .auth-form-group label { display: flex; justify-content: space-between; align-items: center; font-size: 13px; font-weight: 500; color: var(--text); margin-bottom: 7px; }
    .auth-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .auth-forgot-link { font-size: 12px; font-weight: 400; color: var(--ss-accent); text-decoration: none; }
    .auth-forgot-link:hover { text-decoration: underline; }
    .auth-card input[type="text"],
    .auth-card input[type="email"],
    .auth-card input[type="password"],
    .auth-card input[type="tel"] { width: 100%; padding: 10px 13px; border: 1px solid var(--border); border-radius: var(--radius); font-size: 14px; color: var(--text); background: var(--white); font-family: inherit; transition: border-color 0.2s, box-shadow 0.2s; box-sizing: border-box; }
    .auth-card input::placeholder { color: #b0bec5; }
    .auth-card input:focus { outline: none; border-color: var(--ss-primary); box-shadow: 0 0 0 3px rgba(36,70,118,0.1); }
    .auth-btn { width: 100%; padding: 11px; background: var(--ss-primary); color: white; border: none; border-radius: var(--radius); font-size: 14px; font-weight: 600; cursor: pointer; margin-top: 6px; font-family: inherit; transition: background 0.2s; }
    .auth-btn:hover { background: var(--ss-primary-hover); }
    .auth-message { display: flex; align-items: flex-start; gap: 9px; padding: 11px 13px; border-radius: var(--radius); font-size: 13px; margin-bottom: 20px; line-height: 1.5; }
    .auth-message svg { flex-shrink: 0; margin-top: 1px; }
    .auth-message.error { background: var(--error-bg); color: var(--error-color); border: 1px solid var(--error-border); }
    .auth-message.success { background: var(--success-bg); color: var(--success-color); border: 1px solid var(--success-border); }
    .auth-forgot-header { margin-bottom: 22px; }
    .auth-back-link { display: inline-flex; align-items: center; gap: 5px; font-size: 13px; color: var(--text-muted); text-decoration: none; margin-bottom: 16px; transition: color 0.2s; }
    .auth-back-link:hover { color: var(--text); text-decoration: none; }
    .auth-forgot-header h3 { font-size: 18px; font-weight: 700; color: var(--text); margin: 0 0 6px 0; }
    .auth-forgot-header p { font-size: 13px; color: var(--text-muted); line-height: 1.55; margin: 0; }
    .auth-optional { font-size: 12px; color: var(--text-muted); font-weight: 400; }
    .auth-remember { margin-bottom: 14px; }
    .auth-remember label { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-muted); cursor: pointer; font-weight: 400; }
    .auth-remember input[type="checkbox"] { width: 15px; height: 15px; accent-color: var(--ss-primary); cursor: pointer; }
    @media (max-width: 500px) { .auth-card { padding: 30px 20px; } .auth-form-row { grid-template-columns: 1fr; } }
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
        <li><a href="Offres.html">Offres</a></li>
        <li><a href="Publier.html">Publier</a></li>
        <li><a href="FAQ.html">FAQ</a></li>
        <li><a href="Favoris.html">Favoris</a></li>
        <li><a href="Contact.html">Contact</a></li>
        <li><a href="Authentification.php">Inscription / Connexion</a></li>
      </ul>
    </div>
  </header>

  <section class="auth-section">
    <div class="auth-card">

      <div class="auth-logo">
        <div class="auth-logo-icon">
          <svg viewBox="0 0 24 24"><path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z"/><path d="M9 21V12h6v9" fill="none" stroke="rgba(255,255,255,0.55)" stroke-width="1.5"/></svg>
        </div>
        <h2>Seek &amp; Stay</h2>
        <p><?= $tab === 'forgot' ? 'Réinitialisez votre mot de passe' : ($tab === 'register' ? 'Créez votre compte étudiant' : 'Accédez à votre espace personnel') ?></p>
      </div>

      <?php if ($tab !== 'forgot'): ?>
      <div class="auth-tabs">
        <a href="?tab=login"    class="auth-tab <?= $tab === 'login'    ? 'active' : '' ?>">Connexion</a>
        <a href="?tab=register" class="auth-tab <?= $tab === 'register' ? 'active' : '' ?>">Inscription</a>
      </div>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
        <div class="auth-message error"><?= $icon_alert ?> <span><?= htmlspecialchars($error) ?></span></div>
      <?php endif; ?>
      <?php if (!empty($success)): ?>
        <div class="auth-message success"><?= $icon_check ?> <span><?= htmlspecialchars($success) ?></span></div>
      <?php endif; ?>

      <?php if ($tab === 'login'): ?>
      <form method="POST" action="Authentification.php?tab=login" novalidate>
        <input type="hidden" name="action" value="login">
        <div class="auth-form-group">
          <label for="email">Adresse email</label>
          <input type="email" id="email" name="email" placeholder="vous@exemple.com"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autocomplete="email" required>
        </div>
        <div class="auth-form-group">
          <label for="password">
            Mot de passe
            <a href="?tab=forgot" class="auth-forgot-link">Oublié ?</a>
          </label>
          <input type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
        </div>
        <div class="auth-remember">
          <label>
            <input type="checkbox" name="remember" value="1">
            Se souvenir de moi (30 jours)
          </label>
        </div>
        <button type="submit" class="auth-btn">Se connecter</button>
      </form>

      <?php elseif ($tab === 'register'): ?>
      <form method="POST" action="Authentification.php?tab=register" novalidate>
        <input type="hidden" name="action" value="register">
        <div class="auth-form-row">
          <div class="auth-form-group">
            <label for="nom">Nom</label>
            <input type="text" id="nom" name="nom" placeholder="Dupont"
                   value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" autocomplete="family-name" required>
          </div>
          <div class="auth-form-group">
            <label for="lastname">Prénom</label>
            <input type="text" id="lastname" name="lastname" placeholder="Jean"
                   value="<?= htmlspecialchars($_POST['lastname'] ?? '') ?>" autocomplete="given-name" required>
          </div>
        </div>
        <div class="auth-form-group">
          <label for="reg-email">Adresse email</label>
          <input type="email" id="reg-email" name="email" placeholder="vous@exemple.com"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autocomplete="email" required>
        </div>
        <div class="auth-form-group">
          <label for="reg-password">Mot de passe</label>
          <input type="password" id="reg-password" name="password" placeholder="Min. 8 caractères" autocomplete="new-password" required>
        </div>
        <div class="auth-form-group">
          <label for="confirm">Confirmer le mot de passe</label>
          <input type="password" id="confirm" name="confirm" placeholder="••••••••" autocomplete="new-password" required>
        </div>
        <div class="auth-form-group">
          <label for="telephone">Téléphone <span class="auth-optional">(facultatif)</span></label>
          <input type="tel" id="telephone" name="telephone" placeholder="06 00 00 00 00"
                 value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>" autocomplete="tel">
        </div>
        <button type="submit" class="auth-btn">Créer mon compte</button>
      </form>

      <?php elseif ($tab === 'forgot'): ?>
      <div class="auth-forgot-header">
        <a href="?tab=login" class="auth-back-link">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
          Retour à la connexion
        </a>
        <h3>Mot de passe oublié ?</h3>
        <p>Indiquez votre email, nous vous enverrons un lien de réinitialisation.</p>
      </div>
      <?php if (empty($success)): ?>
      <form method="POST" action="Authentification.php?tab=forgot" novalidate>
        <input type="hidden" name="action" value="forgot">
        <div class="auth-form-group">
          <label for="forgot-email">Adresse email</label>
          <input type="email" id="forgot-email" name="email" placeholder="vous@exemple.com"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autocomplete="email" required>
        </div>
        <button type="submit" class="auth-btn">Envoyer le lien</button>
      </form>
      <?php endif; ?>
      <?php endif; ?>

    </div>
  </section>

  <footer>
    <div class="footer-top">
      <div class="footer-left">
        <img src="img/iconSite_WhiteText.png" class="logo-footer" alt="Icône du site internet" />
      </div>
      <div class="footer-center">
        <p class="footer-text">Ce site a été conçu et développé par les élèves de l'ISEP du groupe G9A 2025/2026 en utilisant HTML, CSS, JavaScript et PHP - Tous droits réservés</p>
      </div>
    </div>
    <div class="footer-bottom">
      <a href="Mentionlegales.php">Mentions légales et CGU</a>
      <p>-</p>
      <a href="GestionCookies.html">Gestion des cookies</a>
    </div>
  </footer>
</body>
</html>
