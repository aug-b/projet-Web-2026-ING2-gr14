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
    SELECT p.date_presence, p.statut, c.matiere
    FROM presence p
    JOIN cours c ON p.id_cours = c.id_cours
    WHERE p.id_eleve = ?
    ORDER BY p.date_presence DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_etudiant);
$stmt->execute();

$result = $stmt->get_result();
$presences = $result->fetch_all(MYSQLI_ASSOC);

$total = count($presences);
$absences = count(array_filter($presences, fn($p) => $p['statut'] === 'absent'));
$retards = count(array_filter($presences, fn($p) => $p['statut'] === 'retard'));
$presents = count(array_filter($presences, fn($p) => $p['statut'] === 'present'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Présences Élève — SmartCampus</title>
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
      <a href="presences-eleve.php" class="nav-item active">✅ Présences</a>
      <a href="profil-eleve.php" class="nav-item">👤 Profil</a>
    </nav>
    <a href="../connexion.html" class="nav-logout">🚪 Déconnexion</a>
  </aside>

  <main class="main">
    <div class="topbar">
      <div>
        <h1>✅ Mes Présences</h1>
        <p>Consultez vos présences, absences et retards</p>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">📋</div>
        <div class="stat-value"><?php echo $total; ?></div>
        <div class="stat-label">Total séances</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">✅</div>
        <div class="stat-value"><?php echo $presents; ?></div>
        <div class="stat-label">Présences</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">❌</div>
        <div class="stat-value"><?php echo $absences; ?></div>
        <div class="stat-label">Absences</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">⚠️</div>
        <div class="stat-value"><?php echo $retards; ?></div>
        <div class="stat-label">Retards</div>
      </div>
    </div>

    <div class="card">
      <div class="card-title">📋 Historique</div>

      <table class="notes-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Matière</th>
            <th>Statut</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($presences as $p) : ?>
          <tr>
            <td><?php echo date('d/m/Y', strtotime($p['date_presence'])); ?></td>
            <td><?php echo htmlspecialchars($p['matiere']); ?></td>
            <td>
              <?php
              if ($p['statut'] === 'present') echo '✅ Présent';
              elseif ($p['statut'] === 'absent') echo '❌ Absent';
              else echo '⚠️ Retard';
              ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
</body>
</html>