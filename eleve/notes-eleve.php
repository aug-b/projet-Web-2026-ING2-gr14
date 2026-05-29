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
           n.valeur
    FROM note n
    JOIN cours c ON n.id_cours = c.id_cours
    WHERE n.id_eleve = ?
    ORDER BY c.matiere, n.id_note
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Erreur SQL : " . $conn->error);
}

$stmt->bind_param("i", $id_etudiant);
$stmt->execute();

$result = $stmt->get_result();
$notes = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Notes Élève — SmartCampus</title>
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
      <a href="notes-eleve.php" class="nav-item active">📝 Notes</a>
      <a href="presences-eleve.php" class="nav-item">✅ Présences</a>
      <a href="profil-eleve.php" class="nav-item">👤 Profil</a>
    </nav>
    <a href="../connexion.html" class="nav-logout">🚪 Déconnexion</a>
  </aside>

  <main class="main">
    <div class="topbar">
      <div>
        <h1>📝 Mes Notes</h1>
        <p>Consultez vos résultats par matière</p>
      </div>
    </div>

    <div class="card">
      <div class="card-title">📋 Relevé des notes</div>

      <table class="notes-table">
 <thead>
<tr>
    <th>Matière</th>
    <th>Type</th>
    <th>Note</th>
</tr>
</thead>

<tbody>
<?php foreach ($notes as $n) : ?>
<tr>
    <td><?php echo htmlspecialchars($n['matiere']); ?></td>
    <td><?php echo htmlspecialchars($n['type']); ?></td>
    <td><?php echo htmlspecialchars($n['valeur']); ?>/20</td>
</tr>
<?php endforeach; ?>
</tbody>
      </table>
    </div>
  </main>
</div>
</body>
</html>