<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/db.php';

$id_prof = 1; // id de test

// Récupère l'emploi du temps du prof
$stmt = $pdo->prepare("
    SELECT e.jour, e.heure_debut, e.heure_fin, e.salle, c.matiere
    FROM emploi_du_temps e
    JOIN cours c ON e.id_cours = c.id_cours
    WHERE c.id_enseignant = ?
    ORDER BY e.jour, e.heure_debut
");
$stmt->execute([$id_prof]);
$creneaux = $stmt->fetchAll();

// Organise par jour et heure
$planning = [];
foreach ($creneaux as $creneau) {
    $planning[$creneau['jour']][$creneau['heure_debut']] = $creneau;
}

$jours  = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
$heures = ['08:00:00', '10:00:00', '12:00:00', '14:00:00', '16:00:00'];
$heures_affichage = ['08h00', '10h00', '12h00', '14h00', '16h00'];

// Calcul dates semaine
$offset = isset($_GET['semaine']) ? (int)$_GET['semaine'] : 0;
$lundi  = new DateTime('monday this week');
$lundi->modify("$offset weeks");
$vendredi = clone $lundi;
$vendredi->modify('+4 days');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Planning — SmartCampus</title>
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
      <a href="planning-prof.php"  class="nav-item active">📅 Planning</a>
      <a href="notes-prof.php"     class="nav-item">📝 Notes</a>
      <a href="presences-prof.php" class="nav-item">✅ Présences</a>
      <a href="profil-prof.php"    class="nav-item">👤 Profil</a>
    </nav>
    <a href="../connexion.html" class="nav-logout">🚪 Déconnexion</a>
  </aside>

  <main class="main">

    <div class="topbar">
      <div>
        <h1>📅 Mon Planning</h1>
        <p>Consultez votre emploi du temps de la semaine</p>
      </div>
      <div class="topbar-right">
        <a href="?semaine=<?php echo $offset - 1; ?>">
          <button class="btn-outline">◀ Semaine précédente</button>
        </a>
        <a href="?semaine=<?php echo $offset + 1; ?>">
          <button class="btn-primary">Semaine suivante ▶</button>
        </a>
      </div>
    </div>

    <div class="card">
      <div class="card-title">
        Semaine du <?php echo $lundi->format('d/m/Y'); ?> 
        au <?php echo $vendredi->format('d/m/Y'); ?>
      </div>

      <div class="planning-grid">
        <div class="planning-header"></div>
        <?php foreach ($jours as $jour) : ?>
          <div class="planning-header"><?php echo $jour; ?></div>
        <?php endforeach; ?>

        <?php foreach ($heures as $i => $heure) : ?>
          <div class="planning-heure"><?php echo $heures_affichage[$i]; ?></div>
          <?php foreach ($jours as $jour) : ?>
            <?php if (isset($planning[$jour][$heure])) : ?>
              <?php $c = $planning[$jour][$heure]; ?>
              <div class="planning-cell cours-cell">
                <div class="cours-planning">
                  <div class="cours-planning-name">
                    <?php echo htmlspecialchars($c['matiere']); ?>
                  </div>
                  <div class="cours-planning-info">
                    <?php echo substr($c['heure_debut'], 0, 5); ?> — 
                    <?php echo substr($c['heure_fin'], 0, 5); ?>
                    | Salle <?php echo htmlspecialchars($c['salle']); ?>
                  </div>
                </div>
              </div>
            <?php else : ?>
              <div class="planning-cell"></div>
            <?php endif; ?>
          <?php endforeach; ?>
        <?php endforeach; ?>

      </div>
    </div>

  </main>
</div>
</body>
</html>