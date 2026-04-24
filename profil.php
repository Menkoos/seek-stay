<?php
require 'session.php';

$targetId = trim($_GET['id'] ?? '');
if (empty($targetId)) {
    header('Location: Accueil.php');
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id_utilisateur, nom, lastname, email, telephone, photo_profil, bio,
               role_type, date_inscription
        FROM utilisateur_
        WHERE id_utilisateur = ?
    ");
    $stmt->execute([$targetId]);
    $u = $stmt->fetch();
} catch (PDOException $e) { $u = null; }

if (!$u) {
    header('Location: Accueil.php');
    exit;
}

$moi   = $_SESSION['user_id'] ?? null;
$isMe  = $moi === $u['id_utilisateur'];
$role  = $u['role_type'] ?? 'loueur';

$initiales = strtoupper(mb_substr($u['nom'], 0, 1) . mb_substr($u['lastname'], 0, 1));
$nomComplet = htmlspecialchars($u['nom'] . ' ' . $u['lastname']);

// Annonces publiées (si proprio)
$annonces = [];
if ($role === 'proprietaire') {
    try {
        $s = $pdo->prepare("
            SELECT * FROM annonces
            WHERE utilisateur_id = ? AND statut = 'actif'
            ORDER BY date_publication DESC
        ");
        $s->execute([$u['id_utilisateur']]);
        $annonces = $s->fetchAll();
    } catch (PDOException $e) {}
}
$nbAnnonces = count($annonces);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo $nomComplet; ?> — Profil — Seek &amp; Stay</title>
  <link rel="icon" type="image/x-icon" href="img/flavicon.png" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
  <link rel="stylesheet" href="styles/styles.css" />
  <style>
    :root {
      --primary: #244676; --primary-h: #1a3459; --accent: #30bae6;
      --text: #1e293b; --muted: #64748b; --border: #e2e8f0;
      --bg: #f1f5f9; --white: #fff; --radius: 12px;
      --danger: #dc2626; --danger-bg: #fef2f2;
    }
    body { background: var(--bg); font-family: 'Inter', sans-serif; }

    .profil-page {
      max-width: 960px; margin: 32px auto; padding: 0 24px 60px;
      display: flex; flex-direction: column; gap: 20px;
    }

    /* ── Header carte ── */
    .profil-header {
      background: var(--white); border-radius: var(--radius);
      padding: 32px; display: flex; align-items: center; gap: 28px;
      box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 4px 14px rgba(0,0,0,.06);
    }
    .profil-avatar {
      width: 110px; height: 110px; border-radius: 50%;
      background: var(--primary); color: #fff;
      display: flex; align-items: center; justify-content: center;
      font-size: 38px; font-weight: 700; overflow: hidden; flex-shrink: 0;
    }
    .profil-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .profil-info { flex: 1; min-width: 0; }
    .profil-info h1 {
      font-size: 1.7rem; font-weight: 800; color: var(--text); margin: 0 0 6px;
    }
    .profil-role-row { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-bottom: 8px; }
    .badge-role {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 3px 10px; font-size: 12px; font-weight: 700;
      border-radius: 20px;
    }
    .badge-proprio  { background: #dbeafe; color: #1e40af; }
    .badge-loueur   { background: #f1f5f9; color: #475569; }
    .profil-date {
      font-size: 12px; color: var(--muted);
    }
    .profil-bio {
      margin: 14px 0 0; font-size: 14px; line-height: 1.6; color: #475569;
      white-space: pre-line;
    }

    .profil-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 16px; }
    .btn {
      padding: 10px 18px; border-radius: 10px; font-size: 13px; font-weight: 600;
      font-family: inherit; cursor: pointer; border: none;
      display: inline-flex; align-items: center; gap: 7px;
      text-decoration: none; transition: background .15s, border-color .15s;
    }
    .btn-primary { background: var(--primary); color: #fff; }
    .btn-primary:hover { background: var(--primary-h); }
    .btn-outline {
      background: transparent; border: 1px solid var(--border); color: var(--text);
    }
    .btn-outline:hover { border-color: var(--primary); color: var(--primary); }
    .btn-danger {
      background: transparent; border: 1px solid #fecaca; color: var(--danger);
    }
    .btn-danger:hover { background: var(--danger-bg); }

    /* ── Stats ── */
    .profil-stats { display: flex; gap: 12px; flex-wrap: wrap; }
    .stat-card {
      flex: 1; min-width: 160px; background: var(--white);
      border-radius: var(--radius); padding: 18px 22px;
      box-shadow: 0 1px 3px rgba(0,0,0,.05);
    }
    .stat-num { font-size: 1.7rem; font-weight: 800; color: var(--primary); display: block; line-height: 1; }
    .stat-label { font-size: 12px; color: var(--muted); margin-top: 6px; display: block; }

    /* ── Annonces grid ── */
    .profil-annonces {
      background: var(--white); border-radius: var(--radius);
      padding: 24px 28px;
      box-shadow: 0 1px 3px rgba(0,0,0,.05);
    }
    .profil-annonces h2 {
      font-size: 1.1rem; font-weight: 800; color: var(--text); margin: 0 0 18px;
    }
    .cards-grid {
      display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px;
    }
    .mini-card {
      background: var(--bg); border-radius: 12px; overflow: hidden;
      cursor: pointer; transition: transform .15s, box-shadow .15s;
      text-decoration: none; color: inherit;
    }
    .mini-card:hover { transform: translateY(-3px); box-shadow: 0 6px 16px rgba(0,0,0,.1); }
    .mini-card-img { width: 100%; height: 140px; background: #cbd5e1; overflow: hidden; }
    .mini-card-img img { width: 100%; height: 100%; object-fit: cover; }
    .mini-card-body { padding: 10px 14px 14px; }
    .mini-card-price { font-weight: 800; color: var(--primary); font-size: 14px; }
    .mini-card-ville { font-size: 12px; color: var(--muted); margin-top: 3px; }

    .empty-state {
      padding: 40px 20px; text-align: center; color: var(--muted);
      font-size: 13px;
    }

    /* ── Modal signaler ── */
    .modal-overlay {
      display: none; position: fixed; inset: 0; z-index: 9999;
      background: rgba(15, 23, 42, .6);
      align-items: center; justify-content: center;
      padding: 20px;
    }
    .modal-overlay.open { display: flex; }
    .modal {
      background: var(--white); border-radius: 14px;
      width: 100%; max-width: 460px; overflow: hidden;
      box-shadow: 0 20px 60px rgba(0,0,0,.3);
    }
    .modal-head {
      padding: 18px 22px 14px; border-bottom: 1px solid var(--border);
      display: flex; align-items: center; gap: 10px;
    }
    .modal-head h3 { font-size: 16px; margin: 0; color: var(--text); font-weight: 700; }
    .modal-body { padding: 18px 22px; }
    .modal-body label { display: block; font-size: 12px; font-weight: 600; color: var(--text); margin-bottom: 6px; }
    .modal-body select, .modal-body textarea {
      width: 100%; box-sizing: border-box;
      padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;
      font-size: 13px; font-family: inherit; color: var(--text);
      background: #fff; outline: none; margin-bottom: 14px;
    }
    .modal-body select:focus, .modal-body textarea:focus {
      border-color: var(--accent); box-shadow: 0 0 0 3px rgba(48,186,230,.2);
    }
    .modal-body textarea { resize: vertical; min-height: 80px; }
    .modal-foot {
      display: flex; justify-content: flex-end; gap: 8px;
      padding: 12px 22px 18px;
    }
    .modal-msg { font-size: 12px; margin-top: 8px; padding: 8px 10px; border-radius: 6px; display: none; }
    .modal-msg.error { background: var(--danger-bg); color: var(--danger); display: block; }
    .modal-msg.success { background: #ecfdf5; color: #065f46; display: block; }

    @media (max-width: 700px) {
      .profil-header { flex-direction: column; align-items: flex-start; }
      .profil-avatar { width: 90px; height: 90px; font-size: 32px; }
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
      <li><a href="Annonces.html">Annonces</a></li>
      <?php if (!isset($_SESSION['user_id']) || ($_SESSION['role_type'] ?? '') === 'proprietaire'): ?>
      <li><a href="Publier.php">Publier</a></li>
      <?php endif; ?>
      <li><a href="FAQ.html">FAQ</a></li>
      <li><a href="Favoris.php">Favoris</a></li>
      <?php if (isset($_SESSION['user_id'])): ?>
      <li><a href="messagerie.php">Messages</a></li>
      <li><a href="mon-compte.php" style="font-weight:600;">Mon compte</a></li>
      <?php else: ?>
      <li><a href="Authentification.html">Inscription / Connexion</a></li>
      <?php endif; ?>
    </ul>
  </div>
</header>

<div class="profil-page">

  <!-- ══ Header profil ══ -->
  <div class="profil-header">
    <div class="profil-avatar">
      <?php if (!empty($u['photo_profil']) && file_exists(__DIR__.'/'.$u['photo_profil'])): ?>
        <img src="<?php echo htmlspecialchars($u['photo_profil']); ?>" alt="">
      <?php else: ?>
        <?php echo $initiales; ?>
      <?php endif; ?>
    </div>
    <div class="profil-info">
      <h1><?php echo $nomComplet; ?></h1>
      <div class="profil-role-row">
        <span class="badge-role <?php echo $role==='proprietaire'?'badge-proprio':'badge-loueur'; ?>">
          <i class="fa-solid <?php echo $role==='proprietaire'?'fa-key':'fa-user-graduate'; ?>"></i>
          <?php echo $role==='proprietaire' ? 'Propriétaire' : 'Locataire'; ?>
        </span>
        <?php if (!empty($u['date_inscription'])): ?>
        <span class="profil-date">
          <i class="fa-solid fa-calendar"></i>
          Membre depuis <?php echo date('m/Y', strtotime($u['date_inscription'])); ?>
        </span>
        <?php endif; ?>
      </div>
      <?php if (!empty($u['bio'])): ?>
      <p class="profil-bio"><?php echo htmlspecialchars($u['bio']); ?></p>
      <?php endif; ?>

      <div class="profil-actions">
        <?php if ($isMe): ?>
          <a class="btn btn-outline" href="mon-compte.php">
            <i class="fa-solid fa-pen"></i> Modifier mon profil
          </a>
        <?php else: ?>
          <?php if (isset($_SESSION['user_id'])): ?>
            <a class="btn btn-primary" href="messagerie.php?contact=<?php echo urlencode($u['id_utilisateur']); ?>">
              <i class="fa-solid fa-comment"></i> Contacter
            </a>
            <button class="btn btn-danger" onclick="openReport()">
              <i class="fa-solid fa-flag"></i> Signaler
            </button>
          <?php else: ?>
            <a class="btn btn-primary" href="Authentification.html">
              <i class="fa-solid fa-comment"></i> Contacter
            </a>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ══ Stats ══ -->
  <?php if ($role === 'proprietaire'): ?>
  <div class="profil-stats">
    <div class="stat-card">
      <span class="stat-num"><?php echo $nbAnnonces; ?></span>
      <span class="stat-label">Annonce<?php echo $nbAnnonces>1?'s':''; ?> publiée<?php echo $nbAnnonces>1?'s':''; ?></span>
    </div>
    <div class="stat-card">
      <span class="stat-num"><?php echo date('Y') - (int)date('Y', strtotime($u['date_inscription'])); ?> an<?php echo (date('Y')-(int)date('Y',strtotime($u['date_inscription']))) !== 1 ? 's' : ''; ?></span>
      <span class="stat-label">sur Seek &amp; Stay</span>
    </div>
  </div>
  <?php endif; ?>

  <!-- ══ Annonces publiées ══ -->
  <?php if ($role === 'proprietaire'): ?>
  <div class="profil-annonces">
    <h2>Annonces publiées</h2>
    <?php if (empty($annonces)): ?>
      <div class="empty-state">
        <i class="fa-solid fa-house-circle-xmark" style="font-size:32px;opacity:.3;display:block;margin-bottom:10px;"></i>
        Aucune annonce active.
      </div>
    <?php else: ?>
    <div class="cards-grid">
      <?php foreach ($annonces as $a):
        $img = (!empty($a['image_principale']) && file_exists(__DIR__.'/'.$a['image_principale']))
               ? htmlspecialchars($a['image_principale'])
               : 'img/studio2.jpg';
      ?>
      <a class="mini-card" href="annonce.php?id=<?php echo urlencode($a['id_annonce']); ?>">
        <div class="mini-card-img">
          <img src="<?php echo $img; ?>" alt="" loading="lazy">
        </div>
        <div class="mini-card-body">
          <div class="mini-card-price"><?php echo number_format($a['prix'], 0, ',', ' '); ?> € / mois</div>
          <div class="mini-card-ville">
            <i class="fa-solid fa-location-dot"></i>
            <?php echo htmlspecialchars($a['ville']); ?> · <?php echo $a['superficie']; ?> m²
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

</div>

<!-- ══ Modal Signaler ══ -->
<?php if (isset($_SESSION['user_id']) && !$isMe): ?>
<div class="modal-overlay" id="report-modal" onclick="if(event.target===this) closeReport()">
  <div class="modal">
    <div class="modal-head">
      <i class="fa-solid fa-flag" style="color:var(--danger)"></i>
      <h3>Signaler cet utilisateur</h3>
    </div>
    <div class="modal-body">
      <label for="report-reason">Raison du signalement</label>
      <select id="report-reason">
        <option value="">— Choisir —</option>
        <option value="spam">Spam ou publicité</option>
        <option value="contenu_inapproprie">Contenu inapproprié</option>
        <option value="arnaque">Tentative d'arnaque</option>
        <option value="usurpation">Usurpation d'identité</option>
        <option value="harcelement">Harcèlement</option>
        <option value="autre">Autre raison</option>
      </select>

      <label for="report-comment">Commentaire (facultatif)</label>
      <textarea id="report-comment" placeholder="Précisez le contexte…" maxlength="1000"></textarea>

      <div class="modal-msg" id="report-msg"></div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-outline" onclick="closeReport()">Annuler</button>
      <button class="btn btn-danger" id="report-submit" onclick="submitReport()">
        <i class="fa-solid fa-flag"></i> Envoyer le signalement
      </button>
    </div>
  </div>
</div>
<?php endif; ?>

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
const CIBLE_ID = <?php echo json_encode($u['id_utilisateur']); ?>;

function openReport() {
  const modal = document.getElementById('report-modal');
  if (!modal) return;
  modal.classList.add('open');
  document.getElementById('report-reason').value  = '';
  document.getElementById('report-comment').value = '';
  document.getElementById('report-msg').className = 'modal-msg';
  document.getElementById('report-msg').textContent = '';
  document.getElementById('report-submit').disabled = false;
}
function closeReport() {
  document.getElementById('report-modal').classList.remove('open');
}

async function submitReport() {
  const raison      = document.getElementById('report-reason').value;
  const commentaire = document.getElementById('report-comment').value.trim();
  const msg         = document.getElementById('report-msg');
  const btn         = document.getElementById('report-submit');

  if (!raison) {
    msg.className = 'modal-msg error';
    msg.textContent = 'Veuillez choisir une raison.';
    return;
  }

  btn.disabled = true;

  try {
    const r = await fetch('signaler.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        cible_type: 'utilisateur',
        cible_id:   CIBLE_ID,
        raison:     raison,
        commentaire: commentaire,
      }),
    });
    const d = await r.json();

    if (d.ok) {
      msg.className = 'modal-msg success';
      msg.textContent = 'Merci, votre signalement a bien été envoyé. Notre équipe va l\'examiner.';
      setTimeout(closeReport, 1800);
    } else {
      msg.className = 'modal-msg error';
      msg.textContent = {
        deja_signale:   'Vous avez déjà signalé cet utilisateur.',
        auto_signalement:'Vous ne pouvez pas vous signaler vous-même.',
        non_connecte:   'Vous devez être connecté.',
        donnees_invalides:'Données invalides.',
      }[d.error] || 'Une erreur est survenue.';
      btn.disabled = false;
    }
  } catch(e) {
    msg.className = 'modal-msg error';
    msg.textContent = 'Erreur réseau, réessayez.';
    btn.disabled = false;
  }
}

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeReport();
});
</script>
</body>
</html>
