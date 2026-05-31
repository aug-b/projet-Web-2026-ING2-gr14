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

$date_selectionnee = $_GET['date'] ?? date('Y-m-d');

$jours_fr = [
    "Monday" => "Lundi",
    "Tuesday" => "Mardi",
    "Wednesday" => "Mercredi",
    "Thursday" => "Jeudi",
    "Friday" => "Vendredi",
    "Saturday" => "Samedi",
    "Sunday" => "Dimanche"
];

$jour_selectionne = $jours_fr[date("l", strtotime($date_selectionnee))];

/* Créneaux du prof selon la date */
$sql = "
    SELECT e.id_creneau, e.id_cours, e.id_classe,
           e.heure_debut, e.heure_fin, e.salle,
           c.matiere,
           cl.nom_classe
    FROM emploi_du_temps e
    JOIN cours c ON e.id_cours = c.id_cours
    JOIN classe cl ON e.id_classe = cl.id_classe
    WHERE c.id_enseignant = ?
    AND e.jour = ?
    ORDER BY e.heure_debut
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $id_prof, $jour_selectionne);
$stmt->execute();
$creneaux_prof = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$id_creneau_selectionne = $_GET['id_creneau'] ?? ($creneaux_prof[0]['id_creneau'] ?? null);

$creneau_selectionne = null;
$id_cours_selectionne = null;
$id_classe_selectionne = null;

foreach ($creneaux_prof as $creneau) {
    if ($creneau['id_creneau'] == $id_creneau_selectionne) {
        $creneau_selectionne = $creneau;
        $id_cours_selectionne = $creneau['id_cours'];
        $id_classe_selectionne = $creneau['id_classe'];
        break;
    }
}

/* Sauvegarde présence */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_eleve = $_POST['id_eleve'];
    $id_cours = $_POST['id_cours'];
    $id_creneau = $_POST['id_creneau'];
    $statut = $_POST['statut'];
    $date = $_POST['date'];

    $sql = "
        SELECT id_presence
        FROM presence
        WHERE id_eleve = ?
        AND id_cours = ?
        AND id_creneau = ?
        AND DATE(date_presence) = ?
    ";

    $check = $conn->prepare($sql);
    $check->bind_param("iiis", $id_eleve, $id_cours, $id_creneau, $date);
    $check->execute();
    $presence_existante = $check->get_result()->fetch_assoc();

    if ($presence_existante) {
        $sql = "
            UPDATE presence
            SET statut = ?, date_presence = ?
            WHERE id_presence = ?
        ";

        $update = $conn->prepare($sql);
        $update->bind_param("ssi", $statut, $date, $presence_existante["id_presence"]);
        $update->execute();
    } else {
        $sql = "
            INSERT INTO presence
            (date_presence, statut, id_eleve, id_cours, id_creneau)
            VALUES (?, ?, ?, ?, ?)
        ";

        $insert = $conn->prepare($sql);
        $insert->bind_param("ssiii", $date, $statut, $id_eleve, $id_cours, $id_creneau);
        $insert->execute();
    }

    header("Location: presences-prof.php?date=$date&id_creneau=$id_creneau");
    exit();
}

/* Élèves du créneau sélectionné */
$eleves = [];

if ($id_cours_selectionne && $id_classe_selectionne && $id_creneau_selectionne) {
    $sql = "
        SELECT u.id_utilisateur, u.nom, u.prenom,
               p.statut,
               p.date_presence
        FROM utilisateur u
        JOIN inscription i ON u.id_utilisateur = i.id_eleve
        LEFT JOIN presence p 
            ON p.id_eleve = u.id_utilisateur
            AND p.id_cours = ?
            AND p.id_creneau = ?
            AND DATE(p.date_presence) = ?
        WHERE i.id_classe = ?
        ORDER BY u.nom, u.prenom
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisi", $id_cours_selectionne, $id_creneau_selectionne, $date_selectionnee, $id_classe_selectionne);
    $stmt->execute();
    $eleves = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/* Stats */
$nb_total = count($eleves);
$nb_present = 0;
$nb_absent = 0;
$nb_retard = 0;

foreach ($eleves as $eleve) {
    if ($eleve['statut'] === 'present') $nb_present++;
    if ($eleve['statut'] === 'absent') $nb_absent++;
    if ($eleve['statut'] === 'retard') $nb_retard++;
}

$taux = $nb_total > 0 ? round($nb_present / $nb_total * 100) : 0;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Présences — SmartCampus</title>
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
            <a href="presences-prof.php" class="nav-item active">✅ Présences</a>
            <a href="profil-prof.php" class="nav-item">👤 Profil</a>
        </nav>

        <a href="../connexion/connexion.php" class="nav-logout">🚪 Déconnexion</a>
    </aside>

    <main class="main">

        <div class="topbar">
            <div>
                <h1>✅ Gestion des Présences</h1>
                <p>Choisissez une date et un créneau pour faire l'appel</p>
            </div>
        </div>

        <div class="card" style="margin-bottom:20px">
            <form method="GET" action="presences-prof.php">
                <div class="filtres">

                    <div class="filtre-group">
                        <label>Date</label>
                        <input
                            type="date"
                            class="filtre-select"
                            name="date"
                            value="<?= htmlspecialchars($date_selectionnee) ?>">
                    </div>

                    <div class="filtre-group">
                        <label>Cours</label>

                        <select class="filtre-select" name="id_creneau">
                            <?php if (empty($creneaux_prof)) { ?>
                                <option value="">Aucun cours ce jour</option>
                            <?php } else { ?>
                                <?php foreach ($creneaux_prof as $creneau) { ?>
                                    <option value="<?= $creneau['id_creneau'] ?>"
                                        <?= $creneau['id_creneau'] == $id_creneau_selectionne ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($creneau['matiere']) ?>
                                        -
                                        <?= htmlspecialchars($creneau['nom_classe']) ?>
                                        -
                                        <?= substr($creneau['heure_debut'], 0, 5) ?>
                                        à
                                        <?= substr($creneau['heure_fin'], 0, 5) ?>
                                        -
                                        Salle <?= htmlspecialchars($creneau['salle']) ?>
                                    </option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                    </div>

                    <button type="submit" class="btn-primary">
                        Filtrer
                    </button>

                </div>
            </form>
        </div>

        <div class="stats-grid" style="margin-bottom:20px">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?= $nb_total ?></div>
                <div class="stat-label">Total élèves</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-value" style="color:#16a34a"><?= $nb_present ?></div>
                <div class="stat-label">Présents</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">❌</div>
                <div class="stat-value" style="color:#dc2626"><?= $nb_absent ?></div>
                <div class="stat-label">Absents</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⚠️</div>
                <div class="stat-value" style="color:#b45309"><?= $nb_retard ?></div>
                <div class="stat-label">Retards</div>
            </div>
        </div>

        <div class="card">

            <div class="card-title">
                📋 Séance du <?= date('d/m/Y', strtotime($date_selectionnee)) ?>

                <?php if ($creneau_selectionne) { ?>
                    —
                    <?= htmlspecialchars($creneau_selectionne['matiere']) ?>
                    -
                    <?= htmlspecialchars($creneau_selectionne['nom_classe']) ?>
                    -
                    <?= substr($creneau_selectionne['heure_debut'], 0, 5) ?>
                <?php } ?>

                <span class="badge-moyenne">
                    Taux : <?= $taux ?> %
                </span>
            </div>

            <table class="notes-table">
                <thead>
                    <tr>
                        <th>Élève</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($eleves)) { ?>

                        <tr>
                            <td colspan="3" style="text-align:center;color:#94a3b8;padding:20px">
                                Aucun élève trouvé.
                            </td>
                        </tr>

                    <?php } else { ?>

                        <?php foreach ($eleves as $eleve) { ?>

                            <tr>
                                <form method="POST" action="presences-prof.php">

                                    <td class="eleve-name">
                                        👤 <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?>
                                    </td>

                                    <input type="hidden" name="id_eleve" value="<?= $eleve['id_utilisateur'] ?>">
                                    <input type="hidden" name="id_cours" value="<?= $id_cours_selectionne ?>">
                                    <input type="hidden" name="id_creneau" value="<?= $id_creneau_selectionne ?>">
                                    <input type="hidden" name="date" value="<?= $date_selectionnee ?>">

                                    <td>
                                        <select class="statut-select <?= $eleve['statut'] ?? 'present' ?>" name="statut">
                                            <option value="present" <?= ($eleve['statut'] ?? '') === 'present' ? 'selected' : '' ?>>
                                                ✅ Présent
                                            </option>

                                            <option value="absent" <?= ($eleve['statut'] ?? '') === 'absent' ? 'selected' : '' ?>>
                                                ❌ Absent
                                            </option>

                                            <option value="retard" <?= ($eleve['statut'] ?? '') === 'retard' ? 'selected' : '' ?>>
                                                ⚠️ Retard
                                            </option>
                                        </select>
                                    </td>

                                    <td>
                                        <button type="submit" class="btn-save">
                                            💾 Sauver
                                        </button>
                                    </td>

                                </form>
                            </tr>

                        <?php } ?>

                    <?php } ?>
                </tbody>
            </table>

        </div>

    </main>

</div>

</body>
</html>
