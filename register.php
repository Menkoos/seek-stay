<?php
session_start();
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Email invalide");
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO utilisateur_ (email, password) VALUES (?, ?)");

    try {
        $stmt->execute([$email, $hashedPassword]);
        echo "Inscription réussie. <a href='login.php'>Se connecter</a>";
    } catch (PDOException $e) {
        echo "Erreur : cet email existe déjà.";
    }
}
?>

<form method="POST">
    <label>Email :</label><br>
    <input type="email" name="email" required><br>

    <label>Mot de passe :</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit">S'inscrire</button>
</form>
