<?php
// Connexion à la BDD et récupération des sections de la page mentions légales
require 'config.php';
$stmt = $pdo->query("SELECT section, titre, contenu FROM pages_contenu WHERE page = 'mentions' ORDER BY id ASC");
$sections = [];
foreach ($stmt->fetchAll() as $row) {
    $sections[$row['section']] = $row; // indexé par nom de section pour accès facile
}

// Récupère la date de la dernière modification parmi toutes les sections
$stmtDate = $pdo->query("SELECT MAX(date_modification) as derniere_maj FROM pages_contenu WHERE page = 'mentions'");
$dateBrute = $stmtDate->fetchColumn(); // format : "2026-04-10 22:52:01"

// Formate la date en français : "10 avril 2026 à 22h52"
$mois = ['01'=>'Janvier','02'=>'Février','03'=>'Mars','04'=>'Avril','05'=>'Mai','06'=>'Juin',
         '07'=>'Juillet','08'=>'Août','09'=>'Septembre','10'=>'Octobre','11'=>'Novembre','12'=>'Décembre'];
$dt = new DateTime($dateBrute);
$dateMaj = $dt->format('d') . ' ' . $mois[$dt->format('m')] . ' ' . $dt->format('Y') . ' à ' . $dt->format('H\hi');

// Fonction utilitaire : retourne le titre d'une section (ou une valeur par défaut)
function titre($sections, $key) {
    return isset($sections[$key]) ? htmlspecialchars($sections[$key]['titre']) : '';
}

// Fonction utilitaire : retourne le contenu d'une section (ou une valeur par défaut)
function contenu($sections, $key) {
    return isset($sections[$key]) ? nl2br(htmlspecialchars($sections[$key]['contenu'])) : '';
}
?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <title>Mentions légales</title>
    <meta name="description" content="Mentions légales du site de location de logements pour étudiants" />
    <link rel="icon" type="image/x-icon" href="img/flavicon.png" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <link rel="stylesheet" href="styles/styles.css" />
  </head>

  <body>
    <header>
      <div class="flex">
        <a href="Accueil.html" class="header-logo">
          <img src="img/iconSite.png" class="header-logo" alt="logo" />
        </a>
        <ul class="header-menu">
          <li><a href="Accueil.html">Accueil</a></li>
          <li><a href="Offres.html">Offres</a></li>
          <li><a href="Publier.html">Publier</a></li>
          <li><a href="FAQ.html">FAQ</a></li>
          <li><a href="Favoris.html">Favoris</a></li>
          <li><a href="Contact.html">Contact</a></li>
          <li><a href="Authentification.html">Inscription / Connexion</a></li>
        </ul>
      </div>
    </header>

    <!-- Hero -->
    <section class="mentions-hero">
      <h1>Mentions légales et CGU</h1>
      <p>Informations légales relatives à l'utilisation de ce site</p>
      <span class="badge-date">Dernière mise à jour : <?php echo $dateMaj; ?></span>
    </section>

    <!-- Contenu : chaque section vient de la BDD -->
    <div class="mentions-container">

      <div class="mentions-section">
        <h2><?php echo titre($sections, 'editeur_site'); ?></h2>
        <p><?php echo contenu($sections, 'editeur_site'); ?></p>
        <p>Contact : <a href="Contact.html">via notre formulaire de contact</a></p>
      </div>

      <hr class="divider" />

      <div class="mentions-section">
        <h2><?php echo titre($sections, 'hebergement'); ?></h2>
        <p><?php echo contenu($sections, 'hebergement'); ?></p>
      </div>

      <hr class="divider" />

      <div class="mentions-section">
        <h2><?php echo titre($sections, 'propriete_intellectuelle'); ?></h2>
        <p><?php echo contenu($sections, 'propriete_intellectuelle'); ?></p>
      </div>

      <hr class="divider" />

      <div class="mentions-section">
        <h2><?php echo titre($sections, 'donnees_personnelles'); ?></h2>
        <p><?php echo contenu($sections, 'donnees_personnelles'); ?></p>
        <p>Pour exercer ces droits, contactez-nous via <a href="Contact.html">notre formulaire de contact</a>.</p>
      </div>

      <hr class="divider" />

      <div class="mentions-section">
        <h2><?php echo titre($sections, 'cookies'); ?></h2>
        <p><?php echo contenu($sections, 'cookies'); ?> <a href="GestionCookies.html">Gestion des cookies</a>.</p>
      </div>

      <hr class="divider" />

      <div class="mentions-section">
        <h2><?php echo titre($sections, 'responsabilite'); ?></h2>
        <p><?php echo contenu($sections, 'responsabilite'); ?></p>
      </div>

      <hr class="divider" />

      <div class="mentions-section">
        <h2><?php echo titre($sections, 'droit_applicable'); ?></h2>
        <p><?php echo contenu($sections, 'droit_applicable'); ?></p>
      </div>

    </div>

    <footer>
      <div class="footer-top">
        <div class="footer-left">
          <img src="img/iconSite_WhiteText.png" class="logo-footer" alt="Icône du site internet" />
        </div>
        <div class="footer-center">
          <p class="footer-text">
            Ce site a été conçu et développé par les élèves de l'ISEP du groupe G9A 2025/2026 en utilisant HTML, CSS, JavaScript et PHP - Tous droits réservés
          </p>
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
