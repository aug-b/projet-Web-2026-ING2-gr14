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
    SELECT c.matiere, e.jour, e.heure_debut, e.heure_fin, e.salle,
           cl.nom_classe
    FROM emploi_du_temps e
    JOIN cours c 
        ON e.id_cours = c.id_cours
    JOIN classe cl
        ON e.id_classe = cl.id_classe
    WHERE c.id_enseignant = ?
    ORDER BY 
        FIELD(e.jour, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'),
        e.heure_debut ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_prof);
$stmt->execute();

$result = $stmt->get_result();
$creneaux = $result->fetch_all(MYSQLI_ASSOC);

$planning = [];

foreach ($creneaux as $creneau) {
    $planning[$creneau['jour']][$creneau['heure_debut']] = $creneau;
}

$jours  = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
$heures = ['08:00:00', '09:00:00', '10:00:00', '12:00:00', '14:00:00', '16:00:00'];
$heures_affichage = ['08h00', '09h00', '10h00', '12h00', '14h00', '16h00'];

$offset = isset($_GET['semaine']) ? (int)$_GET['semaine'] : 0;

$lundi = new DateTime('monday this week');
$lundi->modify("$offset weeks");

$vendredi = clone $lundi;
$vendredi->modify('+4 days');
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Planning Professeur — SmartCampus</title>
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
            <a href="planning-prof.php" class="nav-item active">📅 Planning</a>
            <a href="notes-prof.php" class="nav-item">📝 Notes</a>
            <a href="presences-prof.php" class="nav-item">✅ Présences</a>
            <a href="profil-prof.php" class="nav-item">👤 Profil</a>
        </nav>

        <a href="../connexion/connexion.php" class="nav-logout">🚪 Déconnexion</a>

    </aside>

    <main class="main">

        <div class="topbar">
            <div>
                <h1>📅 Mon Planning</h1>
                <p>Consultez votre emploi du temps de la semaine</p>
            </div>

            <div class="topbar-right">
                <a href="?semaine=<?= $offset - 1 ?>">
                    <button class="btn-outline">◀ Semaine précédente</button>
                </a>

                <a href="?semaine=<?= $offset + 1 ?>">
                    <button class="btn-primary">Semaine suivante ▶</button>
                </a>
            </div>
        </div>

        <div class="card">

            <div class="card-title">
                Semaine du <?= $lundi->format('d/m/Y') ?>
                au <?= $vendredi->format('d/m/Y') ?>
            </div>

            <div class="planning-grid">

                <div class="planning-header"></div>

                <?php foreach ($jours as $jour) { ?>
                    <div class="planning-header"><?= $jour ?></div>
                <?php } ?>

                <?php foreach ($heures as $i => $heure) { ?>

                    <div class="planning-heure">
                        <?= $heures_affichage[$i] ?>
                    </div>

                    <?php foreach ($jours as $jour) { ?>

                        <?php if (isset($planning[$jour][$heure])) { ?>

                            <?php $c = $planning[$jour][$heure]; ?>

                            <div class="planning-cell cours-cell">
                                <div class="cours-planning">

                                    <div class="cours-planning-name">
                                        <?= htmlspecialchars($c['matiere']) ?>
                                    </div>

                                    <div class="cours-planning-info">
                                        <?= substr($c['heure_debut'], 0, 5) ?>
                                        —
                                        <?= substr($c['heure_fin'], 0, 5) ?>
                                        | Salle <?= htmlspecialchars($c['salle']) ?>

                                        <br>

                                        Classe :
                                        <?= htmlspecialchars($c['nom_classe']) ?>
                                    </div>

                                </div>
                            </div>

                        <?php } else { ?>

                            <div class="planning-cell"></div>

                        <?php } ?>

                    <?php } ?>

                <?php } ?>

            </div>

        </div>

    </main>

</div>

</body>
</html>
