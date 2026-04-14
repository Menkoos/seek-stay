/* ============================================================
   publier.js — Logique de la page Publier.html
   Gère : compteur de caractères, aperçu des images, validation
   ============================================================ */

// ===== COMPTEUR DE CARACTÈRES (description) =====
const textarea = document.getElementById("description");
const nbChars  = document.getElementById("nb-chars");

textarea.addEventListener("input", () => {
  nbChars.textContent = textarea.value.length;
});

// ===== GESTION DES IMAGES =====
const inputImages   = document.getElementById("input-images");
const previewGrid   = document.getElementById("preview-grid");

// Tableau des fichiers sélectionnés + index de l'image principale
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

// ===== VALIDATION À LA SOUMISSION =====
document.getElementById("form-publier").addEventListener("submit", (e) => {
  e.preventDefault();

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

  // Tout est valide → connexion BDD à implémenter
  alert("Annonce prête à être publiée ! (connexion BDD à implémenter)");
});
