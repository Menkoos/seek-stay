<?php
session_start();
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: Authentification.php?tab=register");
    exit;
}

$nom       = trim($_POST['nom']       ?? '');
$lastname  = trim($_POST['lastname']  ?? '');
$email     = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$password  = $_POST['password']  ?? '';
$confirm   = $_POST['confirm']   ?? '';
$telephone = trim($_POST['telephone'] ?? '');

if (empty($nom) || empty($lastname) || empty($email) || empty($password) || empty($confirm)) {
    header("Location: Authentification.php?tab=register&error=" . urlencode("Veuillez remplir tous les champs obligatoires."));
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: Authentification.php?tab=register&error=" . urlencode("Adresse email invalide."));
    exit;
}

if (strlen($password) < 8) {
    header("Location: Authentification.php?tab=register&error=" . urlencode("Le mot de passe doit contenir au moins 8 caractères."));
    exit;
}

if ($password !== $confirm) {
    header("Location: Authentification.php?tab=register&error=" . urlencode("Les mots de passe ne correspondent pas."));
    exit;
}

try {
    $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO utilisateur_ (id_utilisateur, nom, lastname, email, mot_de_passe, telephone, date_inscription)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$uuid, $nom, $lastname, $email, $hashed, $telephone]);

    $_SESSION['user_id']  = $uuid;
    $_SESSION['email']    = $email;
    $_SESSION['nom']      = $nom;
    $_SESSION['lastname'] = $lastname;
    setcookie('ss_user', $lastname, time() + 86400 * 7, '/');

    header("Location: Accueil.php");
    exit;
} catch (PDOException $e) {
    header("Location: Authentification.php?tab=register&error=" . urlencode("Cette adresse email est déjà utilisée."));
    exit;
}
