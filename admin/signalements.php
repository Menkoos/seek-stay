<?php
require __DIR__ . '/_auth.php';

// Filtre sur le statut
$filterStatut = in_array($_GET['statut'] ?? '', ['en_attente','traite','rejete']) ? $_GET['statut'] : '';

try {
    $sql = "
        SELECT s.*,
               u.nom AS sig_nom, u.lastname AS sig_lastname, u.email AS sig_email,
               a.ville AS ann_ville, a.type_immeuble AS ann_type, a.prix AS ann_prix,
               c.nom AS cible_nom, c.lastname AS cible_lastname, c.email AS cible_email
        FROM signalements s
        LEFT JOIN utilisateur_ u ON u.id_utilisateur = s.signaleur_id
        LEFT JOIN annonces a     ON s.cible_type = 'annonce'      AND a.id_annonce = s.cible_id
        LEFT JOIN utilisateur_ c ON s.cible_type = 'utilisateur'  AND c.id_utilisateur = s.cible_id
    ";
    $params = [];
    if ($filterStatut) { $sql .= " WHERE s.statut = ?"; $params[] = $filterStatut; }
    $sql .= " ORDER BY s.date_signalement DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $signalements = $stmt->fetchAll();

    $counts = [
        'en_attente' => (int)$pdo->query("SELECT COUNT(*) FROM signalements WHERE statut='en_attente'")->fetchColumn(),
        'traite'     => (int)$pdo->query("SELECT COUNT(*) FROM signalements WHERE statut='traite'")->fetchColumn(),
        'rejete'     => (int)$pdo->query("SELECT COUNT(*) FROM signalements WHERE statut='rejete'")->fetchColumn(),
    ];
} catch (PDOException $e) {
    $signalements = [];
    $counts = ['en_attente'=>0,'traite'=>0,'rejete'=>0];
}

$adminPage = 'signalements';
$succes = $_GET['succes'] ?? '';
include __DIR__ . '/_layout.php';
?>

<h1 class="admin-title">Signalements</h1>
<p class="admin-subtitle">Consultez et traitez les signalements envoyés par les utilisateurs</p>

<?php if ($succes): ?>
<div class="msg-box success">
  <i class="fa-solid fa-circle-check"></i>
  <?php echo htmlspecialchars(urldecode($succes)); ?>
</div>
<?php endif; ?>

<!-- Filtres -->
<div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;">
  <a href="signalements.php" class="btn-adm <?php echo !$filterStatut?'':''; ?>"
     style="<?php echo !$filterStatut?'border-color:var(--primary);color:var(--primary);':''; ?>">
    Tous (<?php echo array_sum($counts); ?>)
  </a>
  <a href="signalements.php?statut=en_attente" class="btn-adm"
     style="<?php echo $filterStatut==='en_attente'?'border-color:#d97706;color:#d97706;background:#fef3c7;':''; ?>">
    En attente (<?php echo $counts['en_attente']; ?>)
  </a>
  <a href="signalements.php?statut=traite" class="btn-adm"
     style="<?php echo $filterStatut==='traite'?'border-color:#059669;color:#059669;background:#ecfdf5;':''; ?>">
    Traités (<?php echo $counts['traite']; ?>)
  </a>
  <a href="signalements.php?statut=rejete" class="btn-adm"
     style="<?php echo $filterStatut==='rejete'?'border-color:#dc2626;color:#dc2626;background:#fef2f2;':''; ?>">
    Rejetés (<?php echo $counts['rejete']; ?>)
  </a>
</div>

<div class="card">
  <?php if (empty($signalements)): ?>
    <p style="color:var(--muted);text-align:center;padding:40px 0;margin:0;">
      <i class="fa-solid fa-inbox" style="font-size:32px;opacity:.3;display:block;margin-bottom:10px;"></i>
      Aucun signalement <?php echo $filterStatut ? 'dans cette catégorie' : ''; ?>.
    </p>
  <?php else: ?>
  <table class="admin-table">
    <thead>
      <tr>
        <th>Date</th>
        <th>Type</th>
        <th>Cible</th>
        <th>Raison</th>
        <th>Signaleur</th>
        <th>Commentaire</th>
        <th>Statut</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($signalements as $s):
        $cibleLabel = $s['cible_type'] === 'annonce'
          ? ($s['ann_ville'] ? 'Annonce : '.ucfirst($s['ann_type']).' — '.$s['ann_ville'].' ('.(int)$s['ann_prix'].'€)' : 'Annonce supprimée')
          : ($s['cible_nom'] ? trim($s['cible_nom'].' '.$s['cible_lastname']).' ('.$s['cible_email'].')' : 'Utilisateur supprimé');
        $cibleUrl = $s['cible_type'] === 'annonce'
          ? '../annonce.php?id=' . urlencode($s['cible_id'])
          : '../profil.php?id=' . urlencode($s['cible_id']);
      ?>
      <tr>
        <td><?php echo date('d/m/Y H:i', strtotime($s['date_signalement'])); ?></td>
        <td>
          <span class="badge-adm <?php echo $s['cible_type']==='annonce' ? 'badge-proprio' : 'badge-loueur'; ?>">
            <?php echo $s['cible_type']==='annonce' ? '🏠 Annonce' : '👤 User'; ?>
          </span>
        </td>
        <td><a href="<?php echo $cibleUrl; ?>" target="_blank" style="color:var(--primary);text-decoration:none;"><?php echo htmlspecialchars($cibleLabel); ?></a></td>
        <td style="font-weight:600;"><?php echo htmlspecialchars(ucfirst(str_replace('_',' ',$s['raison']))); ?></td>
        <td>
          <?php echo htmlspecialchars(trim(($s['sig_nom'] ?? '—').' '.($s['sig_lastname'] ?? ''))); ?><br>
          <small style="color:var(--muted);font-size:11px;"><?php echo htmlspecialchars($s['sig_email'] ?? ''); ?></small>
        </td>
        <td style="max-width:220px;font-size:12px;color:var(--muted);">
          <?php echo htmlspecialchars($s['commentaire'] ?? '—'); ?>
        </td>
        <td>
          <span class="badge-adm badge-<?php echo $s['statut']==='en_attente'?'attente':($s['statut']==='traite'?'traite':'rejete'); ?>">
            <?php echo str_replace('_',' ', $s['statut']); ?>
          </span>
        </td>
        <td style="white-space:nowrap;">
          <?php if ($s['statut'] === 'en_attente'): ?>
          <form method="POST" action="actions.php" style="display:inline;">
            <input type="hidden" name="action" value="signalement_statut">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($s['id_signalement']); ?>">
            <input type="hidden" name="nouveau_statut" value="traite">
            <button type="submit" class="btn-adm success" title="Marquer traité"><i class="fa-solid fa-check"></i></button>
          </form>
          <form method="POST" action="actions.php" style="display:inline;">
            <input type="hidden" name="action" value="signalement_statut">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($s['id_signalement']); ?>">
            <input type="hidden" name="nouveau_statut" value="rejete">
            <button type="submit" class="btn-adm danger" title="Rejeter"><i class="fa-solid fa-xmark"></i></button>
          </form>
          <?php endif; ?>
          <?php if ($s['cible_type'] === 'annonce' && $s['ann_ville']): ?>
          <form method="POST" action="actions.php" style="display:inline;"
                onsubmit="return confirm('Supprimer cette annonce ?');">
            <input type="hidden" name="action" value="annonce_supprimer">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($s['cible_id']); ?>">
            <input type="hidden" name="retour" value="signalements.php">
            <button type="submit" class="btn-adm danger" title="Supprimer l'annonce"><i class="fa-solid fa-trash"></i></button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

</main></div>
</body></html>
