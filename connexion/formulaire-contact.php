<?php

require_once("../connexion/connexion.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nom = $_POST["nom"];
    $prenom = $_POST["prenom"];
    $email = $_POST["email"];
    $telephone = $_POST["telephone"];
    $date_naissance = $_POST["date_naissance"];
    $classe = $_POST["classe"];

    $photo = NULL;
    $photo_id = NULL;

    $dossier = "../uploads/";

    if (!is_dir($dossier)) {
        mkdir($dossier, 0777, true);
    }

    /* Photo de profil */

    if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] == 0) {

        $photo = time() . "_photo_" .
                 basename($_FILES["photo"]["name"]);

        move_uploaded_file(
            $_FILES["photo"]["tmp_name"],
            $dossier . $photo
        );
    }

    /* Pièce d'identité */

    if (isset($_FILES["photo_id"]) && $_FILES["photo_id"]["error"] == 0) {

        $photo_id = time() . "_id_" .
                    basename($_FILES["photo_id"]["name"]);

        move_uploaded_file(
            $_FILES["photo_id"]["tmp_name"],
            $dossier . $photo_id
        );
    }



    $sql = "
        INSERT INTO utilisateur_en_attente
        (
            nom,
            prenom,
            email,
            telephone,
            date_de_naissance,
            classe,
            photo,
            photo_id
        )
        VALUES
        (
            ?, ?, ?, ?, ?, ?, ?, ?
        )
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Erreur préparation : " . $conn->error);
    }

    $stmt->bind_param(
        "ssssssss",
        $nom,
        $prenom,
        $email,
        $telephone,
        $date_naissance,
        $classe,
        $photo,
        $photo_id
    );

    if ($stmt->execute()) {

        header("Location: connexion.html");
		exit();

    } else {

        echo "Erreur : " . $stmt->error;

    }

    $stmt->close();
    $conn->close();
}
?>