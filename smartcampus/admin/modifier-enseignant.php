<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once("../connexion/connexion.php");

if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: ../connexion/connexion.html");
    exit();
}

if (!isset($_GET["id"])) {
    die("ID enseignant manquant.");
}

$id = intval($_GET["id"]);

$stmt = $conn->prepare("
    SELECT
        u.*,
        e.specialite
    FROM utilisateur u
    INNER JOIN enseignant e
        ON u.id_utilisateur = e.id_utilisateur
    WHERE u.id_utilisateur = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Enseignant introuvable.");
}

$enseignant = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nom = $_POST["nom"];
    $prenom = $_POST["prenom"];
    $email = $_POST["email"];
    $telephone = $_POST["telephone"];
    $date_naissance = $_POST["date_naissance"];
    $specialite = $_POST["specialite"];

    $stmt = $conn->prepare("
        UPDATE utilisateur
        SET
            nom = ?,
            prenom = ?,
            email = ?,
            telephone = ?,
            date_de_naissance = ?
        WHERE id_utilisateur = ?
    ");

    $stmt->bind_param(
        "sssssi",
        $nom,
        $prenom,
        $email,
        $telephone,
        $date_naissance,
        $id
    );

    $stmt->execute();

    $stmt = $conn->prepare("
        UPDATE enseignant
        SET specialite = ?
        WHERE id_utilisateur = ?
    ");

    $stmt->bind_param(
        "si",
        $specialite,
        $id
    );

    $stmt->execute();

    header("Location: enseignants.php?success=2");
    exit();
}
?>

<!DOCTYPE html>

<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Modifier Enseignant</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body>

<div class="layout">

```
<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="../images/logo-blanc.png" alt="">
    </div>
</aside>

<main class="main">

    <div class="card" style="max-width:800px;margin:auto;">

        <div class="card-title">
            ✏️ Modifier un enseignant
        </div>

        <form method="POST">

            <div class="profil-info-grid">

                <div class="profil-info-item">
                    <span class="profil-info-label">Nom</span>
                    <input
                        type="text"
                        class="form-input"
                        name="nom"
                        value="<?= htmlspecialchars($enseignant['nom']) ?>"
                        required>
                </div>

                <div class="profil-info-item">
                    <span class="profil-info-label">Prénom</span>
                    <input
                        type="text"
                        class="form-input"
                        name="prenom"
                        value="<?= htmlspecialchars($enseignant['prenom']) ?>"
                        required>
                </div>

                <div class="profil-info-item">
                    <span class="profil-info-label">Email</span>
                    <input
                        type="email"
                        class="form-input"
                        name="email"
                        value="<?= htmlspecialchars($enseignant['email']) ?>"
                        required>
                </div>

                <div class="profil-info-item">
                    <span class="profil-info-label">Téléphone</span>
                    <input
                        type="text"
                        class="form-input"
                        name="telephone"
                        value="<?= htmlspecialchars($enseignant['telephone']) ?>">
                </div>

                <div class="profil-info-item">
                    <span class="profil-info-label">Date de naissance</span>
                    <input
                        type="date"
                        class="form-input"
                        name="date_naissance"
                        value="<?= $enseignant['date_de_naissance'] ?>">
                </div>

                <div class="profil-info-item">
                    <span class="profil-info-label">Spécialité</span>
                    <input
                        type="text"
                        class="form-input"
                        name="specialite"
                        value="<?= htmlspecialchars($enseignant['specialite']) ?>">
                </div>

            </div>

            <div style="display:flex;gap:10px;margin-top:25px;">

                <button
                    type="submit"
                    class="btn-primary">
                    💾 Enregistrer
                </button>

                <a href="enseignants.php">
                    <button
                        type="button"
                        class="btn-outline">
                        ↩ Retour
                    </button>
                </a>

            </div>

        </form>

    </div>

</main>
```

</div>

</body>
</html>
