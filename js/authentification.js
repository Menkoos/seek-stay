/* Bascule entre le formulaire Connexion et Inscription
   - type : "loginForm" ou "registerForm"
   - Active le bon formulaire et le bon bouton */
function showForm(type) {
  document.getElementById("loginForm").classList.remove("active");
  document.getElementById("registerForm").classList.remove("active");
  document.getElementById(type).classList.add("active");

  document.getElementById("btn-login").classList.remove("active");
  document.getElementById("btn-register").classList.remove("active");
  document.getElementById("btn-" + type).classList.add("active");
}

/* Affiche ou cache le panneau de saisie du code secret admin
   Remet aussi le champ et le message d'erreur à zéro à chaque ouverture */
function toggleAdmin() {
  const panel = document.getElementById("admin-panel");
  panel.style.display = panel.style.display === "block" ? "none" : "block";
  document.getElementById("admin-code").value = "";
  document.getElementById("admin-error").style.display = "none";
}

/* Vérifie le code secret saisi par l'utilisateur
   - Correct → redirige vers le dashboard admin
   - Incorrect → affiche un message d'erreur et vide le champ */
function verifierAdmin() {
  const code = document.getElementById("admin-code").value;
  if (code === "Aurora") {
    window.location.href = "admin/dashboard.html";
  } else {
    document.getElementById("admin-error").style.display = "block";
    document.getElementById("admin-code").value = "";
  }
}
