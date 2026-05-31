<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once("../connexion/connexion.php");

if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: ../connexion/connexion.html");
    exit();
}

$id_prof = $_SESSION["id_utilisateur"];

$sql = "
    SELECT u.nom, u.prenom, u.email, u.telephone,
           u.date_de_naissance, u.photo, ens.specialite
    FROM utilisateur u
    LEFT JOIN enseignant ens ON u.id_utilisateur = ens.id_utilisateur
    WHERE u.id_utilisateur = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_prof);
$stmt->execute();

$prof = $stmt->get_result()->fetch_assoc();

if (!$prof) {
    die("Professeur introuvable.");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Profil Professeur — SmartCampus</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body>

<div class="layout">

  <aside class="sidebar">
        <div class="sidebar-logo">
            <a href="dashboard-prof.php">
                <img src="../images/logo-blanc.png" alt="logo">
            </a>
        </div>

        <nav class="nav">
            <a href="dashboard-prof.php" class="nav-item">🏠 Tableau de bord</a>
            <a href="planning-prof.php" class="nav-item">📅 Planning</a>
            <a href="notes-prof.php" class="nav-item">📝 Notes</a>
            <a href="presences-prof.php" class="nav-item">✅ Présences</a>
            <a href="profil-prof.php" class="nav-item active">👤 Profil</a>
        </nav>

        <a href="../connexion/connexion.html" class="nav-logout">🚪 Déconnexion</a>
    </aside>

    <main class="main">

        <h1 class="page-title">Mon profil</h1>

        <div class="profile-layout">

            <div class="profile-card">

                <form action="upload-photo-prof.php" method="POST" enctype="multipart/form-data">

                    <div class="profile-image">
                        <img
                            src="<?= !empty($prof['photo']) ? htmlspecialchars($prof['photo']) : '../images/default.png' ?>"
                            alt="photo profil">
                    </div>

                    <h2 class="profile-name">
                        <?= htmlspecialchars($prof["prenom"] . " " . $prof["nom"]) ?>
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
                        <span class="value"><?= htmlspecialchars($prof["email"] ?? "Non renseigné") ?></span>
                    </div>

                    <div class="info-row">
                        <span class="label">Téléphone</span>
                        <span class="value"><?= htmlspecialchars($prof["telephone"] ?? "Non renseigné") ?></span>
                    </div>

                    <div class="info-row">
                        <span class="label">Date de naissance</span>
                        <span class="value">
                            <?= !empty($prof["date_de_naissance"])
                                ? date("d/m/Y", strtotime($prof["date_de_naissance"]))
                                : "Non renseignée" ?>
                        </span>
                    </div>

                    <div class="info-row">
                        <span class="label">Spécialité</span>
                        <span class="value"><?= htmlspecialchars($prof["specialite"] ?? "Non renseignée") ?></span>
                    </div>

                </div>

                <div class="info-card">

                    <h2>Sécurité</h2>

                    <div class="info-row">
                        <span class="label">Mot de passe</span>
                        <span class="value">••••••••••••</span>
                    </div>

                    <br>

                    <a href="changer-mdp-prof.html" class="security-btn">
                        Changer le mot de passe
                    </a>

                </div>

            </div>

        </div>

    </main>

</div>

</body>
</html>
