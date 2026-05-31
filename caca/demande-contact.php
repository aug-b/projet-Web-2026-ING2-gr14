<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once("connexion.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Accès interdit");
}

$nom = $_POST["nom"] ?? "";
$prenom = $_POST["prenom"] ?? "";
$email = $_POST["email"] ?? "";
$telephone = $_POST["telephone"] ?? "";
$date_naissance = $_POST["date_naissance"] ?? "";

$mot_de_passe = "temporaire";

$photo = NULL;
$photo_id = NULL;


$dossierPhoto = "images/profils/";

if (!is_dir($dossierPhoto)) {
    mkdir($dossierPhoto, 0777, true);
}

if (
    isset($_FILES["photo"]) &&
    $_FILES["photo"]["error"] == 0
) {

    $nomPhoto =
        "user_" .
        time() .
        "_" .
        basename($_FILES["photo"]["name"]);

    if (
        move_uploaded_file(
            $_FILES["photo"]["tmp_name"],
            $dossierPhoto . $nomPhoto
        )
    ) {
        $photo = $dossierPhoto . $nomPhoto;
    }
}

$dossierId = "images/pieces_identite/";

if (!is_dir($dossierId)) {
    mkdir($dossierId, 0777, true);
}

if (
    isset($_FILES["photo_id"]) &&
    $_FILES["photo_id"]["error"] == 0
) {

    $nomId =
        "id_" .
        time() .
        "_" .
        basename($_FILES["photo_id"]["name"]);

    if (
        move_uploaded_file(
            $_FILES["photo_id"]["tmp_name"],
            $dossierId . $nomId
        )
    ) {
        $photo_id = $dossierId . $nomId;
    }
}


$sql = "
INSERT INTO utilisateur_en_attente
(
    nom,
    prenom,
    email,
    mot_de_passe,
    telephone,
    date_de_naissance,
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
    $mot_de_passe,
    $telephone,
    $date_naissance,
    $photo,
    $photo_id
);

if ($stmt->execute()) {

    echo "
    <h2 style='color:green;text-align:center;'>
        Votre demande a bien été envoyée.
    </h2>

    <p style='text-align:center;'>
        Un administrateur va vérifier vos informations.
    </p>

    <div style='text-align:center;'>
        <a href='connexion.html'>
            Retour à la connexion
        </a>
    </div>
    ";

} else {

    die(
        "Erreur insertion : " .
        $stmt->error
    );
}

$stmt->close();
$conn->close();
?>
