<?php

session_start();

if(!isset($_POST["email"]) || !isset($_POST["password"])){
    header("Location: connexion/connexion.html");
    exit();
}
$email = $_POST["email"];
$password = $_POST["password"];

$conn = new mysqli("localhost", "root", "", "smartcampus");

if ($conn->connect_error) {
    die("Erreur connexion BDD : " . $conn->connect_error);
}

$sql = "SELECT * FROM utilisateur 
        WHERE email='$email' 
        AND mot_de_passe='$password'";

$result = $conn->query($sql);

if (!$result) {
    die("Erreur SQL : " . $conn->error);
}

if($result->num_rows > 0){

    $user = $result->fetch_assoc();
$_SESSION["id_utilisateur"] = $user["id_utilisateur"];
$_SESSION["user"] = $user["email"];
$_SESSION["nom"] = $user["nom"];
$_SESSION["prenom"] = $user["prenom"];
$_SESSION["role"] = $user["role"];

    if($user["role"] == "admin"){
        echo "c'est un admin";
    }
    elseif($user["role"] == "enseignant"){
        echo "c'est un prof";
    }
    elseif($user["role"] == "eleve"){
          header("Location: ../eleve/dashboard-etudiant.php");
    exit();

    }

}else{
        header("Location: connexion.html?error=1");
    exit();
}
?>