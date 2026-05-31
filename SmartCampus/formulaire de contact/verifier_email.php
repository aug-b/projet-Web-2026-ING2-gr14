<?php
require_once("../connexion/connexion.php");

if (!isset($_POST["email"])) {
    echo "erreur";
    exit();
}

$email = $_POST["email"];

$sql = "SELECT id_utilisateur FROM utilisateur WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "existe";
} else {
    echo "disponible";
}
?>