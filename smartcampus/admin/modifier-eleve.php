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
    die("Élève introuvable.");
}

$id = intval($_GET["id"]);

/* Récupération des classes */
$classes = [];

$result = $conn->query("
    SELECT *
    FROM classe
    ORDER BY nom_classe
");

while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}

/* Récupération des infos de l'élève */
$stmt = $conn->prepare("
    SELECT
        u.id_utilisateur,
        u.nom,
        u.prenom,
        u.email,
        el.niveau_scolaire,
        i.id_classe
    FROM utilisateur u
    INNER JOIN eleve el
        ON u.id_utilisateur = el.id_utilisateur
    LEFT JOIN inscription i
        ON i.id_eleve = u.id_utilisateur
    WHERE u.id_utilisateur = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Élève introuvable.");
}

$eleve = $result->fetch_assoc();

/* Enregistrement des modifications */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nom = $_POST["nom"];
    $prenom = $_POST["prenom"];
    $email = $_POST["email"];
    $niveau = $_POST["niveau"];
    $id_classe = $_POST["id_classe"];

    $stmt = $conn->prepare("
        UPDATE utilisateur
        SET
            nom = ?,
            prenom = ?,
            email = ?
        WHERE id_utilisateur = ?
    ");

    $stmt->bind_param(
        "sssi",
        $nom,
        $prenom,
        $email,
        $id
    );

    $stmt->execute();

    $stmt = $conn->prepare("
        UPDATE eleve
        SET niveau_scolaire = ?
        WHERE id_utilisateur = ?
    ");

    $stmt->bind_param(
        "si",
        $niveau,
        $id
    );

    $stmt->execute();

    $stmt = $conn->prepare("
        DELETE FROM inscription
        WHERE id_eleve = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    if (!empty($id_classe)) {

        $stmt = $conn->prepare("
            INSERT INTO inscription
            (
                date_inscription,
                statut,
                id_eleve,
                id_classe
            )
            VALUES
            (
                CURDATE(),
                'valide',
                ?,
                ?
            )
        ");

        $stmt->bind_param(
            "ii",
            $id,
            $id_classe
        );

        $stmt->execute();
    }

    header("Location: eleves.php?success=2");
    exit();
}
?>

<!DOCTYPE html>

<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Modifier Élève</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body>

<div class="layout">

```
<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="../images/logo-blanc.png" alt="logo">
    </div>
</aside>

<main class="main">

    <div class="card" style="max-width:800px;margin:auto;">

        <div class="card-title">
            ✏️ Modifier un élève
        </div>

        <form method="POST">

            <div class="profil-info-grid">

                <div class="profil-info-item">
                    <span class="profil-info-label">Nom</span>
                    <input
                        type="text"
                        class="form-input"
                        name="nom"
                        value="<?= htmlspecialchars($eleve['nom']) ?>"
                        required>
                </div>

                <div class="profil-info-item">
                    <span class="profil-info-label">Prénom</span>
                    <input
                        type="text"
                        class="form-input"
                        name="prenom"
                        value="<?= htmlspecialchars($eleve['prenom']) ?>"
                        required>
                </div>

                <div class="profil-info-item">
                    <span class="profil-info-label">Email</span>
                    <input
                        type="email"
                        class="form-input"
                        name="email"
                        value="<?= htmlspecialchars($eleve['email']) ?>"
                        required>
                </div>

                <div class="profil-info-item">
                    <span class="profil-info-label">Niveau</span>

                    <select
                        name="niveau"
                        class="form-input">

                        <option value="second"
                            <?= ($eleve["niveau_scolaire"]=="second") ? "selected" : "" ?>>
                            Seconde
                        </option>

                        <option value="premiere"
                            <?= ($eleve["niveau_scolaire"]=="premiere") ? "selected" : "" ?>>
                            Première
                        </option>

                        <option value="terminal"
                            <?= ($eleve["niveau_scolaire"]=="terminal") ? "selected" : "" ?>>
                            Terminale
                        </option>

                    </select>
                </div>

                <div class="profil-info-item">
                    <span class="profil-info-label">Classe</span>

                    <select
                        class="form-input"
                        name="id_classe">

                        <option value="">
                            Aucune classe
                        </option>

                        <?php foreach ($classes as $classe) : ?>

                            <option
                                value="<?= $classe['id_classe'] ?>"
                                <?= ($eleve['id_classe'] == $classe['id_classe']) ? 'selected' : '' ?>>

                                <?= htmlspecialchars($classe['nom_classe']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

            </div>

            <div style="display:flex;gap:10px;margin-top:25px;">

                <button
                    type="submit"
                    class="btn-primary">
                    💾 Enregistrer
                </button>

                <a href="eleves.php">
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
