<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'connexion.php';

if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: connexion.html");
    exit();
}

$classes = [];

$result = $conn->query("
    SELECT *
    FROM classe
    ORDER BY nom_classe
");

while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}

$id_classe_sel = $_GET['id_classe'] ?? ($classes[0]['id_classe'] ?? null);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ajouter') {

    $id_classe_post = $_POST['id_classe'] ?? $id_classe_sel;

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM emploi_du_temps
        WHERE id_classe = ?
        AND jour = ?
        AND NOT (heure_fin <= ? OR heure_debut >= ?)
    ");

    $stmt->bind_param(
        "isss",
        $id_classe_post,
        $_POST['jour'],
        $_POST['heure_debut'],
        $_POST['heure_fin']
    );

    $stmt->execute();

    $check = $stmt->get_result()->fetch_assoc();

    if ($check['total'] > 0) {
        header("Location: emploi-du-temps.php?id_classe=$id_classe_post&error=conflit");
        exit();
    }

    $stmt = $conn->prepare("
        INSERT INTO emploi_du_temps
        (jour, heure_debut, heure_fin, salle, id_cours, id_classe)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssssii",
        $_POST['jour'],
        $_POST['heure_debut'],
        $_POST['heure_fin'],
        $_POST['salle'],
        $_POST['id_cours'],
        $id_classe_post
    );

    $stmt->execute();

    $stmt = $conn->prepare("
        INSERT IGNORE INTO suivre
        (id_classe, id_cours)
        VALUES (?, ?)
    ");

    $stmt->bind_param(
        "ii",
        $id_classe_post,
        $_POST['id_cours']
    );

    $stmt->execute();

    header("Location: emploi-du-temps.php?id_classe=$id_classe_post&success=1");
    exit();
}

if (isset($_GET['supprimer'])) {

    $id_creneau = intval($_GET['supprimer']);

    $stmt = $conn->prepare("
        DELETE FROM emploi_du_temps
        WHERE id_creneau = ?
    ");

    $stmt->bind_param("i", $id_creneau);
    $stmt->execute();

    header("Location: emploi-du-temps.php?id_classe=$id_classe_sel");
    exit();
}

$planning = [];

if ($id_classe_sel) {

    $stmt = $conn->prepare("
        SELECT e.id_creneau, e.jour, e.heure_debut, e.heure_fin, e.salle,
               c.matiere, u.nom, u.prenom
        FROM emploi_du_temps e
        JOIN cours c ON e.id_cours = c.id_cours
        JOIN enseignant en ON en.id_utilisateur = c.id_enseignant
        JOIN utilisateur u ON u.id_utilisateur = en.id_utilisateur
        WHERE e.id_classe = ?
        ORDER BY FIELD(e.jour,'Lundi','Mardi','Mercredi','Jeudi','Vendredi'),
                 e.heure_debut
    ");

    $stmt->bind_param("i", $id_classe_sel);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($cr = $result->fetch_assoc()) {
        $planning[$cr['jour']][substr($cr['heure_debut'], 0, 5)] = $cr;
    }
}

$cours = [];

$result = $conn->query("
    SELECT c.id_cours, c.matiere, u.nom, u.prenom
    FROM cours c
    JOIN enseignant e ON e.id_utilisateur = c.id_enseignant
    JOIN utilisateur u ON u.id_utilisateur = e.id_utilisateur
    ORDER BY c.matiere
");

while ($row = $result->fetch_assoc()) {
    $cours[] = $row;
}

$offset = isset($_GET['semaine']) ? (int)$_GET['semaine'] : 0;
$lundi = new DateTime('monday this week');
$lundi->modify("$offset weeks");
$vendredi = clone $lundi;
$vendredi->modify('+4 days');

$jours = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi'];
$heures = ['08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00'];
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Planning — SmartCampus</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="sidebar-logo"><img src="images/logo-blanc.png" alt="logo"></div>
    <nav class="nav">
      <a href="dashboard-admin.php" class="nav-item">🏠 Tableau de bord</a>
      <a href="emploi-du-temps.php" class="nav-item active">📅 Gestion emploi du temps</a>
      <a href="utilisateurs.php" class="nav-item ">👥 Gestion des utilisateurs</a>
      <a href="enseignants.php" class="nav-item ">🎓 Gestion des enseignants</a>
      <a href="eleves.php" class="nav-item ">👤 Gestion des élèves</a>
      <a href="inscriptions.php" class="nav-item ">📋 Gestion des inscriptions</a>
      <a href="mon-profil.php" class="nav-item ">👤 Mon Profil</a>
    </nav>
    <a href="connexion.html" class="nav-logout">🚪 Déconnexion</a>
  </aside>
  <main class="main">
    <div class="topbar">
      <div><h1>📅 Gestion de l'emploi du temps</h1><p>Gérez les créneaux horaires par classe</p></div>
      <div class="topbar-right"><button class="btn-primary" onclick="document.getElementById('modal-ajout').style.display='flex'">+ Ajouter un cours</button></div>
    </div>

    <?php if (isset($_GET['success'])) : ?><div class="pwd-success" style="margin-bottom:16px">✅ Créneau ajouté avec succès.</div><?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'conflit') : ?><div class="pwd-error" style="margin-bottom:16px">❌ Conflit horaire : un cours existe déjà sur ce créneau.</div><?php endif; ?>

    <div class="card" style="margin-bottom:20px">
      <form method="GET">
        <div class="filtres">
          <div class="filtre-group"><label>Classe</label><select class="filtre-select" name="id_classe">
            <?php foreach ($classes as $cl) : ?><option value="<?= $cl['id_classe'] ?>" <?= $cl['id_classe'] == $id_classe_sel ? 'selected' : '' ?>><?= htmlspecialchars($cl['nom_classe']) ?></option><?php endforeach; ?>
          </select></div>
          <input type="hidden" name="semaine" value="<?= $offset ?>">
          <button class="btn-primary">Afficher</button>
        </div>
      </form>
    </div>

    <div class="card">
      <div class="card-title">
        Semaine du <?= $lundi->format('d/m/Y') ?> au <?= $vendredi->format('d/m/Y') ?>
        <span style="float:right;display:flex;gap:8px">
          <a class="btn-outline" href="?id_classe=<?= $id_classe_sel ?>&semaine=<?= $offset-1 ?>">◀</a>
          <a class="btn-outline" href="?id_classe=<?= $id_classe_sel ?>&semaine=<?= $offset+1 ?>">▶</a>
        </span>
      </div>
      <div class="planning-grid">
        <div class="planning-header"></div>
        <?php foreach ($jours as $jour) : ?><div class="planning-header"><?= $jour ?></div><?php endforeach; ?>

        <?php foreach ($heures as $heure) : ?>
          <div class="planning-heure"><?= str_replace(':', 'h', $heure) ?></div>
          <?php foreach ($jours as $jour) : ?>
            <?php if (isset($planning[$jour][$heure])) : $c = $planning[$jour][$heure]; ?>
              <div class="planning-cell cours-cell">
                <div class="cours-planning">
                  <div class="cours-planning-name"><?= htmlspecialchars($c['matiere']) ?></div>
                  <div class="cours-planning-info"><?= substr($c['heure_debut'],0,5) ?> — <?= substr($c['heure_fin'],0,5) ?> | <?= htmlspecialchars($c['salle']) ?></div>
                  <div class="cours-planning-info"><?= htmlspecialchars($c['prenom'].' '.$c['nom']) ?></div>
                  <a href="emploi-du-temps.php?id_classe=<?= $id_classe_sel ?>&supprimer=<?= $c['id_creneau'] ?>" onclick="return confirm('Supprimer ce créneau ?')" style="color:white;font-size:11px;display:inline-block;margin-top:4px">Supprimer</a>
                </div>
              </div>
            <?php else : ?><div class="planning-cell"></div><?php endif; ?>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </div>
    </div>

    <div id="modal-ajout" class="modal">
      <div class="modal-card"><h2 class="modal-title">➕ Ajouter un créneau</h2>
        <form method="POST"><input type="hidden" name="action" value="ajouter"><input type="hidden" name="id_classe" value="<?= htmlspecialchars($id_classe_sel) ?>">
          <div class="profil-info-grid">
            <div class="profil-info-item"><span class="profil-info-label">Cours</span><select class="form-input" name="id_cours" required><?php foreach ($cours as $c) : ?><option value="<?= $c['id_cours'] ?>"><?= htmlspecialchars($c['matiere'].' — '.$c['prenom'].' '.$c['nom']) ?></option><?php endforeach; ?></select></div>
            <div class="profil-info-item"><span class="profil-info-label">Jour</span><select class="form-input" name="jour"><?php foreach ($jours as $j) : ?><option value="<?= $j ?>"><?= $j ?></option><?php endforeach; ?></select></div>
            <div class="profil-info-item"><span class="profil-info-label">Heure début</span><input type="time" class="form-input" name="heure_debut" required></div>
            <div class="profil-info-item"><span class="profil-info-label">Heure fin</span><input type="time" class="form-input" name="heure_fin" required></div>
            <div class="profil-info-item"><span class="profil-info-label">Salle</span><input class="form-input" name="salle" placeholder="Ex : B204" required></div>
          </div>
          <div class="actions-row"><button class="btn-primary">✅ Ajouter</button><button type="button" class="btn-outline" onclick="document.getElementById('modal-ajout').style.display='none'">Annuler</button></div>
        </form>
      </div>
    </div>
  </main>
</div>
</body>
</html>