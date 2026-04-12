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
    $message_succes = "✅ Les mentions légales ont bien été mises à jour.";
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
  <style>
    /* ===== GÉNÉRAL ===== */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: "Inter", sans-serif; background-color: #f0f2f8; color: #0f1c2e; }

    /* ===== HEADER ADMIN ===== */
    .admin-header {
      background-color: #244676;
      color: white;
      padding: 16px 40px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .admin-header h1 { font-size: 1.3rem; font-weight: 700; }
    .admin-header a {
      color: #30bae6;
      text-decoration: none;
      font-size: 0.9rem;
    }
    .admin-header a:hover { text-decoration: underline; }

    /* ===== CONTENU PRINCIPAL ===== */
    .admin-container {
      max-width: 900px;
      margin: 40px auto;
      padding: 0 20px 60px;
    }

    /* Titre de la page */
    .admin-container h2 {
      font-size: 1.6rem;
      font-weight: 800;
      margin-bottom: 8px;
      color: #0f1c2e;
    }
    .admin-container .subtitle {
      color: #666;
      font-size: 0.95rem;
      margin-bottom: 30px;
    }

    /* Message de succès après enregistrement */
    .alert-succes {
      background-color: #e6f9ee;
      border: 1px solid #2a9d5c;
      color: #2a9d5c;
      padding: 14px 20px;
      border-radius: 10px;
      margin-bottom: 24px;
      font-weight: 600;
    }

    /* ===== CARTE D'UNE SECTION ===== */
    .section-card {
      background-color: white;
      border-radius: 14px;
      padding: 28px 32px;
      margin-bottom: 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.07);
      border-left: 4px solid #30bae6;
    }

    /* Numéro et nom de la section */
    .section-card .section-label {
      font-size: 0.78rem;
      font-weight: 600;
      color: #30bae6;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 10px;
    }

    /* Champ titre */
    .section-card label {
      display: block;
      font-size: 0.85rem;
      font-weight: 600;
      color: #444;
      margin-bottom: 6px;
      margin-top: 14px;
    }

    .section-card input[type="text"],
    .section-card textarea {
      width: 100%;
      padding: 12px 16px;
      border: 1.5px solid #dde1ee;
      border-radius: 10px;
      font-family: "Inter", sans-serif;
      font-size: 0.92rem;
      color: #0f1c2e;
      background-color: #fafbff;
      outline: none;
      transition: border-color 0.2s;
    }

    .section-card input[type="text"]:focus,
    .section-card textarea:focus {
      border-color: #30bae6;
    }

    .section-card textarea {
      min-height: 120px;
      resize: vertical; /* L'admin peut agrandir la zone de texte */
    }

    /* ===== BOUTON ENREGISTRER ===== */
    .btn-save {
      display: block;
      width: 100%;
      margin-top: 30px;
      padding: 16px;
      background-color: #244676;
      color: white;
      font-size: 1rem;
      font-weight: 700;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      transition: background-color 0.2s;
    }
    .btn-save:hover { background-color: #1a3358; }

    /* Lien vers la page publique */
    .preview-link {
      display: block;
      text-align: center;
      margin-top: 14px;
      color: #30bae6;
      font-size: 0.9rem;
      text-decoration: none;
    }
    .preview-link:hover { text-decoration: underline; }
  </style>
</head>
<body>

  <!-- En-tête de l'interface admin -->
  <div class="admin-header">
    <h1>⚙️ Backoffice – Mentions légales</h1>
    <a href="../Mentionlegales.php" target="_blank">👁 Voir la page publique</a>
  </div>

  <div class="admin-container">
    <h2>Modifier les Mentions légales</h2>
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

      <!-- Bouton d'enregistrement global -->
      <button type="submit" class="btn-save">💾 Enregistrer toutes les modifications</button>
    </form>

    <a href="../Mentionlegales.php" target="_blank" class="preview-link">
      → Voir le résultat sur la page publique
    </a>
  </div>

</body>
</html>
