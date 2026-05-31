<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

session_start();

if (!isset($_POST["email"]) || !isset($_POST["password"])) {
    header("Location: connexion.html");
    exit();
}

$email = $_POST["email"];
$password = $_POST["password"];

$conn = new mysqli("localhost", "root", "root", "smartcampus");

if ($conn->connect_error) {
    die("Erreur connexion BDD : " . $conn->connect_error);
}

/* Requête préparée */
$stmt = $conn->prepare("
    SELECT *
    FROM utilisateur
    WHERE email = ?
    AND mot_de_passe = ?
");

$stmt->bind_param("ss", $email, $password);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $user = $result->fetch_assoc();

    $_SESSION["id_utilisateur"] = $user["id_utilisateur"];
    $_SESSION["user"] = $user["email"];
    $_SESSION["nom"] = $user["nom"];
    $_SESSION["prenom"] = $user["prenom"];
    $_SESSION["role"] = $user["role"];

    if ($user["role"] == "admin") {
        header("Location: dashboard-admin.php");
        exit();
    }

    elseif ($user["role"] == "enseignant") {
        header("Location: dashboard-prof.php");
        exit();
    }

    elseif ($user["role"] == "eleve") {
        header("Location: dashboard-etudiant.php");
        exit();
    }

    else {
        die("Rôle inconnu.");
    }

} else {

    header("Location: connexion.html?error=1");
    exit();

}

$stmt->close();
$conn->close();

?>