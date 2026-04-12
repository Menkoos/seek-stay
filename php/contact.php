<?php
$conn = new mysqli("localhost", "root", "", "ton_database");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nom = test_input($_POST['nom']);
    $email = test_input($_POST['email']);
    $telephone = test_input($_POST['telephone']);
    $message = test_input($_POST['message']);
    $genre = $_POST['gender'];
    $typeDemande = $_POST['typeDemande'];


    $sql = "INSERT INTO contact (nom, email, telephone, genre, type_demande, message)
            VALUES ('$nom', '$email', '$telephone', '$genre', '$typeDemande', '$message')";

    $conn->query($sql);
}



function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

$conn->close();
?>