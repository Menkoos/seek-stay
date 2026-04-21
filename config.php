<?php
// Paramètres de connexion à la base de données MySQL (XAMPP local)
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'bddtest');
define('DB_USER', 'root');
define('DB_PASS', ''); // Mot de passe vide par défaut sur XAMPP

// Connexion PDO
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS
    );
    // Affiche les erreurs SQL clairement (utile en développement)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
