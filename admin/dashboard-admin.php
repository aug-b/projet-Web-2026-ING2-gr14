<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/db.php';

$id_admin = 2; // id de test

// Infos admin
$stmt = $pdo->prepare("SELECT nom, prenom FROM utilisateur WHERE id_utilisateur = ?");
$stmt->execute([$id_admin]);
$admin = $stmt->fetch();

// Nombre d'élèves
$nb_eleves = $pdo->query("SELECT COUNT(*) FROM eleve")->fetchColumn();

// Nombre de classes
$nb_classes = $pdo->query("SELECT COUNT(*) FROM classe")->fetchColumn();

// Nombre de profs
$nb_profs = $pdo->query("SELECT COUNT(*) FROM enseignant")->fetchColumn();

// Inscriptions en attente
$nb_attente = $pdo->query("SELECT COUNT(*) FROM utilisateur_en_attente WHERE statut = 'en_attente'")->fetchColumn();

// Derniers enseignants ajoutés
$stmt = $pdo->query("
    SELECT u.nom, u.prenom, e.specialite
    FROM utilisateur u
    JOIN enseignant e ON u.id_utilisateur = e.id_utilisateur
    ORDER BY u.id_utilisateur DESC
    LIMIT 3
");
$derniers_profs = $stmt->fetchAll();

// Derniers élèves ajoutés
$stmt = $pdo->query("
    SELECT u.nom, u.prenom, el.niveau_scolaire
    FROM utilisateur u
    JOIN eleve el ON u.id_utilisateur = el.id_utilisateur
    ORDER BY u.id_utilisateur DESC
    LIMIT 3
");
$derniers_eleves = $stmt->fetchAll();

// Notifications
$stmt = $pdo->prepare("
    SELECT contenu, date_notification
    FROM notification
    WHERE id_utilisateur = ?
    ORDER BY date_notification DESC
    LIMIT 3
");
$stmt->execute([$id_admin]);
$notifications = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Admin — SmartCampus</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="layout">

  <aside class="sidebar">
    <div class="sidebar-logo">
      <img src="../images/logo-blanc.png" alt="logo">
    </div>
    <nav class="nav">
      <a href="dashboard-admin.php" class="nav-item active">🏠 Tableau de bord</a>
      <a href="emploi-du-temps.php" class="nav-item">📅 Gestion emploi du temps</a>
      <a href="utilisateurs.php"    class="nav-item">👥 Gestion des utilisateurs</a>
      <a href="enseignants.php"     class="nav-item">🎓 Gestion des enseignants</a>
      <a href="eleves.php"          class="nav-item">👤 Gestion des élèves</a>
      <a href="inscriptions.php"    class="nav-item">📋 Gestion des inscriptions</a>
      <a href="mon-profil.php"      class="nav-item">👤 Mon Profil</a>
    </nav>
    <a href="../connexion/connexion.html" class="nav-logout">🚪 Déconnexion</a>
  </aside>

  <main class="main">

    <div class="topbar">
      <div>
        <h1>Tableau de Bord</h1>
        <p>Bienvenue, <?php echo htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']); ?></p>
      </div>
      <div class="topbar-right">
        <button class="btn-outline">Mon compte</button>
        <button class="btn-primary">Profil Admin</button>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">👨‍🎓</div>
        <div class="stat-value"><?php echo $nb_eleves; ?></div>
        <div class="stat-label">Nombre d'élèves</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🎓</div>
        <div class="stat-value"><?php echo $nb_classes; ?></div>
        <div class="stat-label">Nombre de classes</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">👨‍🏫</div>
        <div class="stat-value"><?php echo $nb_profs; ?></div>
        <div class="stat-label">Nombre de profs</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">📋</div>
        <div class="stat-value"><?php echo $nb_attente; ?></div>
        <div class="stat-label">Inscriptions en attente</div>
      </div>
    </div>

    <div class="content-grid">

      <!-- Derniers enseignants -->
      <div class="card">
        <div class="card-title">👨‍🏫 Derniers enseignants ajoutés</div>
        <?php if (empty($derniers_profs)) : ?>
          <p style="color:#94a3b8;font-size:13px">Aucun enseignant.</p>
        <?php else : ?>
          <?php foreach ($derniers_profs as $prof) : ?>
          <div class="cours-item">
            <div class="cours-dot"></div>
            <div>
              <div class="cours-name">
                <?php echo htmlspecialchars($prof['prenom'] . ' ' . $prof['nom']); ?>
              </div>
              <div class="cours-info">
                <?php echo htmlspecialchars($prof['specialite'] ?? '--'); ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
        <a href="enseignants.php">
          <button class="btn-full">Voir tous les enseignants</button>
        </a>
      </div>

      <!-- Derniers élèves -->
      <div class="card">
        <div class="card-title">👤 Derniers élèves ajoutés</div>
        <?php if (empty($derniers_eleves)) : ?>
          <p style="color:#94a3b8;font-size:13px">Aucun élève.</p>
        <?php else : ?>
          <?php foreach ($derniers_eleves as $eleve) : ?>
          <div class="cours-item">
            <div class="cours-dot"></div>
            <div>
              <div class="cours-name">
                <?php echo htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']); ?>
              </div>
              <div class="cours-info">
                <?php echo htmlspecialchars($eleve['niveau_scolaire'] ?? '--'); ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
        <a href="eleves.php">
          <button class="btn-full">Voir tous les élèves</button>
        </a>
      </div>

      <!-- Notifications -->
      <div class="card">
        <div class="card-title">🔔 Notifications</div>
        <?php if (empty($notifications)) : ?>
          <p style="color:#94a3b8;font-size:13px">Aucune notification.</p>
        <?php else : ?>
          <?php $dots = ['green', 'blue', 'orange']; ?>
          <?php foreach ($notifications as $i => $notif) : ?>
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