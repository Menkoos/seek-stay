mapboxgl.accessToken = "token_a_remplacer";

const logements = [
    {
        titre: "Studio Étudiant",
        prix: "1167 €",
        surface: "20m²",
        ville: "Paris",
        coords: [2.3522, 48.8566]
    },
    {
        titre: "Résidence étudiante",
        prix: "1300 €",
        surface: "23m²",
        ville: "Paris",
        coords: [2.3550, 48.8600]
    },
    {
        titre: "Studio Étudiant",
        prix: "886 €",
        surface: "19m²",
        ville: "Lille",
        coords: [3.0573, 50.6292]
    }
];

const mapWrapper = document.querySelector(".map-wrapper");
const annonces = document.getElementById("annonces");
const btnListe = document.getElementById("btn-liste");
const btnMap = document.getElementById("btn-map");

let map = null;
let userPosition = null;

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
        zoom: 5.5
    });

    map.addControl(new mapboxgl.NavigationControl(), "top-right");

    const geolocate = new mapboxgl.GeolocateControl({
        positionOptions: {
            enableHighAccuracy: true
        },
        trackUserLocation: false,
        showUserHeading: true
    });

    map.addControl(geolocate, "top-right");

    geolocate.on("geolocate", (e) => {
        userPosition = [e.coords.longitude, e.coords.latitude];
    });

    logements.forEach((logement) => {
        const popup = new mapboxgl.Popup({ offset: 25 }).setHTML(`
      <div class="popup-logement">
        <strong>${logement.titre}</strong>
        <p>${logement.prix} · ${logement.surface}</p>
        <p>${logement.ville}</p>
        <button class="btn-itineraire" onclick="calculerItineraire(${logement.coords[0]}, ${logement.coords[1]})">
          Itinéraire
        </button>
      </div>
    `);

        new mapboxgl.Marker()
            .setLngLat(logement.coords)
            .setPopup(popup)
            .addTo(map);
    });

    map.on("style.load", () => {
        if (!map.getSource("mapbox-dem")) {
            map.addSource("mapbox-dem", {
                type: "raster-dem",
                url: "mapbox://mapbox.mapbox-terrain-dem-v1",
                tileSize: 512,
                maxzoom: 14
            });
        }

        map.setTerrain({
            source: "mapbox-dem",
            exaggeration: 1.2
        });
    });
}

async function calculerItineraire(destLng, destLat) {
    if (!navigator.geolocation) {
        alert("La géolocalisation n'est pas disponible sur ce navigateur.");
        return;
    }

    navigator.geolocation.getCurrentPosition(
        async (position) => {
            userPosition = [position.coords.longitude, position.coords.latitude];

            const url = `https://api.mapbox.com/directions/v5/mapbox/driving/${userPosition[0]},${userPosition[1]};${destLng},${destLat}?geometries=geojson&access_token=${mapboxgl.accessToken}`;

            try {
                const response = await fetch(url);
                const data = await response.json();

                if (!data.routes || !data.routes.length) {
                    alert("Aucun itinéraire trouvé.");
                    return;
                }

                const route = data.routes[0].geometry;

                if (map.getSource("route")) {
                    map.getSource("route").setData({
                        type: "Feature",
                        properties: {},
                        geometry: route
                    });
                } else {
                    map.addSource("route", {
                        type: "geojson",
                        data: {
                            type: "Feature",
                            properties: {},
                            geometry: route
                        }
                    });

                    map.addLayer({
                        id: "route",
                        type: "line",
                        source: "route",
                        layout: {
                            "line-join": "round",
                            "line-cap": "round"
                        },
                        paint: {
                            "line-color": "#244676",
                            "line-width": 6,
                            "line-opacity": 0.9
                        }
                    });
                }

                const bounds = new mapboxgl.LngLatBounds();
                bounds.extend(userPosition);
                bounds.extend([destLng, destLat]);

                map.fitBounds(bounds, {
                    padding: 60
                });
            } catch (error) {
                console.error(error);
                alert("Erreur pendant le calcul de l'itinéraire.");
            }
        },
        () => {
            alert("Impossible de récupérer votre position.");
        },
        {
            enableHighAccuracy: true
        }
    );
}

window.calculerItineraire = calculerItineraire;