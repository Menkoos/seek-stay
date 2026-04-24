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

/* Accès au backoffice : désormais contrôlé par la colonne is_admin
   dans la DB. Connectez-vous normalement puis allez sur admin/dashboard.php */
function verifierAdmin() {
  window.location.href = "admin/dashboard.php";
}
