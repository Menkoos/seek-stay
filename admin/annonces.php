<?php
require __DIR__ . '/_auth.php';

$filter = in_array($_GET['statut'] ?? '', ['actif','inactif','archive']) ? $_GET['statut'] : '';
$q      = trim($_GET['q'] ?? '');

try {
    $sql = "
        SELECT a.*, u.nom, u.lastname, u.email,
               (SELECT COUNT(*) FROM signalements WHERE cible_type='annonce' AND cible_id = a.id_annonce) AS nb_signals
        FROM annonces a
        LEFT JOIN utilisateur_ u ON u.id_utilisateur = a.utilisateur_id
    ";
    $params = []; $where = [];
    if ($filter) { $where[] = "a.statut = ?"; $params[] = $filter; }
    if ($q !== '') {
        $like = '%' . $q . '%';
        $where[] = "(a.ville LIKE ? OR a.adresse LIKE ? OR a.description LIKE ?)";
        array_push($params, $like, $like, $like);
    }
    if ($where) $sql .= " WHERE " . implode(' AND ', $where);
    $sql .= " ORDER BY a.date_publication DESC";

    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    $annonces = $stmt->fetchAll();
} catch (PDOException $e) { $annonces = []; }

$adminPage = 'annonces';
$succes = $_GET['succes'] ?? '';
include __DIR__ . '/_layout.php';
?>

<h1 class="admin-title">Annonces</h1>
<p class="admin-subtitle"><?php echo count($annonces); ?> annonce<?php echo count($annonces)>1?'s':''; ?></p>

<?php if ($succes): ?>
<div class="msg-box success"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars(urldecode($succes)); ?></div>
<?php endif; ?>

<form method="GET" style="margin-bottom:20px;display:flex;gap:10px;flex-wrap:wrap;">
  <input type="search" name="q" value="<?php echo htmlspecialchars($q); ?>"
         placeholder="Rechercher ville, adresse…"
         style="padding:9px 14px;border:1px solid var(--border);border-radius:8px;font-size:13px;width:280px;font-family:inherit;">
  <select name="statut" style="padding:9px 14px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;background:#fff;">
    <option value="">Tous les statuts</option>
    <option value="actif"    <?php echo $filter==='actif'   ?'selected':''; ?>>Actif</option>
    <option value="inactif"  <?php echo $filter==='inactif' ?'selected':''; ?>>Inactif</option>
    <option value="archive"  <?php echo $filter==='archive' ?'selected':''; ?>>Archivé</option>
  </select>
  <button type="submit" class="btn-adm"><i class="fa-solid fa-filter"></i> Filtrer</button>
  <?php if ($q || $filter): ?><a href="annonces.php" class="btn-adm">Effacer</a><?php endif; ?>
</form>

<div class="card" style="padding:0;overflow:hidden;">
  <?php if (empty($annonces)): ?>
    <p style="padding:30px;text-align:center;color:var(--muted);margin:0;">Aucune annonce.</p>
  <?php else: ?>
  <table class="admin-table">
    <thead>
      <tr>
        <th>Date</th>
        <th>Logement</th>
        <th>Propriétaire</th>
        <th>Prix</th>
        <th>Vues</th>
        <th>Signalé</th>
        <th>Statut</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($annonces as $a): ?>
      <tr>
        <td style="font-size:12px;"><?php echo date('d/m/Y', strtotime($a['date_publication'])); ?></td>
        <td>
          <a href="../annonce.php?id=<?php echo urlencode($a['id_annonce']); ?>" target="_blank" style="color:var(--primary);text-decoration:none;font-weight:600;">
            <?php echo htmlspecialchars(ucfirst($a['type_immeuble']).' — '.$a['ville']); ?>
          </a>
          <br>
          <small style="color:var(--muted);font-size:11px;"><?php echo htmlspecialchars($a['adresse']); ?></small>
        </td>
        <td>
          <?php if (!empty($a['utilisateur_id']) && !empty($a['nom'])): ?>
            <a href="../profil.php?id=<?php echo urlencode($a['utilisateur_id']); ?>" target="_blank" style="color:var(--primary);text-decoration:none;">
              <?php echo htmlspecialchars($a['nom'].' '.$a['lastname']); ?>
            </a>
          <?php else: ?>
            <span style="color:var(--muted);font-size:12px;">—</span>
          <?php endif; ?>
        </td>
        <td><strong><?php echo number_format($a['prix'],0,',',' '); ?> €</strong></td>
        <td><?php echo (int)$a['nb_vues']; ?></td>
        <td>
          <?php if ((int)$a['nb_signals'] > 0): ?>
            <span class="badge-adm" style="background:#fef2f2;color:#991b1b;"><?php echo (int)$a['nb_signals']; ?>×</span>
          <?php else: ?>
            <span style="color:var(--muted);font-size:12px;">—</span>
          <?php endif; ?>
        </td>
        <td>
          <span class="badge-adm badge-<?php echo ($a['statut'] ?? 'actif'); ?>">
            <?php echo $a['statut'] ?? 'actif'; ?>
          </span>
        </td>
        <td style="white-space:nowrap;">
          <form method="POST" action="actions.php" style="display:inline;">
            <input type="hidden" name="action" value="annonce_statut">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($a['id_annonce']); ?>">
            <input type="hidden" name="nouveau_statut" value="<?php echo $a['statut']==='actif'?'inactif':'actif'; ?>">
            <button type="submit" class="btn-adm" title="<?php echo $a['statut']==='actif'?'Masquer':'Réactiver'; ?>">
              <i class="fa-solid fa-<?php echo $a['statut']==='actif'?'eye-slash':'eye'; ?>"></i>
            </button>
          </form>
          <form method="POST" action="actions.php" style="display:inline;" onsubmit="return confirm('Supprimer cette annonce définitivement ?');">
            <input type="hidden" name="action" value="annonce_supprimer">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($a['id_annonce']); ?>">
            <button type="submit" class="btn-adm danger" title="Supprimer"><i class="fa-solid fa-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

</main></div>
</body></html>
