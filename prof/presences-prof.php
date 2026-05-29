<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/db.php';

$id_prof = 1;

// Récupère les cours du prof
$stmt = $pdo->prepare("
    SELECT c.id_cours, c.matiere
    FROM cours c
    WHERE c.id_enseignant = ?
");
$stmt->execute([$id_prof]);
$cours_prof = $stmt->fetchAll();

$id_cours_selectionne = $_GET['id_cours'] ?? ($cours_prof[0]['id_cours'] ?? null);
$date_selectionnee    = $_GET['date'] ?? date('Y-m-d');

// Récupère les élèves du cours sélectionné
$eleves = [];
if ($id_cours_selectionne) {
    $stmt = $pdo->prepare("
        SELECT u.id_utilisateur, u.nom, u.prenom,
               MAX(p.statut) as statut,
               MAX(p.date_presence) as date_presence
        FROM utilisateur u
        JOIN inscription i ON u.id_utilisateur = i.id_eleve
        JOIN suivre s ON i.id_classe = s.id_classe
        LEFT JOIN presence p ON p.id_eleve = u.id_utilisateur 
            AND p.id_cours = ? 
            AND p.date_presence = ?
        WHERE s.id_cours = ?
        GROUP BY u.id_utilisateur, u.nom, u.prenom
    ");
    $stmt->execute([$id_cours_selectionne, $date_selectionnee, $id_cours_selectionne]);
    $eleves = $stmt->fetchAll();
}

// Stats
$nb_total   = count($eleves);
$nb_present = count(array_filter($eleves, fn($e) => $e['statut'] === 'present'));
$nb_absent  = count(array_filter($eleves, fn($e) => $e['statut'] === 'absent'));
$nb_retard  = count(array_filter($eleves, fn($e) => $e['statut'] === 'retard'));

// Sauvegarde présence
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_eleve = $_POST['id_eleve'];
    $id_cours = $_POST['id_cours'];
    $statut   = $_POST['statut'];
    $date     = $_POST['date'];

    $check = $pdo->prepare("SELECT id_presence FROM presence WHERE id_eleve = ? AND id_cours = ? AND date_presence = ?");
    $check->execute([$id_eleve, $id_cours, $date]);

    if ($check->fetch()) {
        $pdo->prepare("UPDATE presence SET statut = ? WHERE id_eleve = ? AND id_cours = ? AND date_presence = ?")
            ->execute([$statut, $id_eleve, $id_cours, $date]);
    } else {
        $pdo->prepare("INSERT INTO presence (date_presence, statut, id_eleve, id_cours) VALUES (?, ?, ?, ?)")
            ->execute([$date, $statut, $id_eleve, $id_cours]);
    }
    header("Location: presences-prof.php?id_cours=$id_cours&date=$date");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Présences — SmartCampus</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="layout">

  <aside class="sidebar">
    <div class="sidebar-logo">
      <img src="../images/logo-blanc.png" alt="logo">
    </div>
    <nav class="nav">
      <a href="dashboard-prof.php" class="nav-item">🏠 Tableau de bord</a>
      <a href="planning-prof.php"  class="nav-item">📅 Planning</a>
      <a href="notes-prof.php"     class="nav-item">📝 Notes</a>
      <a href="presences-prof.php" class="nav-item active">✅ Présences</a>
      <a href="profil-prof.php"    class="nav-item">👤 Profil</a>
    </nav>
    <a href="../connexion.html" class="nav-logout">🚪 Déconnexion</a>
  </aside>

  <main class="main">

    <div class="topbar">
      <div>
        <h1>✅ Gestion des Présences</h1>
        <p>Enregistrez les présences et absences de vos élèves</p>
      </div>
      <div class="topbar-right">
        <button class="btn-outline">Exporter</button>
      </div>
    </div>

    <div class="card" style="margin-bottom:20px">
      <form method="GET" action="presences-prof.php">
        <div class="filtres">
          <div class="filtre-group">
            <label>Matière</label>
            <select class="filtre-select" name="id_cours">
              <?php foreach ($cours_prof as $cours) : ?>
              <option value="<?php echo $cours['id_cours']; ?>"
                <?php echo $cours['id_cours'] == $id_cours_selectionne ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cours['matiere']); ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filtre-group">
            <label>Date</label>
            <input type="date" class="filtre-select" name="date"
                   value="<?php echo $date_selectionnee; ?>">
          </div>
          <button type="submit" class="btn-primary">Filtrer</button>
        </div>
      </form>
    </div>

    <div class="stats-grid" style="margin-bottom:20px">
      <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-value"><?php echo $nb_total; ?></div>
        <div class="stat-label">Total élèves</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">✅</div>
        <div class="stat-value" style="color:#16a34a"><?php echo $nb_present; ?></div>
        <div class="stat-label">Présents</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">❌</div>
        <div class="stat-value" style="color:#dc2626"><?php echo $nb_absent; ?></div>
        <div class="stat-label">Absents</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">⚠️</div>
        <div class="stat-value" style="color:#b45309"><?php echo $nb_retard; ?></div>
        <div class="stat-label">Retards</div>
      </div>
    </div>

    <div class="card">
      <div class="card-title">
        📋 Séance du <?php echo date('d/m/Y', strtotime($date_selectionnee)); ?>
        <span class="badge-moyenne">
          Taux : <?php echo $nb_total > 0 ? round($nb_present / $nb_total * 100) : 0; ?> %
        </span>
      </div>

      <table class="notes-table">
        <thead>
          <tr>
            <th>Élève</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($eleves)) : ?>
          <tr>
            <td colspan="3" style="text-align:center;color:#94a3b8;padding:20px">
              Aucun élève trouvé.
            </td>
          </tr>
          <?php else : ?>
          <?php foreach ($eleves as $eleve) : ?>
          <tr>
            <td class="eleve-name">
              👤 <?php echo htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']); ?>
            </td>
            <form method="POST" action="presences-prof.php">
              <input type="hidden" name="id_eleve" value="<?php echo $eleve['id_utilisateur']; ?>">
              <input type="hidden" name="id_cours" value="<?php echo $id_cours_selectionne; ?>">
              <input type="hidden" name="date"     value="<?php echo $date_selectionnee; ?>">
              <td>
                <select class="statut-select <?php echo $eleve['statut'] ?? 'present'; ?>" name="statut">
                  <option value="present" <?php echo ($eleve['statut'] ?? '') === 'present' ? 'selected' : ''; ?>>✅ Présent</option>
                  <option value="absent"  <?php echo ($eleve['statut'] ?? '') === 'absent'  ? 'selected' : ''; ?>>❌ Absent</option>
                  <option value="retard"  <?php echo ($eleve['statut'] ?? '') === 'retard'  ? 'selected' : ''; ?>>⚠️ Retard</option>
                </select>
              </td>
              <td>
                <button type="submit" class="btn-save">💾 Sauver</button>
              </td>
            </form>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </main>
</div>

<script>
document.querySelectorAll('.statut-select').forEach(select => {
  select.addEventListener('change', function() {
    this.className = 'statut-select ' + this.value;
  });
});
</script>

</body>
</html>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Présences — SmartCampus</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="layout">

  <aside class="sidebar">
    <div class="sidebar-logo">
      <img src="../images/logo-blanc.png" alt="logo">
    </div>
    <nav class="nav">
      <a href="dashboard-prof.php" class="nav-item">🏠 Tableau de bord</a>
      <a href="planning-prof.php"  class="nav-item">📅 Planning</a>
      <a href="notes-prof.php"     class="nav-item">📝 Notes</a>
      <a href="presences-prof.php" class="nav-item active">✅ Présences</a>
      <a href="profil-prof.php"    class="nav-item">👤 Profil</a>
    </nav>
    <a href="../connexion.html" class="nav-logout">🚪 Déconnexion</a>
  </aside>

  <main class="main">

    <div class="topbar">
      <div>
        <h1>✅ Gestion des Présences</h1>
        <p>Enregistrez les présences et absences de vos élèves</p>
      </div>
      <div class="topbar-right">
        <button class="btn-outline">Exporter</button>
      </div>
    </div>

    <!-- Filtres -->
    <div class="card" style="margin-bottom:20px">
      <form method="GET" action="presences-prof.php">
        <div class="filtres">
          <div class="filtre-group">
            <label>Matière</label>
            <select class="filtre-select" name="id_cours">
              <?php foreach ($cours_prof as $cours) : ?>
              <option value="<?php echo $cours['id_cours']; ?>"
                <?php echo $cours['id_cours'] == $id_cours_selectionne ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cours['matiere']); ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filtre-group">
            <label>Date</label>
            <input type="date" class="filtre-select" name="date"
                   value="<?php echo $date_selectionnee; ?>">
          </div>
          <button type="submit" class="btn-primary">Filtrer</button>
        </div>
      </form>
    </div>

    <!-- Stats -->
    <div class="stats-grid" style="margin-bottom:20px">
      <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-value"><?php echo $nb_total; ?></div>
        <div class="stat-label">Total élèves</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">✅</div>
        <div class="stat-value" style="color:#16a34a"><?php echo $nb_present; ?></div>
        <div class="stat-label">Présents</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">❌</div>
        <div class="stat-value" style="color:#dc2626"><?php echo $nb_absent; ?></div>
        <div class="stat-label">Absents</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">⚠️</div>
        <div class="stat-value" style="color:#b45309"><?php echo $nb_retard; ?></div>
        <div class="stat-label">Retards</div>
      </div>
    </div>

    <!-- Tableau -->
    <div class="card">
      <div class="card-title">
        📋 Liste des élèves — Séance du <?php echo date('d/m/Y', strtotime($date_selectionnee)); ?>
        <span class="badge-moyenne">
          Taux : <?php echo $nb_total > 0 ? round($nb_present / $nb_total * 100) : 0; ?> %
        </span>
      </div>

      <table class="notes-table">
        <thead>
          <tr>
            <th>Élève</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($eleves)) : ?>
          <tr>
            <td colspan="3" style="text-align:center;color:#94a3b8;padding:20px">
              Aucun élève trouvé.
            </td>
          </tr>
          <?php else : ?>
          <?php foreach ($eleves as $eleve) : ?>
          <tr>
            <td class="eleve-name">
              👤 <?php echo htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']); ?>
            </td>
            <form method="POST" action="presences-prof.php">
              <input type="hidden" name="id_eleve" value="<?php echo $eleve['id_utilisateur']; ?>">
              <input type="hidden" name="id_cours" value="<?php echo $id_cours_selectionne; ?>">
              <input type="hidden" name="date"     value="<?php echo $date_selectionnee; ?>">
              <td>
                <select class="statut-select <?php echo $eleve['statut'] ?? 'present'; ?>" name="statut">
                  <option value="present" <?php echo ($eleve['statut'] ?? '') === 'present' ? 'selected' : ''; ?>>✅ Présent</option>
                  <option value="absent"  <?php echo ($eleve['statut'] ?? '') === 'absent'  ? 'selected' : ''; ?>>❌ Absent</option>
                  <option value="retard"  <?php echo ($eleve['statut'] ?? '') === 'retard'  ? 'selected' : ''; ?>>⚠️ Retard</option>
                </select>
              </td>
              <td>
                <button type="submit" class="btn-save">💾 Sauver</button>
              </td>
            </form>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>

    </div>

  </main>
</div>

<script>
document.querySelectorAll('.statut-select').forEach(select => {
  select.addEventListener('change', function() {
    this.className = 'statut-select ' + this.value;
  });
});
</script>

</body>
</html>