<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/db.php';

$id = 1; // id du prof de test

// Infos du prof
$stmt = $pdo->prepare("
    SELECT u.nom, u.prenom 
    FROM utilisateur u 
    WHERE u.id_utilisateur = ?
");
$stmt->execute([$id]);
$prof = $stmt->fetch();

// Nombre d'étudiants dans ses cours
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT i.id_eleve) as nb
    FROM inscription i
    JOIN suivre s ON i.id_classe = s.id_classe
    JOIN cours c ON s.id_cours = c.id_cours
    WHERE c.id_enseignant = ?
");
$stmt->execute([$id]);
$nb_eleves = $stmt->fetch()['nb'];

// Nombre de cours
$stmt = $pdo->prepare("
    SELECT COUNT(*) as nb 
    FROM cours 
    WHERE id_enseignant = ?
");
$stmt->execute([$id]);
$nb_cours = $stmt->fetch()['nb'];

// Notes à saisir (élèves sans note)
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT i.id_eleve) as nb
    FROM inscription i
    JOIN suivre s ON i.id_classe = s.id_classe
    JOIN cours c ON s.id_cours = c.id_cours
    LEFT JOIN note n ON n.id_eleve = i.id_eleve AND n.id_cours = c.id_cours
    WHERE c.id_enseignant = ? AND n.id_note IS NULL
");
$stmt->execute([$id]);
$nb_notes = $stmt->fetch()['nb'];

// Prochains cours (emploi du temps)
$stmt = $pdo->prepare("
    SELECT c.matiere, e.jour, e.heure_debut, e.salle
    FROM emploi_du_temps e
    JOIN cours c ON e.id_cours = c.id_cours
    WHERE c.id_enseignant = ?
    ORDER BY e.jour, e.heure_debut
    LIMIT 3
");
$stmt->execute([$id]);
$prochains_cours = $stmt->fetchAll();

// Cours pour notes à saisir
$stmt = $pdo->prepare("
    SELECT c.id_cours, c.matiere
    FROM cours c
    WHERE c.id_enseignant = ?
    LIMIT 3
");
$stmt->execute([$id]);
$cours_notes = $stmt->fetchAll();

// Notifications
$stmt = $pdo->prepare("
    SELECT contenu, date_notification
    FROM notification
    WHERE id_utilisateur = ?
    ORDER BY date_notification DESC
    LIMIT 3
");
$stmt->execute([$id]);
$notifications = $stmt->fetchAll();
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
      <img src="../images/logo-blanc.png" alt="logo">
    </div>
    <nav class="nav">
      <a href="dashboard-prof.php"  class="nav-item active">🏠 Tableau de bord</a>
      <a href="planning-prof.php"   class="nav-item">📅 Planning</a>
      <a href="notes-prof.php"      class="nav-item">📝 Notes</a>
      <a href="presences-prof.php"  class="nav-item">✅ Présences</a>
      <a href="profil-prof.php"     class="nav-item">👤 Profil</a>
    </nav>
    <a href="../connexion.html" class="nav-logout">🚪 Déconnexion</a>
  </aside>

  <main class="main">

    <div class="topbar">
      <div>
        <h1>Tableau de Bord</h1>
        <p>Bienvenue, <?php echo htmlspecialchars($prof['prenom'] . ' ' . $prof['nom']); ?></p>
      </div>
      <div class="topbar-right">
        <button class="btn-outline">Mon compte</button>
        <button class="btn-primary">Profil</button>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">👨‍🎓</div>
        <div class="stat-value"><?php echo $nb_eleves; ?></div>
        <div class="stat-label">Étudiants</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">📚</div>
        <div class="stat-value"><?php echo $nb_cours; ?></div>
        <div class="stat-label">Cours</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">📝</div>
        <div class="stat-value"><?php echo $nb_notes; ?></div>
        <div class="stat-label">Notes à saisir</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">✅</div>
        <div class="stat-value">--</div>
        <div class="stat-label">Présences</div>
      </div>
    </div>

    <div class="content-grid">

      <!-- Prochains cours -->
      <div class="card">
        <div class="card-title">📅 Prochains cours</div>
        <?php if (empty($prochains_cours)) : ?>
          <p style="color:#94a3b8;font-size:13px">Aucun cours programmé.</p>
        <?php else : ?>
          <?php foreach ($prochains_cours as $cours) : ?>
          <div class="cours-item">
            <div class="cours-dot"></div>
            <div>
              <div class="cours-name"><?php echo htmlspecialchars($cours['matiere']); ?></div>
              <div class="cours-info">
                <?php echo htmlspecialchars($cours['jour']); ?> 
                <?php echo substr($cours['heure_debut'], 0, 5); ?> — 
                Salle <?php echo htmlspecialchars($cours['salle']); ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Notes à saisir -->
      <div class="card">
        <div class="card-title">📝 Notes à saisir</div>
        <?php if (empty($cours_notes)) : ?>
          <p style="color:#94a3b8;font-size:13px">Aucun cours.</p>
        <?php else : ?>
          <?php foreach ($cours_notes as $cours) : ?>
          <div class="notes-item">
            <span><?php echo htmlspecialchars($cours['matiere']); ?></span>
            <a href="notes-prof.php?id_cours=<?php echo $cours['id_cours']; ?>">
              <button class="btn-primary small">Saisir</button>
            </a>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
        <button class="btn-full">Faire l'appel</button>
      </div>

      <!-- Notifications -->
      <div class="card">
        <div class="card-title">🔔 Notifications</div>
        <?php if (empty($notifications)) : ?>
          <p style="color:#94a3b8;font-size:13px">Aucune notification.</p>
        <?php else : ?>
          <?php foreach ($notifications as $i => $notif) : ?>
          <?php $dots = ['green', 'blue', 'orange']; ?>
          <div class="notif-item">
            <span class="notif-dot <?php echo $dots[$i % 3]; ?>"></span>
            <span><?php echo htmlspecialchars($notif['contenu']); ?></span>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

    </div>
  </main>
</div>

</body>
</html>