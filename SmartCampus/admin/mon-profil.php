<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once("smartcampus/admin/connexion.php");

if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: smartcampus/connexion/connexion.html");
    exit();
}

$id_admin = $_SESSION["id_utilisateur"];

$sql = "
    SELECT nom, prenom, email, telephone, date_de_naissance, photo, role
    FROM utilisateur
    WHERE id_utilisateur = ? AND role = 'admin'
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_admin);
$stmt->execute();

$admin = $stmt->get_result()->fetch_assoc();

if (!$admin) {
    die("Administrateur introuvable.");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Profil Administrateur — SmartCampus</title>
    <link rel="stylesheet" href="smartcampus/style.css">
</head>

<body>

<div class="layout">

    <aside class="sidebar">
        <div class="sidebar-logo">
            <a href="dashboard-admin.php">
                <img src="smartcampus/images/logo-blanc.png" alt="logo">
            </a>
        </div>

        <nav class="nav">
     <a href="smartcampus/admin/dashboard-admin.php" class="nav-item">🏠 Tableau de bord</a>
      <a href="smartcampus/admin/planning-admin.php" class="nav-item ">📅 Gestion emploi du temps</a>
      <a href="smartcampus/admin/utilisateurs.php" class="nav-item">👥 Gestion des utilisateurs</a>
      <a href="smartcampus/admin/enseignants.php" class="nav-item">🎓 Gestion des enseignants</a>
      <a href="smartcampus/admin/eleves.php" class="nav-item">👤 Gestion des élèves</a>
      <a href="smartcampus/admin/inscriptions.php" class="nav-item">📋 Gestion des inscriptions</a>
      <a href="smartcampus/admin/mon-profil.php" class="nav-item active">👤 Mon Profil</a>
        </nav>

        <a href="smartcampus/connexion/connexion.php" class="nav-logout">🚪 Déconnexion</a>
    </aside>

    <main class="main">

        <h1 class="page-title">Mon profil</h1>

        <div class="profile-layout">

            <div class="profile-card">

                <form action="smartcampus/admin/upload-photo-admin.php" method="POST" enctype="multipart/form-data">

                    <div class="profile-image">
                        <img
                            src="<?= !empty($admin['photo']) ? htmlspecialchars($admin['photo']) : 'images/default.png' ?>"
                            alt="photo profil">
                    </div>

                    <h2 class="profile-name">
                        <?= htmlspecialchars($admin["prenom"] . " " . $admin["nom"]) ?>
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
                        <span class="value"><?= htmlspecialchars($admin["email"] ?? "Non renseigné") ?></span>
                    </div>

                    <div class="info-row">
                        <span class="label">Téléphone</span>
                        <span class="value"><?= htmlspecialchars($admin["telephone"] ?? "Non renseigné") ?></span>
                    </div>

                    <div class="info-row">
                        <span class="label">Date de naissance</span>
                        <span class="value">
                            <?= !empty($admin["date_de_naissance"])
                                ? date("d/m/Y", strtotime($admin["date_de_naissance"]))
                                : "Non renseignée" ?>
                        </span>
                    </div>

                    <div class="info-row">
                        <span class="label">Rôle</span>
                        <span class="value"><?= htmlspecialchars($admin["role"]) ?></span>
                    </div>

                </div>

                <div class="info-card">

                    <h2>Sécurité</h2>

                    <div class="info-row">
                        <span class="label">Mot de passe</span>
                        <span class="value">••••••••••••</span>
                    </div>

                    <br>

                    <a href="smartcampus/admin/changer-mdp-admin.html" class="security-btn">
                        Changer le mot de passe
                    </a>

                </div>

            </div>

        </div>

    </main>

</div>

</body>
</html>