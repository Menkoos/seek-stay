<?php
// define variables and set to empty values
$nomErr = $emailErr = $telephoneErr = $genderErr = $typeErr = "";

$nom = $email = $telephone = $message = $gender = $typeDemande = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  // NOM
  if (empty($_POST["nom"])) {
    $nomErr = "Nom requis";
  } else {
    $nom = test_input($_POST["nom"]);
  }

  // EMAIL
  if (empty($_POST["email"])) {
    $emailErr = "Email requis";
  } else {
    $email = test_input($_POST["email"]);
  }

  // TELEPHONE
  if (empty($_POST["telephone"])) {
    $telephoneErr = "Téléphone requis";
  } else {
    $telephone = test_input($_POST["telephone"]);
  }

  // MESSAGE (optionnel)
  if (empty($_POST["message"])) {
    $message = "";
  } else {
    $message = test_input($_POST["message"]);
  }

  // GENRE
  if (empty($_POST["gender"])) {
    $genderErr = "Genre requis";
  } else {
    $gender = test_input($_POST["gender"]);
  }

  // TYPE DEMANDE
  if (empty($_POST["typeDemande"])) {
    $typeErr = "Type de demande requis";
  } else {
    $typeDemande = test_input($_POST["typeDemande"]);
  }

  // INSERT si aucun champ obligatoire vide
  if (
    $nomErr == "" &&
    $emailErr == "" &&
    $telephoneErr == "" &&
    $genderErr == "" &&
    $typeErr == ""
  ) {

    $conn = new mysqli("localhost", "root", "", "ton_database");

    $sql = "INSERT INTO contact (nom, email, telephone, genre, type_demande, message)
            VALUES ('$nom', '$email', '$telephone', '$gender', '$typeDemande', '$message')";

    $conn->query($sql);
    $conn->close();
  }
}
?> 

<?php
echo "<h2>DEBUG - Your Input:</h2>";

echo "Nom: " . $nom;
echo "<br>";

echo "Email: " . $email;
echo "<br>";

echo "Telephone: " . $telephone;
echo "<br>";

echo "Message: " . $message;
echo "<br>";

echo "Genre: " . $gender;
echo "<br>";

echo "Type de demande: " . $typeDemande;
?>