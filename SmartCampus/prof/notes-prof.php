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

/* Cours du prof */
$sql = "SELECT id_cours, matiere FROM cours WHERE id_enseignant = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_prof);
$stmt->execute();
$cours_prof = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$id_cours_selectionne = $_GET["id_cours"] ?? ($cours_prof[0]["id_cours"] ?? null);
$id_eleve_selectionne = $_GET["id_eleve"] ?? null;

/* Élèves du cours */
$eleves = [];
if ($id_cours_selectionne) {
    $sql = "
        SELECT DISTINCT u.id_utilisateur, u.nom, u.prenom
        FROM utilisateur u
        JOIN inscription i ON u.id_utilisateur = i.id_eleve
        JOIN suivre s ON i.id_classe = s.id_classe
        WHERE s.id_cours = ?
        ORDER BY u.nom, u.prenom
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_cours_selectionne);
    $stmt->execute();
    $eleves = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (!$id_eleve_selectionne && !empty($eleves)) {
        $id_eleve_selectionne = $eleves[0]["id_utilisateur"];
    }
}

/* Sauvegarde nouvelle note */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_cours = $_POST["id_cours"];
    $id_eleve = $_POST["id_eleve"];
    $type = $_POST["type"];
    $valeur = $_POST["valeur"];

    $sql = "
        INSERT INTO note (valeur, type, date_note, id_eleve, id_cours)
        VALUES (?, ?, NOW(), ?, ?)
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dsii", $valeur, $type, $id_eleve, $id_cours);
    $stmt->execute();

    header("Location: notes-prof.php?id_cours=$id_cours&id_eleve=$id_eleve");
    exit();
}

/* Infos élève sélectionné */
$eleve_selectionne = null;
$notes_eleve = [];

if ($id_eleve_selectionne && $id_cours_selectionne) {
    $sql = "
        SELECT u.id_utilisateur, u.nom, u.prenom, u.photo, e.niveau_scolaire
        FROM utilisateur u
        JOIN eleve e ON u.id_utilisateur = e.id_utilisateur
        WHERE u.id_utilisateur = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_eleve_selectionne);
    $stmt->execute();
    $eleve_selectionne = $stmt->get_result()->fetch_assoc();

    $sql = "
        SELECT id_note, type, valeur, date_note
        FROM note
        WHERE id_eleve = ?
        AND id_cours = ?
        ORDER BY date_note DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_eleve_selectionne, $id_cours_selectionne);
    $stmt->execute();
    $notes_eleve = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/* Moyenne */
$moyenne = "--";
if (!empty($notes_eleve)) {
    $total = 0;
    foreach ($notes_eleve as $n) {
        $total += $n["valeur"];
    }
    $moyenne = number_format($total / count($notes_eleve), 2);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Notes Professeur</title>
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
            <a href="notes-prof.php" class="nav-item active">📝 Notes</a>
            <a href="presences-prof.php" class="nav-item">✅ Présences</a>
            <a href="profil-prof.php" class="nav-item">👤 Profil</a>
        </nav>

        <a href="connexion.php" class="nav-logout">🚪 Déconnexion</a>
    </aside>

    <main class="main">

        <div class="topbar">
            <div>
                <h1>📝 Notes</h1>
                <p>Consultez et ajoutez les notes des élèves</p>
            </div>
        </div>

        <div class="card" style="margin-bottom:20px">
            <form method="GET" action="notes-prof.php">
                <div class="filtres">
                    <div class="filtre-group">
                        <label>Matière</label>
                        <select class="filtre-select" name="id_cours">
                            <?php foreach ($cours_prof as $cours) { ?>
                                <option value="<?= $cours['id_cours'] ?>"
                                    <?= $cours['id_cours'] == $id_cours_selectionne ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cours['matiere']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <button type="submit" class="btn-primary">Afficher</button>
                </div>
            </form>
        </div>

        <div class="notes-prof-layout">

            <div class="notes-left">
                <div class="liste-eleves-prof">
                    <h3>Liste des élèves</h3>

                    <?php if (empty($eleves)) { ?>

                        <p>Aucun élève</p>

                    <?php } else { ?>

                        <?php foreach ($eleves as $eleve) { ?>

                            <a class="eleve-mini <?= $eleve['id_utilisateur'] == $id_eleve_selectionne ? 'eleve-active' : '' ?>"
                               href="notes-prof.php?id_cours=<?= $id_cours_selectionne ?>&id_eleve=<?= $eleve['id_utilisateur'] ?>">
                                <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?>
                            </a>

                        <?php } ?>

                    <?php } ?>
                </div>
            </div>

            <div class="notes-center">

                <?php if ($eleve_selectionne) { ?>

                    <div class="eleve-header">

                        <div class="photo-mini">
                            <img src="<?= !empty($eleve_selectionne['photo']) ? htmlspecialchars($eleve_selectionne['photo']) : 'images/default.png' ?>" alt="photo">
                        </div>

                        <div>
                            <h3>
                                <?= htmlspecialchars($eleve_selectionne['prenom'] . ' ' . $eleve_selectionne['nom']) ?>
                            </h3>
                            <p><?= htmlspecialchars($eleve_selectionne['niveau_scolaire']) ?></p>
                        </div>

                        <div class="moyenne-box">
                            Moyenne<br>
                            <strong><?= $moyenne ?>/20</strong>
                        </div>

                    </div>

                    <form method="POST" class="form-ajout-note">
                        <input type="hidden" name="id_cours" value="<?= $id_cours_selectionne ?>">
                        <input type="hidden" name="id_eleve" value="<?= $id_eleve_selectionne ?>">

                        <input type="text" name="type" placeholder="Évaluation ex: CC1" required>

                        <input type="number" name="valeur" min="0" max="20" step="0.5" placeholder="Note /20" required>

                        <button type="submit" class="btn-primary">Ajouter la note</button>
                    </form>

                    <table class="notes-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Évaluation</th>
                                <th>Note</th>
                                <th>/20</th>
                                <th>Coef.</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($notes_eleve)) { ?>

                                <tr>
                                    <td colspan="5">Aucune note pour cet élève.</td>
                                </tr>

                            <?php } else { ?>

                                <?php foreach ($notes_eleve as $note) { ?>

                                    <tr>
                                        <td><?= date("d/m/Y", strtotime($note["date_note"])) ?></td>
                                        <td><?= htmlspecialchars($note["type"]) ?></td>
                                        <td><?= htmlspecialchars($note["valeur"]) ?></td>
                                        <td>/20</td>
                                        <td>1</td>
                                    </tr>

                                <?php } ?>

                            <?php } ?>
                        </tbody>
                    </table>

                <?php } else { ?>

                    <p>Sélectionnez un élève.</p>

                <?php } ?>

            </div>

        </div>

    </main>

</div>

</body>
</html>
