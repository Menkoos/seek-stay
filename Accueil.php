<?php
require 'config.php';
try {
    $stmt = $pdo->query("SELECT * FROM annonces WHERE statut = 'actif' ORDER BY date_publication DESC");
    $annonces = $stmt->fetchAll();
} catch (PDOException $e) {
    $annonces = [];
}
?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8" />
  <title>Seek & Stay | Location de Logements pour étudiants</title>
  <meta name="description" content="Ceci est notre projet APP - site de location de logements pour étudiants" />
  <link rel="icon" type="image/x-icon" href="img/flavicon.png" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
  <link rel="stylesheet" href="styles/styles.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link href="https://api.mapbox.com/mapbox-gl-js/v3.20.0/mapbox-gl.css" rel="stylesheet">
  <script src="https://api.mapbox.com/mapbox-gl-js/v3.20.0/mapbox-gl.js"></script>
  <script src="js/vue-map.js" defer></script>

  <link rel="stylesheet" href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.css" type="text/css" />
  <script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.min.js"></script>
</head>

<body>

<!-- ===== HEADER ===== -->
<header>
  <div class="flex">
    <a href="Accueil.php" class="header-logo">
      <img src="img/iconSite.png" class="header-logo" alt="logo Seek & Stay" />
    </a>
    <ul class="header-menu">
      <li><a href="Accueil.php" class="active">Accueil</a></li>
      <li><a href="Publier.html">Publier</a></li>
      <li><a href="FAQ.html">FAQ</a></li>
      <li><a href="Favoris.html">Favoris</a></li>
      <li><a href="Contact.html">Contact</a></li>
      <li><a href="HUB.html">HUB</a></li>
      <li><a href="Authentification.html">Inscription / Connexion</a></li>
    </ul>
  </div>
</header>

<!-- ===== BANNIÈRE ===== -->
<div class="slider" id="home">
  <img src="img/banniere3.jpg" class="slider-background" alt="bannière du site Seek & Stay" />
</div>

<!-- ===== FILTRES + RÉSULTATS ===== -->
<section class="page-recherche">

  <!-- Colonne de gauche : Filtres -->
  <form action="/filtres_offres.php">
    <aside class="filters">
      <h2>Filtres avancés</h2>

      <div class="filter-group1">
        <h4>Types de logement</h4>
        <input type="radio" name="logement" value="studio"> Studio<br>
        <input type="radio" name="logement" value="appartement"> Appartement<br>
        <input type="radio" name="logement" value="colocation"> Colocation<br>
        <input type="radio" name="logement" value="chambre"> Chambre<br>
      </div>

      <div class="filter-group">
        <h4>Prix du logement</h4>
        <label>min :</label>
        <input type="number" name="prix_min" min="150" max="3000" placeholder="€"><br>
        <label>max :</label>
        <input type="number" name="prix_max" min="150" max="3000" placeholder="€">
      </div>

      <div class="filter-group">
        <h4>Superficie</h4>
        <label>Surface minimale :</label>
        <input type="number" id="surface_min" name="surface_min" min="9" max="300" placeholder="m²">
        <label>Surface maximale :</label>
        <input type="number" id="surface_max" name="surface_max" min="9" max="300" placeholder="m²">
      </div>

      <div class="filter-group">
        <h4>Équipement</h4>
        <input type="checkbox" id="wifi" name="equipements[]" value="wifi">
        <label for="wifi">Wi-Fi</label><br>
        <input type="checkbox" id="lave-linge" name="equipements[]" value="machine_a_laver">
        <label for="lave-linge">Machine à laver</label><br>
        <input type="checkbox" id="micro-onde" name="equipements[]" value="micro_onde">
        <label for="micro-onde">Micro-ondes</label><br>
        <input type="checkbox" id="four" name="equipements[]" value="four">
        <label for="four">Four</label><br>
        <input type="checkbox" id="frigo" name="equipements[]" value="frigo">
        <label for="frigo">Frigo</label><br>
        <input type="checkbox" id="ustensiles" name="equipements[]" value="ustensiles_cuisine">
        <label for="ustensiles">Ustensiles de cuisine</label><br>
        <input type="checkbox" id="ascenseur" name="equipements[]" value="ascenseur">
        <label for="ascenseur">Ascenseur</label><br>
        <input type="checkbox" id="parking" name="equipements[]" value="parking">
        <label for="parking">Parking</label><br>
      </div>

      <div class="filter-group1">
        <h4>Type de propriétaire</h4>
        <input type="radio" name="proprietaire" value="particulier"> Particulier<br>
        <input type="radio" name="proprietaire" value="agence"> Agence<br>
      </div>

      <div class="filter-group">
        <h4>Aide</h4>
        <input type="checkbox" id="CAF" name="aides[]" value="caf">
        <label for="CAF">CAF accepté</label><br>
        <input type="checkbox" id="APL" name="aides[]" value="apl">
        <label for="APL">APL accepté</label><br>
      </div>

    </aside>
  </form>

  <!-- Colonne de droite : Recherche et Annonces -->
  <div class="resultats">

    <!-- Barre de recherche et boutons d'affichage -->
    <section class="search-section">
      <div class="search-bar">
        <input type="text" placeholder="Search..." />
        <i class="fa-solid fa-magnifying-glass"></i>
      </div>
      <div class="search-icons">
        <button class="icon-button active" id="btn-liste" onclick="setVue('liste')" title="Vue liste">
          <i class="fa-solid fa-list"></i>
        </button>
        <button class="icon-button" id="btn-map" onclick="setVue('map')" title="Vue carte">
          <i class="fa-solid fa-location-dot"></i>
        </button>
      </div>
    </section>

    <!-- Carte Mapbox -->
    <div class="map-wrapper">
      <div class="map-toolbar">
        <button type="button" id="btn-plan">Plan</button>
        <button type="button" id="btn-satellite">Satellite</button>
        <button type="button" id="btn-3d">3D</button>
      </div>
      <div id="map"></div>
    </div>

    <!-- ===== LISTE DES ANNONCES (dynamique depuis la BDD) ===== -->
    <section class="annonces" id="annonces">

      <?php if (empty($annonces)): ?>
      <!-- Aucune annonce en base de données -->
      <p style="padding: 30px; color: #888; font-style: italic;">
        Aucune annonce disponible pour le moment.
      </p>

      <?php else: ?>
      <?php foreach ($annonces as $annonce): ?>

      <div class="carte-logement">
        <div class="carte-image">
          <?php
                // Affiche l'image principale si elle existe, sinon une image par défaut
                $img = !empty($annonce['image_principale']) ? $annonce['image_principale'] : 'img/placeholder.jpg';
              ?>
          <img src="<?php echo htmlspecialchars($img); ?>" alt="Photo du logement à <?php echo htmlspecialchars($annonce['ville']); ?>" />
        </div>
        <div class="carte-contenu">
          <div class="carte-haut">
            <h2><?php echo htmlspecialchars(ucfirst($annonce['type_immeuble'])); ?></h2>
          </div>
          <p class="prix"><?php echo number_format($annonce['prix'], 0, ',', ' '); ?> € — <?php echo $annonce['superficie']; ?> m²</p>
          <p class="description"><?php echo htmlspecialchars($annonce['description']); ?></p>
          <p class="ville"><?php echo htmlspecialchars($annonce['ville']); ?></p>
        </div>
      </div>

      <?php endforeach; ?>
      <?php endif; ?>

    </section>

  </div>
</section>

<!-- ===== FOOTER ===== -->
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

<!-- Script de validation surface min/max dans les filtres -->
<script>
  const minInput = document.getElementById('surface_min');
  const maxInput = document.getElementById('surface_max');

  minInput.addEventListener('input', () => {
    const minValue = parseInt(minInput.value);
    if (!isNaN(minValue)) {
      maxInput.min = minValue;
      if (parseInt(maxInput.value) < minValue) maxInput.value = minValue;
    }
  });
</script>

</body>
</html>
