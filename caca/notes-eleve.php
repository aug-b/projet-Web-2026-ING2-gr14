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

$sql = "
    SELECT c.matiere,
           n.type,
           n.valeur,
           n.date_note
    FROM note n
    JOIN cours c ON n.id_cours = c.id_cours
    WHERE n.id_eleve = ?
    ORDER BY c.matiere, n.date_note DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Erreur SQL : " . $conn->error);
}

$stmt->bind_param("i", $id_etudiant);
$stmt->execute();

$result = $stmt->get_result();
$notes = $result->fetch_all(MYSQLI_ASSOC);

$moyenne = 0;

if (count($notes) > 0) {
    $total = 0;

    foreach ($notes as $note) {
        $total += $note["valeur"];
    }

    $moyenne = round($total / count($notes), 2);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Notes Élève — SmartCampus</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .topbar{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:25px;
        }

        .moyenne-card{
            background:white;
            padding:18px 25px;
            border-radius:12px;
            box-shadow:0 2px 8px rgba(0,0,0,0.08);
            text-align:center;
            min-width:180px;
        }

        .moyenne-card span{
            display:block;
            color:#64748b;
            font-size:14px;
            margin-bottom:6px;
        }

        .moyenne-card strong{
            font-size:32px;
            color:#2563eb;
        }

        .notes-table{
            width:100%;
            border-collapse:collapse;
        }

        .notes-table th,
        .notes-table td{
            padding:12px;
            border-bottom:1px solid #e2e8f0;
            text-align:left;
        }

        .notes-table th{
            background:#f8fafc;
        }
    </style>
</head>

<body>

<div class="layout">

    <aside class="sidebar">
        <div class="sidebar-logo">
            <a href="dashboard-etudiant.php">
                <img src="images/logo-blanc.png" alt="logo">
            </a>
        </div>

        <nav class="nav">
            <a href="dashboard-etudiant.php" class="nav-item">🏠 Tableau de bord</a>
            <a href="planning-eleve.php" class="nav-item">📅 Planning</a>
            <a href="notes-eleve.php" class="nav-item active">📝 Notes</a>
            <a href="presences-eleve.php" class="nav-item">✅ Présences</a>
            <a href="profil-eleve.php" class="nav-item">👤 Profil</a>
        </nav>

        <a href="connexion.html" class="nav-logout">🚪 Déconnexion</a>
    </aside>

    <main class="main">
      <br>

        <div class="topbar">

            <div>
                <h1>📝 Mes Notes</h1>
                <p>Consultez vos résultats par matière</p>
            </div>

            <div class="moyenne-card">
                <span>Moyenne générale</span>
                <strong><?= $moyenne ?>/20</strong>
            </div>

        </div>
        <br><br>

        <div class="card">

            <div class="card-title">
                📋 Relevé des notes
            </div>

            <table class="notes-table">

                <thead>
                    <tr>
                        <th>Matière</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Note</th>
                    </tr>
                </thead>

                <tbody>

                <?php if(count($notes) > 0): ?>

                    <?php foreach ($notes as $n): ?>

                        <tr>
                            <td><?= htmlspecialchars($n["matiere"]) ?></td>

                            <td><?= htmlspecialchars($n["type"]) ?></td>

                            <td>
                                <?= date('d/m/Y', strtotime($n["date_note"])) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($n["valeur"]) ?>/20
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="4" style="text-align:center;">
                            Aucune note disponible.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </main>

</div>

</body>
</html>
