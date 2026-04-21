<?php
session_start();
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: Authentification.php");
    exit;
}

$email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    header("Location: Authentification.php?tab=login&error=" . urlencode("Veuillez remplir tous les champs."));
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
        setcookie('ss_user', $user['lastname'], time() + 86400 * 7, '/');
        header("Location: Accueil.php");
        exit;
    } else {
        header("Location: Authentification.php?tab=login&error=" . urlencode("Email ou mot de passe incorrect."));
        exit;
    }
} catch (PDOException $e) {
    header("Location: Authentification.php?tab=login&error=" . urlencode("Une erreur est survenue, veuillez réessayer."));
    exit;
}
