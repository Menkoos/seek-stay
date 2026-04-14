<?php
// Connexion à la BDD
require '../config.php';

// Traitement du formulaire : quand l'admin clique "Enregistrer"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['sections'] as $section => $data) {
        $stmt = $pdo->prepare("
            UPDATE pages_contenu
            SET titre = ?, contenu = ?, date_modification = NOW()
            WHERE page = 'mentions' AND section = ?
        ");
        $stmt->execute([$data['titre'], $data['contenu'], $section]);
    }
    $message_succes = "Les mentions légales ont bien été mises à jour.";
}

// Récupération des sections actuelles depuis la BDD
$stmt = $pdo->query("SELECT * FROM pages_contenu WHERE page = 'mentions' ORDER BY id ASC");
$sections = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Admin – Gestion des Mentions légales</title>
  <link rel="icon" type="image/x-icon" href="../img/flavicon.png" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../styles/styles.css" />
</head>
<body>

  <!-- En-tête de l'interface admin -->
  <div class="admin-header">
    <div class="admin-header-left">
      <img src="../img/iconSite_WhiteText.png" alt="logo" />
      <h1>Backoffice – Mentions légales</h1>
    </div>
    <a href="dashboard.html">← Retour au dashboard</a>
  </div>

  <div class="admin-container">
    <h2>Modifier les Mentions Légales</h2>
    <p class="subtitle">Chaque section correspond à un bloc de la page publique. Les modifications sont enregistrées en base de données et visibles immédiatement.</p>

    <!-- Message de confirmation après enregistrement -->
    <?php if (!empty($message_succes)): ?>
      <div class="alert-succes"><?php echo $message_succes; ?></div>
    <?php endif; ?>

    <!-- Formulaire : une carte par section -->
    <form method="POST">
      <?php foreach ($sections as $section): ?>
        <div class="section-card">
          <div class="section-label">Section — <?php echo htmlspecialchars($section['section']); ?></div>

          <label>Titre</label>
          <input
            type="text"
            name="sections[<?php echo htmlspecialchars($section['section']); ?>][titre]"
            value="<?php echo htmlspecialchars($section['titre']); ?>"
          />

          <label>Contenu</label>
          <textarea
            name="sections[<?php echo htmlspecialchars($section['section']); ?>][contenu]"
          ><?php echo htmlspecialchars($section['contenu']); ?></textarea>
        </div>
      <?php endforeach; ?>

      <button type="submit" class="btn-save">Enregistrer toutes les modifications</button>
    </form>

    <a href="../Mentionlegales.php" target="_blank" class="preview-link">
      → Voir le résultat sur la page publique
    </a>
  </div>

</body>
</html>
