<?php
require 'session.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: Authentification.html?error=' . urlencode("Connectez-vous pour voir vos favoris."));
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT a.*, f.date_ajout AS date_favori, u.nom, u.lastname
        FROM favoris f
        JOIN annonces a ON a.id_annonce = f.annonce_id
        LEFT JOIN utilisateur_ u ON u.id_utilisateur = a.utilisateur_id
        WHERE f.utilisateur_id = ? AND a.statut = 'actif'
        ORDER BY f.date_ajout DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $favoris = $stmt->fetchAll();
} catch (PDOException $e) { $favoris = []; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mes Favoris — Seek &amp; Stay</title>
  <link rel="icon" type="image/x-icon" href="img/flavicon.png" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
  <link rel="stylesheet" href="styles/styles.css" />
  <style>
    body { background: #f1f5f9; font-family: 'Inter', sans-serif; }
    .fav-page { max-width: 1100px; margin: 30px auto; padding: 0 24px 60px; }
    .fav-header {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 28px; flex-wrap: wrap; gap: 10px;
    }
    .fav-header h1 {
      font-size: 1.8rem; font-weight: 800; color: #0f1c2e; margin: 0;
      display: flex; align-items: center; gap: 10px;
    }
    .fav-header h1 i { color: #ef4444; }
    .fav-header p { font-size: 14px; color: #64748b; margin: 0; }

    .cards-grid {
      display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;
    }
    .annonce-card {
      border-radius: 16px; overflow: hidden; background: #fff;
      box-shadow: 0 2px 8px rgba(0,0,0,.08);
      transition: transform .18s, box-shadow .18s;
      display: flex; flex-direction: column; position: relative;
    }
    .annonce-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,.13); }
    .annonce-card-img { width: 100%; height: 200px; overflow: hidden; position: relative; cursor: pointer; }
    .annonce-card-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .3s; }
    .annonce-card:hover .annonce-card-img img { transform: scale(1.04); }
    .annonce-type-badge {
      position: absolute; top: 12px; left: 12px;
      background: #244676; color: #fff; font-size: 11px; font-weight: 700;
      padding: 4px 10px; border-radius: 20px; text-transform: capitalize;
    }
    .btn-unfav {
      position: absolute; top: 10px; right: 10px;
      width: 36px; height: 36px; border-radius: 50%; border: none;
      background: rgba(255,255,255,.95); color: #ef4444;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,.2);
      font-size: 14px; transition: transform .15s, background .15s;
    }
    .btn-unfav:hover { transform: scale(1.1); background: #fee2e2; }
    .annonce-card-body { padding: 16px 18px; flex: 1; cursor: pointer; }
    .annonce-card-prix { font-size: 1.15rem; font-weight: 800; color: #244676; margin: 0 0 6px; }
    .annonce-card-prix span { font-size: 13px; font-weight: 400; color: #64748b; }
    .annonce-card-ville { font-size: 13px; color: #64748b; display: flex; align-items: center; gap: 5px; margin-bottom: 8px; }
    .annonce-card-desc { font-size: 13px; color: #475569; line-height: 1.5;
      display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .annonce-card-footer {
      padding: 10px 18px 14px;
      display: flex; align-items: center; gap: 14px;
      font-size: 12px; color: #94a3b8; border-top: 1px solid #f1f5f9;
    }
    .annonce-card-footer span { display: flex; align-items: center; gap: 4px; }
    .annonce-card-footer .added-date { margin-left: auto; font-style: italic; }

    .empty-fav {
      background: #fff; border-radius: 14px; padding: 60px 30px;
      text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,.05);
    }
    .empty-fav i { font-size: 52px; color: #cbd5e1; display: block; margin-bottom: 14px; }
    .empty-fav h2 { font-size: 18px; font-weight: 700; color: #334155; margin: 0 0 8px; }
    .empty-fav p { font-size: 14px; color: #64748b; margin: 0 0 20px; }
    .btn-browse {
      display: inline-block; padding: 10px 22px;
      background: #244676; color: #fff; border-radius: 10px;
      text-decoration: none; font-size: 14px; font-weight: 700;
    }
    .btn-browse:hover { background: #1a3459; }

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
      <li><a href="Annonces.html">Annonces</a></li>
      <li><a href="Favoris.php" class="active">Favoris</a></li>
      <?php if (($_SESSION['role_type'] ?? '') === 'proprietaire'): ?>
      <li><a href="Publier.php">Publier</a></li>
      <?php endif; ?>
      <li><a href="Contact.html">Contact</a></li>
      <li><a href="FAQ.html">FAQ</a></li>
      <li><a href="messagerie.php">Messages</a></li>
      <li><a href="mon-compte.php" style="font-weight:600;">Mon compte</a></li>
    </ul>
  </div>
</header>

<div class="fav-page">
  <div class="fav-header">
    <h1><i class="fa-solid fa-heart"></i> Mes favoris</h1>
    <p><?php echo count($favoris); ?> annonce<?php echo count($favoris)>1?'s':''; ?> sauvegardée<?php echo count($favoris)>1?'s':''; ?></p>
  </div>

  <?php if (empty($favoris)): ?>
  <div class="empty-fav">
    <i class="fa-regular fa-heart"></i>
    <h2>Aucun favori pour l'instant</h2>
    <p>Cliquez sur le cœur des annonces qui vous intéressent pour les retrouver ici.</p>
    <a href="Accueil.php" class="btn-browse">
      <i class="fa-solid fa-magnifying-glass" style="margin-right:6px"></i>Parcourir les annonces
    </a>
  </div>
  <?php else: ?>
  <div class="cards-grid">
    <?php foreach ($favoris as $a):
      $img = (!empty($a['image_principale']) && file_exists(__DIR__.'/'.$a['image_principale']))
              ? htmlspecialchars($a['image_principale']) : 'img/studio2.jpg';
      $url = 'annonce.php?id=' . urlencode($a['id_annonce']);
    ?>
    <div class="annonce-card" data-id="<?php echo htmlspecialchars($a['id_annonce']); ?>">
      <div class="annonce-card-img" onclick="location.href='<?php echo $url; ?>'">
        <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($a['ville']); ?>" loading="lazy">
        <span class="annonce-type-badge"><?php echo htmlspecialchars(ucfirst($a['type_immeuble'])); ?></span>
        <button class="btn-unfav" onclick="toggleFavori('<?php echo htmlspecialchars($a['id_annonce']); ?>', this)"
                title="Retirer des favoris">
          <i class="fa-solid fa-heart"></i>
        </button>
      </div>
      <div class="annonce-card-body" onclick="location.href='<?php echo $url; ?>'">
        <p class="annonce-card-prix"><?php echo number_format($a['prix'],0,',',' '); ?> € <span>/ mois</span></p>
        <p class="annonce-card-ville">
          <i class="fa-solid fa-location-dot"></i>
          <?php echo htmlspecialchars($a['ville']); ?> · <?php echo htmlspecialchars($a['code_postal']); ?>
        </p>
        <p class="annonce-card-desc"><?php echo htmlspecialchars($a['description']); ?></p>
      </div>
      <div class="annonce-card-footer">
        <span><i class="fa-solid fa-vector-square"></i> <?php echo $a['superficie']; ?> m²</span>
        <span><i class="fa-solid fa-key"></i> <?php echo ucfirst($a['type_offre']); ?></span>
        <span class="added-date">
          <i class="fa-solid fa-heart" style="color:#ef4444"></i>
          <?php echo date('d/m', strtotime($a['date_favori'])); ?>
        </span>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
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
    if (d.ok && d.action === 'removed') {
      // Supprimer la carte avec animation
      const card = btn.closest('.annonce-card');
      card.style.transition = 'opacity .3s, transform .3s';
      card.style.opacity = '0';
      card.style.transform = 'scale(.95)';
      setTimeout(() => {
        card.remove();
        // Si plus aucune carte, recharger pour afficher l'état vide
        if (document.querySelectorAll('.annonce-card').length === 0) {
          location.reload();
        }
      }, 300);
    }
  } catch(e) {}
}
</script>
</body>
</html>
