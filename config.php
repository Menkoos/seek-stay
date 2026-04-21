<?php
// Cacher toutes les erreurs PHP à l'utilisateur (les erreurs restent dans les logs XAMPP)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// ── Identifiants de connexion ─────────────────────────────────────────────
// Si tu as un mot de passe root MySQL, crée l'utilisateur seekstay
// en important create_user.sql dans phpMyAdmin, puis utilise ces identifiants.

define('DB_HOST', 'localhost');
define('DB_NAME', 'bddtest');
define('DB_USER', 'root');   // utilisateur commun au groupe
define('DB_PASS', '');

// Fallback : si seekstay n'existe pas encore, on essaie root sans mot de passe
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Fallback avec root sans mot de passe (XAMPP par défaut)
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            'root',
            '',
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    } catch (PDOException $e2) {
        die("Erreur de connexion à la base de données. Vérifiez vos identifiants MySQL.");
    }
}
