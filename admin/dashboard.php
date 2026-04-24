<?php
require __DIR__ . '/_auth.php';

try {
    $nbUsers       = (int)$pdo->query("SELECT COUNT(*) FROM utilisateur_")->fetchColumn();
    $nbProprios    = (int)$pdo->query("SELECT COUNT(*) FROM utilisateur_ WHERE role_type='proprietaire'")->fetchColumn();
    $nbAnnonces    = (int)$pdo->query("SELECT COUNT(*) FROM annonces WHERE statut='actif'")->fetchColumn();
    $nbSignals     = (int)$pdo->query("SELECT COUNT(*) FROM signalements WHERE statut='en_attente'")->fetchColumn();
    $nbMsgs        = (int)$pdo->query("SELECT COUNT(*) FROM messagerie")->fetchColumn();
    $dernierMois   = (int)$pdo->query("SELECT COUNT(*) FROM utilisateur_ WHERE date_inscription >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

    $signalementsRecents = $pdo->query("
        SELECT s.*, u.nom, u.lastname
        FROM signalements s
        LEFT JOIN utilisateur_ u ON u.id_utilisateur = s.signaleur_id
        ORDER BY s.date_signalement DESC LIMIT 5
    ")->fetchAll();
} catch (PDOException $e) {
    $nbUsers = $nbProprios = $nbAnnonces = $nbSignals = $nbMsgs = $dernierMois = 0;
    $signalementsRecents = [];
}

$adminPage = 'dashboard';
include __DIR__ . '/_layout.php';
?>

<h1 class="admin-title">Tableau de bord</h1>
<p class="admin-subtitle">Vue d'ensemble de la plateforme Seek &amp; Stay</p>

<div class="stats-grid">
  <div class="stat-box">
    <span class="stat-icon"><i class="fa-solid fa-users"></i></span>
    <span class="stat-label">Utilisateurs</span>
    <span class="stat-num"><?php echo $nbUsers; ?></span>
  </div>
  <div class="stat-box">
    <span class="stat-icon"><i class="fa-solid fa-user-plus"></i></span>
    <span class="stat-label">Inscrits (30 j)</span>
    <span class="stat-num"><?php echo $dernierMois; ?></span>
  </div>
  <div class="stat-box">
    <span class="stat-icon"><i class="fa-solid fa-key"></i></span>
    <span class="stat-label">Propriétaires</span>
    <span class="stat-num"><?php echo $nbProprios; ?></span>
  </div>
  <div class="stat-box">
    <span class="stat-icon"><i class="fa-solid fa-house"></i></span>
    <span class="stat-label">Annonces actives</span>
    <span class="stat-num"><?php echo $nbAnnonces; ?></span>
  </div>
  <div class="stat-box" style="border-left:3px solid <?php echo $nbSignals > 0 ? '#dc2626' : 'transparent'; ?>;">
    <span class="stat-icon" style="background:rgba(220,38,38,.12);color:#dc2626;"><i class="fa-solid fa-flag"></i></span>
    <span class="stat-label">Signalements en attente</span>
    <span class="stat-num" style="color:<?php echo $nbSignals > 0 ? '#dc2626' : 'var(--primary)'; ?>"><?php echo $nbSignals; ?></span>
  </div>
  <div class="stat-box">
    <span class="stat-icon"><i class="fa-solid fa-comments"></i></span>
    <span class="stat-label">Messages échangés</span>
    <span class="stat-num"><?php echo $nbMsgs; ?></span>
  </div>
</div>

<div class="card">
  <h2 style="margin:0 0 14px;font-size:16px;">Derniers signalements</h2>
  <?php if (empty($signalementsRecents)): ?>
    <p style="color:var(--muted);font-size:13px;margin:0;">Aucun signalement.</p>
  <?php else: ?>
  <table class="admin-table">
    <thead>
      <tr><th>Date</th><th>Type</th><th>Raison</th><th>Signaleur</th><th>Statut</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($signalementsRecents as $s): ?>
      <tr>
        <td><?php echo date('d/m H:i', strtotime($s['date_signalement'])); ?></td>
        <td><?php echo htmlspecialchars(ucfirst($s['cible_type'])); ?></td>
        <td><?php echo htmlspecialchars(str_replace('_',' ',$s['raison'])); ?></td>
        <td><?php echo htmlspecialchars(($s['nom'] ?? '—') . ' ' . ($s['lastname'] ?? '')); ?></td>
        <td><span class="badge-adm badge-<?php echo $s['statut'] === 'en_attente' ? 'attente' : ($s['statut'] === 'traite' ? 'traite' : 'rejete'); ?>">
          <?php echo str_replace('_',' ', $s['statut']); ?>
        </span></td>
        <td><a class="btn-adm" href="signalements.php">Voir tous</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

</main></div>
</body></html>
