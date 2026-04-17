/* ============================================================
   publier.js — Logique de la page Publier.html
   Gère : message de succès, compteur de caractères,
          aperçu des images, validation et envoi du formulaire
   ============================================================ */

// ===== MESSAGE DE SUCCÈS APRÈS REDIRECTION =====
// Affiché quand l'URL contient ?succes=1 (après traitement_annonce.php)
if (new URLSearchParams(window.location.search).get("succes") === "1") {
  const banner = document.getElementById("banner-succes");
  if (banner) {
    banner.style.display = "block";
    // Fait défiler la page vers le haut pour voir le message
    window.scrollTo({ top: 0, behavior: "smooth" });
  }
}

// ===== COMPTEUR DE CARACTÈRES (description) =====
const textarea = document.getElementById("description");
const nbChars  = document.getElementById("nb-chars");

textarea.addEventListener("input", () => {
  nbChars.textContent = textarea.value.length;
});

// ===== GESTION DES IMAGES =====
const inputImages   = document.getElementById("input-images");
const previewGrid   = document.getElementById("preview-grid");

// Tableau de tous les fichiers sélectionnés + index de l'image principale
let fichiers        = [];
let indexPrincipale = 0;

// Quand l'utilisateur choisit des fichiers via l'explorateur
inputImages.addEventListener("change", (e) => {
  fichiers = fichiers.concat(Array.from(e.target.files));
  renderPreviews();
  // Remet l'input à zéro pour permettre de re-sélectionner les mêmes fichiers
  inputImages.value = "";
});

// Affiche tous les aperçus dans la grille
function renderPreviews() {
  previewGrid.innerHTML = "";

  fichiers.forEach((fichier, index) => {
    const reader = new FileReader();

    reader.onload = (e) => {
      const item = document.createElement("div");
      item.className = "preview-item" + (index === indexPrincipale ? " is-principale" : "");
      item.title = "Cliquez pour définir comme image principale";

      item.innerHTML = `
        <img src="${e.target.result}" alt="Aperçu image ${index + 1}" />
        <button class="btn-remove" title="Supprimer cette image">
          <i class="fa-solid fa-xmark"></i>
        </button>
        <span class="badge-principale">Principale</span>
      `;

      // Clic sur la carte → définir comme image principale
      item.addEventListener("click", (ev) => {
        if (ev.target.closest(".btn-remove")) return;
        indexPrincipale = index;
        renderPreviews();
      });

      // Clic sur le bouton croix → supprimer l'image
      item.querySelector(".btn-remove").addEventListener("click", (ev) => {
        ev.stopPropagation();
        fichiers.splice(index, 1);
        if (indexPrincipale >= fichiers.length) indexPrincipale = 0;
        renderPreviews();
      });

      previewGrid.appendChild(item);
    };

    reader.readAsDataURL(fichier);
  });
}

// ===== VALIDATION ET ENVOI DU FORMULAIRE =====
document.getElementById("form-publier").addEventListener("submit", (e) => {
  e.preventDefault();

  // Vérifie que les radios obligatoires sont cochés
  const typeImmeuble = document.querySelector('input[name="type_immeuble"]:checked');
  const typeOffre    = document.querySelector('input[name="type_offre"]:checked');

  if (!typeImmeuble) {
    alert("Veuillez sélectionner un type d'immeuble.");
    return;
  }
  if (!typeOffre) {
    alert("Veuillez sélectionner un type d'offre.");
    return;
  }
  if (fichiers.length === 0) {
    alert("Veuillez ajouter au moins une photo.");
    return;
  }

  // Transmet l'index de l'image principale au PHP via un champ caché
  document.getElementById("index-principale").value = indexPrincipale;

  // Remet tous les fichiers dans l'input (ils avaient été vidés à chaque ajout)
  // DataTransfer permet de reconstruire la liste de fichiers
  const dt = new DataTransfer();
  fichiers.forEach(f => dt.items.add(f));
  inputImages.files = dt.files;

  // Tout est valide → soumet le formulaire vers traitement_annonce.php
  e.target.submit();
});
