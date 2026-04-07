<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<h1>Bienvenue, <?php echo htmlspecialchars($_SESSION['email']); ?></h1>

<a href="logout.php">Se déconnecter</a>
