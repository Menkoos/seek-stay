
    var map = null;

    function setVue(vue) {
    var annonces = document.getElementById('annonces');
    var vueMap   = document.getElementById('vue-map');
    var btnListe = document.getElementById('btn-liste');
    var btnMap   = document.getElementById('btn-map');

    if (vue === 'liste') {
    /* --- Vue liste --- */
    annonces.classList.remove('annonces-hidden');
    annonces.classList.remove('vue-liste');
    vueMap.classList.remove('vue-map-visible');
    btnListe.classList.add('active');
    btnMap.classList.remove('active');

} else {
    /* --- Vue map --- */
    annonces.classList.add('annonces-hidden');
    vueMap.classList.add('vue-map-visible');
    btnMap.classList.add('active');
    btnListe.classList.remove('active');

    /* Initialise Leaflet une seule fois */
    if (!map) {
    map = L.map('vue-map').setView([46.6034, 1.8883], 6);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
}).addTo(map);

    /* --- Marqueurs des logements --- */
    L.marker([48.8566, 2.3522]).addTo(map)
    .bindPopup('<b>Studio Étudiant</b><br>1167 € · 20m² · Paris');

    L.marker([48.8600, 2.3550]).addTo(map)
    .bindPopup('<b>Résidence étudiante</b><br>1300 € · 23m² · Paris');

    L.marker([50.6292, 3.0573]).addTo(map)
    .bindPopup('<b>Studio Étudiant</b><br>886 € · 19m² · Lille');

    L.marker([48.8700, 2.3300]).addTo(map)
    .bindPopup('<b>Jolie chambre</b><br>450 € · 9m² · Paris');

    L.marker([48.8550, 2.3600]).addTo(map)
    .bindPopup('<b>Joli studio</b><br>881 € · 25m² · Paris');

    L.marker([47.7000, 2.1800]).addTo(map)
    .bindPopup('<b>Studio Étudiant</b><br>530 € · 18m² · Fleury');
}

    /* Corrige l'affichage quand la div était cachée */
    setTimeout(function() { map.invalidateSize(); }, 100);
}
}
