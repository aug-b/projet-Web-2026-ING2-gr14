<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once("connexion.php");

if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: connexion.html");
    exit();
}

$id_etudiant = $_SESSION["id_utilisateur"];

/* Récupération des infos élève */
$sql = "
    SELECT u.nom,
           u.prenom,
           u.email,
           u.telephone,
           u.date_de_naissance,
           u.photo,
           cl.nom_classe
    FROM utilisateur u
    LEFT JOIN inscription i ON u.id_utilisateur = i.id_eleve
    LEFT JOIN classe cl ON i.id_classe = cl.id_classe
    WHERE u.id_utilisateur = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_etudiant);
$stmt->execute();

$result = $stmt->get_result();
$eleve = $result->fetch_assoc();

if (!$eleve) {
    die("Élève introuvable.");
}

/* Modification des infos */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {

    if ($_POST["action"] === "mdp") {

        $nouveau = $_POST["nouveau_mdp"];
        $confirm = $_POST["confirm_mdp"];

        if ($nouveau === $confirm && strlen($nouveau) >= 6) {

            $sql = "
                UPDATE utilisateur
                SET mot_de_passe = ?
                WHERE id_utilisateur = ?
            ";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $nouveau, $id_etudiant);
            $stmt->execute();

            header("Location: profil-eleve.php?mdp=1");
            exit();

        } else {
            header("Location: profil-eleve.php?mdp=error");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Profil Élève — SmartCampus</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="layout">

    <aside class="sidebar">
        <div class="sidebar-logo">
          <a href="dashboard-etudiant.php"><img src="images/logo-blanc.png" alt="logo"></a>
        </div>

        <nav class="nav">
            <a href="dashboard-etudiant.php" class="nav-item">🏠 Tableau de bord</a>
            <a href="planning-eleve.php" class="nav-item">📅 Planning</a>
            <a href="notes-eleve.php" class="nav-item">📝 Notes</a>
            <a href="presences-eleve.php" class="nav-item">✅ Présences</a>
            <a href="profil-eleve.php" class="nav-item active">👤 Profil</a>
        </nav>

        <a href="deconnexion.php" class="nav-logout">🚪 Déconnexion</a>
    </aside>

    <main class="main">

        <h1 class="page-title">Mon profil</h1>

        <div class="profile-layout">

            <div class="profile-card">

       <form action="upload-photo.php" method="POST" enctype="multipart/form-data">

    <div class="profile-image">
        <img
            src="<?= !empty($eleve['photo']) ? htmlspecialchars($eleve['photo']) : 'images/default.png' ?>"
            alt="photo profil">
    </div>

    <h2 class="profile-name">
        <?= htmlspecialchars($eleve["prenom"] . " " . $eleve["nom"]) ?>
    </h2>

    <input
        type="file"
        name="photo"
        accept="image/*"
        class="input-photo"
        required>

    <button type="submit" class="upload-btn">
        Modifier la photo
    </button>

</form>
            </div>

            <div class="info-section">

                <div class="info-card">

                    <h2>Informations personnelles</h2>

                    <div class="info-row">
                        <span class="label">Email</span>
                        <span class="value">
                            <?= htmlspecialchars($eleve["email"] ?? "Non renseigné") ?>
                        </span>
                    </div>

                    <div class="info-row">
                        <span class="label">Téléphone</span>
                        <span class="value">
                            <?= htmlspecialchars($eleve["telephone"] ?? "Non renseigné") ?>
                        </span>
                    </div>

                    <div class="info-row">
                        <span class="label">Date de naissance</span>
                        <span class="value">
                            <?php
                            echo !empty($eleve["date_de_naissance"])
                                ? date("d/m/Y", strtotime($eleve["date_de_naissance"]))
                                : "Non renseignée";
                            ?>
                        </span>
                    </div>

                    <div class="info-row">
                        <span class="label">Classe</span>
                        <span class="value">
                            <?= htmlspecialchars($eleve["nom_classe"] ?? "Non affecté") ?>
                        </span>
                    </div>

                </div>

                <div class="info-card">

                    <h2>Sécurité</h2>
                  <div class="info-row">
                 <span class="label">Mot de passe</span>
                 <span class="value">••••••••••••</span>
                </div>
                <br>

                <a href="changer-mdp-eleve.php" class="security-btn">
               Changer le mot de passe
    </a>

                </div>

            </div>

        </div>

    </main>

</div>

</body>
</html>