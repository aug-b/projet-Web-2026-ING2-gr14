<?php

session_start();
require_once("connexion.php");

if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: connexion.html");
    exit();
}

$id_prof = $_SESSION["id_utilisateur"];

/* PROFIL */
$profil = $conn->query("
    SELECT nom, prenom, photo, role
    FROM utilisateur
    WHERE id_utilisateur = $id_prof
")->fetch_assoc();

/* 4 PROCHAINS COURS */
$prochains_cours = $conn->query("
    SELECT c.matiere, e.jour, e.heure_debut, e.salle
    FROM emploi_du_temps e
    JOIN cours c ON e.id_cours = c.id_cours
    WHERE c.id_enseignant = $id_prof
    ORDER BY
        FIELD(e.jour,'Lundi','Mardi','Mercredi','Jeudi','Vendredi'),
        e.heure_debut
    LIMIT 4
");

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Professeur</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="layout">

    <aside class="sidebar">

        <div class="sidebar-logo">
            <a href="dashboard-prof.php">
                <img src="images/logo-blanc.png" alt="logo">
            </a>
        </div>

        <nav class="nav">
            <a href="dashboard-prof.php" class="nav-item active">
                🏠 Tableau de bord
            </a>

            <a href="planning-prof.php" class="nav-item">
                📅 Planning
            </a>

            <a href="notes-prof.php" class="nav-item">
                📝 Notes
            </a>

            <a href="presences-prof.php" class="nav-item">
                ✅ Présences
            </a>

            <a href="profil-prof.php" class="nav-item">
                👤 Profil
            </a>
        </nav>

        <a href="deconnexion.php" class="nav-logout">
            🚪 Déconnexion
        </a>

    </aside>

    <main class="main">

        <div class="titre-page">
            <h1>Tableau de bord</h1>
            <p>Bienvenue sur votre espace professeur</p>
        </div>

        <div class="carte-profil">

            <div class="photo-profil">
                <img
                    src="<?= !empty($profil['photo']) ? htmlspecialchars($profil['photo']) : 'images/default.png' ?>"
                    alt="photo profil">
            </div>

            <div class="infos-profil">
                <h2>
                    <?= htmlspecialchars($profil['prenom']) ?>
                    <?= htmlspecialchars($profil['nom']) ?>
                </h2>

                <span>Professeur</span>
            </div>

        </div>

        <br><br>

        <div class="carte-info-cours">

            <div class="info">

                <h2>Mes  prochains cours</h2>

                <?php if ($prochains_cours->num_rows > 0) { ?>

                    <?php while ($cours = $prochains_cours->fetch_assoc()) { ?>

                        <div class="sous-carte">

                            <strong>
                                <?= htmlspecialchars($cours['matiere']) ?>
                            </strong>

                            <p>
                                <?= $cours['jour'] ?>
                                à
                                <?= substr($cours['heure_debut'], 0, 5) ?>
                                - Salle
                                <?= htmlspecialchars($cours['salle']) ?>
                            </p>

                        </div>

                    <?php } ?>

                <?php } else { ?>

                    <p class="message_pas_cours">
                        Aucun cours prévu pour le moment.
                    </p>

                <?php } ?>

            </div>

            <div class="info">

                <h2>Actions rapides</h2>

                <a href="notes-prof.php" class="btn-action-prof">
                    📝 Saisir les notes
                </a>

                <a href="presences-prof.php" class="btn-action-prof">
                    ✅ Faire l'appel
                </a>

            </div>

        </div>

    </main>

</div>

</body>

</html>
