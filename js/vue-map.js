mapboxgl.accessToken =
    "pk.eyJ1IjoibWVua29vcyIsImEiOiJjbW5zeHkycXIwZTk2Mm9zOWptcmtkdjh2In0.v-2w8GRPwZaHopaowVlsFA";

// Données des logements affichés sur la carte
const logements = [
  {
    titre: "Studio Étudiant",
    prix: "1167 €",
    surface: "20m²",
    ville: "Paris",
    coords: [2.3522, 48.8566],
  },
  {
    titre: "Résidence étudiante",
    prix: "1300 €",
    surface: "23m²",
    ville: "Paris",
    coords: [2.355, 48.86],
  },
  {
    titre: "Studio Étudiant",
    prix: "886 €",
    surface: "19m²",
    ville: "Lille",
    coords: [3.0573, 50.6292],
  },
];

const mapWrapper = document.querySelector(".map-wrapper");
const annonces = document.getElementById("annonces");
const btnListe = document.getElementById("btn-liste");
const btnMap = document.getElementById("btn-map");

let map = null;

// Bascule entre la vue liste et la vue carte
function setVue(vue) {
  if (vue === "liste") {
    annonces.classList.remove("annonces-hidden");
    mapWrapper.classList.remove("map-visible");
    btnListe.classList.add("active");
    btnMap.classList.remove("active");
  } else {
    annonces.classList.add("annonces-hidden");
    mapWrapper.classList.add("map-visible");
    btnMap.classList.add("active");
    btnListe.classList.remove("active");

    if (!map) {
      initialiserCarte();
    }

    setTimeout(() => {
      map.resize();
    }, 100);
  }
}

window.setVue = setVue;

function initialiserCarte() {
  map = new mapboxgl.Map({
    container: "map",
    style: "mapbox://styles/mapbox/standard",
    center: [2.3522, 48.8566],
    zoom: 5.5,
  });

  // Contrôles de navigation (zoom, rotation)
  map.addControl(new mapboxgl.NavigationControl(), "top-right");

  // Contrôle de géolocalisation
  map.addControl(new mapboxgl.GeolocateControl({
    positionOptions: { enableHighAccuracy: true },
    trackUserLocation: false,
    showUserHeading: true,
  }), "top-right");

  // Ajout des marqueurs avec popup pour chaque logement
  logements.forEach((logement) => {
    const popup = new mapboxgl.Popup({ offset: 25 }).setHTML(`
      <div class="popup-logement">
        <strong>${logement.titre}</strong>
        <p>${logement.prix} · ${logement.surface}</p>
        <p>${logement.ville}</p>
      </div>
    `);

    new mapboxgl.Marker().setLngLat(logement.coords).setPopup(popup).addTo(map);
  });

  // Ajout du relief 3D après chargement du style
  map.on("style.load", () => {
    if (!map.getSource("mapbox-dem")) {
      map.addSource("mapbox-dem", {
        type: "raster-dem",
        url: "mapbox://mapbox.mapbox-terrain-dem-v1",
        tileSize: 512,
        maxzoom: 14,
      });
    }
    map.setTerrain({ source: "mapbox-dem", exaggeration: 1.2 });
  });

  // ===== BOUTONS TOOLBAR =====

  // Bouton Plan : carte standard (vue normale)
  document.getElementById("btn-plan").addEventListener("click", () => {
    map.setStyle("mapbox://styles/mapbox/standard");
    setActiveToolbar("btn-plan");
  });

  // Bouton Satellite : vue satellite avec labels
  document.getElementById("btn-satellite").addEventListener("click", () => {
    map.setStyle("mapbox://styles/mapbox/satellite-streets-v12");
    setActiveToolbar("btn-satellite");
  });

  // Bouton 3D : incline la caméra pour une vue en perspective
  document.getElementById("btn-3d").addEventListener("click", () => {
    map.easeTo({ pitch: 60, bearing: -20, duration: 800 });
    setActiveToolbar("btn-3d");
  });
}

// Met en surbrillance le bouton toolbar actif
function setActiveToolbar(id) {
  document.querySelectorAll(".map-toolbar button").forEach((btn) => {
    btn.style.background = "rgba(255,255,255,0.92)";
    btn.style.color = "#333";
  });
  const active = document.getElementById(id);
  if (active) {
    active.style.background = "#244676";
    active.style.color = "white";
  }
}
