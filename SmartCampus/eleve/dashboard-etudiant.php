<?php include("dashboard.php"); ?>

<!DOCTYPE HTML>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <title>Dashboard étudiant</title>
    <link rel="stylesheet" href="../style.css">
    
</head>

<body>
<div class="layout">

  <aside class="sidebar">
    <div class="sidebar-logo">
     <a href="dashboard-etudiant.php"><img src="../images/logo-blanc.png" alt="logo"></a>
    </div>
    <nav class="nav">
<a href="dashboard-etudiant.php" class="nav-item active">🏠 Tableau de bord</a>
<a href="planning-eleve.php" class="nav-item">📅 Planning</a>
      <a href="notes-eleve.php" class="nav-item">📝 Notes</a>
      <a href="presences-eleve.php" class="nav-item">✅ Présences</a>
      <a href="profil-eleve.php" class="nav-item">👤 Profil</a>
    </nav>
    <a href="../connexion/deconnexion.php" class="nav-logout">🚪 Déconnexion</a>
  </aside>
 
<main class="main">

    <div class="titre-page">
        <h1>Tableau de bord</h1>
        <p>Bienvenue sur votre espace étudiant</p>
    </div>

    <div class="carte-profil">
        <div class="photo-profil">
            <img src="<?= !empty($profil['photo']) ? htmlspecialchars($profil['photo']) : '../images/default.png' ?>" alt="photo profil">
        </div>

        <div class="infos-profil">
            <h2><?= $profil['prenom'] ?> <?= $profil['nom'] ?></h2>
            <span><?= $profil['role'] ?? 'Étudiant' ?></span>
        </div>
    </div>

    <div class="cartes-dashboard">

        <div class="carte">
            <h2>Moyenne générale</h2>
            <p><?= $moyenne['moyenne'] ?? 0 ?>/20</p>
        </div>

        <div class="carte">
            <h2>Absences</h2>
            <p><?= $absences['total'] ?? 0 ?></p>
        </div>

        <div class="carte">
            <h2>Retards</h2>
            <p><?= $retards['total'] ?? 0 ?></p>
        </div>

    </div>
<br><br>

    <div class="carte-info-cours">

        <div class="info">
            <h2>Prochains cours</h2>

    <?php if($prochains_cours->num_rows > 0) { ?>

    <?php while($cours = $prochains_cours->fetch_assoc()) { ?>

        <div class="sous-carte">
            <strong><?= $cours['matiere'] ?></strong>

            <p>
                <?= $cours['jour'] ?>
                à
                <?= $cours['heure_debut'] ?>
                - Salle <?= $cours['salle'] ?>
            </p>
        </div>

    <?php } ?>

<?php } else { ?>

    <p class="message_pas_cours">Aucun cours prévu pour le moment</p>

<?php } ?>
</div>

<div class="info">

    <h2>Notes récentes</h2>

    <?php if($notes_recentes->num_rows > 0){ ?>

        <?php while($note = $notes_recentes->fetch_assoc()) { ?>

            <div class="sous-carte note-ligne">

                <div>
                    <strong><?= $note['matiere'] ?></strong>
                    <p><?= $note['date_note'] ?></p>
                </div>

                <span><?= $note['valeur'] ?>/20</span>

            </div>

        <?php } ?>

    <?php } else { ?>

        <p class="message_pas_cours">
            Pas de note pour le moment
        </p>

    <?php } ?>

</div>

    </div>

</main>

</body>

</html>