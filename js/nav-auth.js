// Met à jour la nav selon les cookies ss_user et ss_role (posés par login.php)
(function () {
  function getCookie(name) {
    const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : null;
  }

  const nom  = getCookie('ss_user');
  const role = getCookie('ss_role');

  if (!nom) return;

  // Remplace le lien "Inscription / Connexion" par Mon compte
  const liens = document.querySelectorAll('.header-menu a[href="Authentification.html"], .header-menu a[href="Authentification.php"]');
  liens.forEach(function (lien) {
    const li = lien.parentElement;
    li.innerHTML = '<a href="mon-compte.php" style="font-weight:600;">Mon compte</a>';
  });

  // Cache le lien "Publier" pour les locataires connectés
  if (role && role !== 'proprietaire') {
    document.querySelectorAll('.header-menu a[href="Publier.php"]').forEach(function (a) {
      a.parentElement.style.display = 'none';
    });
  }
})();
