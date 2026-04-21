<?php
session_start();
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: Authentification.html");
    exit;
}

$email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';
$remember = !empty($_POST['remember']);

if (empty($email) || empty($password)) {
    header("Location: Authentification.html?tab=login&error=" . urlencode("Veuillez remplir tous les champs."));
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM utilisateur_ WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['mot_de_passe'])) {
        $_SESSION['user_id']  = $user['id_utilisateur'];
        $_SESSION['email']    = $user['email'];
        $_SESSION['nom']      = $user['nom'];
        $_SESSION['lastname'] = $user['lastname'];

        if ($remember) {
            // Générer un token sécurisé et le stocker haché en BDD
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
            $pdo->prepare(
                "UPDATE utilisateur_ SET remember_token = ?, remember_expires = ? WHERE id_utilisateur = ?"
            )->execute([hash('sha256', $token), $expires, $user['id_utilisateur']]);

            setcookie('ss_remember', $user['id_utilisateur'] . ':' . $token, time() + 86400 * 30, '/', '', false, true);
        }

        header("Location: Accueil.php");
        exit;
    } else {
        header("Location: Authentification.html?tab=login&error=" . urlencode("Email ou mot de passe incorrect."));
        exit;
    }
} catch (PDOException $e) {
    header("Location: Authentification.html?tab=login&error=" . urlencode("Une erreur est survenue, veuillez réessayer."));
    exit;
}
