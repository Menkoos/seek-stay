<?php
require __DIR__ . '/_auth.php';

$q = trim($_GET['q'] ?? '');
try {
    $sql = "
        SELECT u.*,
               (SELECT COUNT(*) FROM annonces WHERE utilisateur_id = u.id_utilisateur) AS nb_annonces,
               (SELECT COUNT(*) FROM signalements WHERE cible_type='utilisateur' AND cible_id = u.id_utilisateur) AS nb_signals
        FROM utilisateur_ u
    ";
    $params = [];
    if ($q !== '') {
        $sql .= " WHERE u.nom LIKE ? OR u.lastname LIKE ? OR u.email LIKE ? ";
        $like = '%' . $q . '%';
        $params = [$like, $like, $like];
    }
    $sql .= " ORDER BY u.date_inscription DESC";
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    $users = $stmt->fetchAll();
} catch (PDOException $e) { $users = []; }

$adminPage = 'users';
$succes = $_GET['succes'] ?? '';
include __DIR__ . '/_layout.php';
?>

<h1 class="admin-title">Utilisateurs</h1>
<p class="admin-subtitle"><?php echo count($users); ?> utilisateur<?php echo count($users)>1?'s':''; ?></p>

<?php if ($succes): ?>
<div class="msg-box success"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars(urldecode($succes)); ?></div>
<?php endif; ?>

<form method="GET" style="margin-bottom:20px;">
  <input type="search" name="q" value="<?php echo htmlspecialchars($q); ?>"
         placeholder="Rechercher nom, email…"
         style="padding:9px 14px;border:1px solid var(--border);border-radius:8px;font-size:13px;width:300px;font-family:inherit;">
  <button type="submit" class="btn-adm"><i class="fa-solid fa-magnifying-glass"></i> Rechercher</button>
  <?php if ($q): ?><a href="users.php" class="btn-adm">Effacer</a><?php endif; ?>
</form>

<div class="card" style="padding:0;overflow:hidden;">
  <?php if (empty($users)): ?>
    <p style="padding:30px;text-align:center;color:var(--muted);margin:0;">Aucun utilisateur trouvé.</p>
  <?php else: ?>
  <table class="admin-table">
    <thead>
      <tr>
        <th>Utilisateur</th>
        <th>Email</th>
        <th>Rôle</th>
        <th>Annonces</th>
        <th>Signalé</th>
        <th>Inscrit le</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u):
        $isMe = $u['id_utilisateur'] === $_SESSION['user_id'];
      ?>
      <tr>
        <td>
          <a href="../profil.php?id=<?php echo urlencode($u['id_utilisateur']); ?>" target="_blank" style="color:var(--primary);text-decoration:none;font-weight:600;">
            <?php echo htmlspecialchars($u['nom'].' '.$u['lastname']); ?>
          </a>
          <?php if (!empty($u['is_admin'])): ?><span class="badge-adm badge-admin" style="margin-left:6px;">ADMIN</span><?php endif; ?>
        </td>
        <td style="font-size:12px;color:var(--muted);"><?php echo htmlspecialchars($u['email']); ?></td>
        <td>
          <span class="badge-adm <?php echo ($u['role_type'] ?? '')==='proprietaire'?'badge-proprio':'badge-loueur'; ?>">
            <?php echo ($u['role_type'] ?? '')==='proprietaire'?'Propriétaire':'Locataire'; ?>
          </span>
        </td>
        <td><?php echo (int)$u['nb_annonces']; ?></td>
        <td>
          <?php if ((int)$u['nb_signals'] > 0): ?>
            <span class="badge-adm" style="background:#fef2f2;color:#991b1b;"><?php echo (int)$u['nb_signals']; ?>×</span>
          <?php else: ?>
            <span style="color:var(--muted);font-size:12px;">—</span>
          <?php endif; ?>
        </td>
        <td style="font-size:12px;"><?php echo $u['date_inscription'] ? date('d/m/Y', strtotime($u['date_inscription'])) : '—'; ?></td>
        <td style="white-space:nowrap;">
          <?php if (!$isMe): ?>
            <form method="POST" action="actions.php" style="display:inline;">
              <input type="hidden" name="action" value="user_toggle_admin">
              <input type="hidden" name="id" value="<?php echo htmlspecialchars($u['id_utilisateur']); ?>">
              <button type="submit" class="btn-adm" title="<?php echo $u['is_admin'] ? 'Retirer admin' : 'Donner admin'; ?>">
                <i class="fa-solid fa-shield<?php echo $u['is_admin'] ? '-halved':''; ?>"></i>
              </button>
            </form>
            <form method="POST" action="actions.php" style="display:inline;"
                  onsubmit="return confirm('Supprimer définitivement <?php echo htmlspecialchars(addslashes($u['nom'].' '.$u['lastname'])); ?> ? Ses annonces et messages seront aussi supprimés.');">
              <input type="hidden" name="action" value="user_supprimer">
              <input type="hidden" name="id" value="<?php echo htmlspecialchars($u['id_utilisateur']); ?>">
              <button type="submit" class="btn-adm danger" title="Supprimer"><i class="fa-solid fa-trash"></i></button>
            </form>
          <?php else: ?>
            <span style="color:var(--muted);font-size:11px;">(vous)</span>
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
