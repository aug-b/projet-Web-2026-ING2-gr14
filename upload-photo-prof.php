<?php

session_start();
require_once("connexion.php");

if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: connexion.html");
    exit();
}

$id_utilisateur = $_SESSION["id_utilisateur"];

if (
    isset($_FILES["photo"]) &&
    $_FILES["photo"]["error"] === 0
) {

    $dossier = "images/profils/";

    if (!is_dir($dossier)) {
        mkdir($dossier, 0777, true);
    }

    $extension = strtolower(
        pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION)
    );

    $autorisees = ["jpg", "jpeg", "png", "gif", "webp"];

    if (in_array($extension, $autorisees)) {

        $nom = "user_" . $id_utilisateur . "_" . time() . "." . $extension;

        $chemin = $dossier . $nom;

        move_uploaded_file(
            $_FILES["photo"]["tmp_name"],
            $chemin
        );

        $sql = "
            UPDATE utilisateur
            SET photo = ?
            WHERE id_utilisateur = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $chemin, $id_utilisateur);
        $stmt->execute();
    }
}

header("Location: profil-prof.php");
exit();