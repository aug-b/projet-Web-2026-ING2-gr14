<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/db.php';

// Récupère les classes
$classes = $pdo->query("SELECT * FROM classe ORDER BY nom_classe")->fetchAll();

// Classe sélectionnée
$id_classe_sel = $_GET['id_classe'] ?? ($classes[0]['id_classe'] ?? null);

// Calcul semaine
$offset   = isset($_GET['semaine']) ? (int)$_GET['semaine'] : 0;
$lundi    = new DateTime('monday this week');
$lundi->modify("$offset weeks");
$vendredi = clone $lundi;
$vendredi->modify('+4 days');

// Récupère les créneaux de la classe
$planning = [];
if ($id_classe_sel) {
    $stmt = $pdo->prepare("
        SELECT e.jour, e.heure_debut, e.heure_fin, e.salle,
               c.matiere, u.nom, u.prenom
        FROM emploi_du_temps e
        JOIN cours c ON e.id_cours = c.id_cours
        JOIN suivre s ON s.id_cours = c.id_cours
        JOIN enseignant en ON en.id_utilisateur = c.id_enseignant
        JOIN utilisateur u ON u.id_utilisateur = en.id_utilisateur
        WHERE s.id_classe = ?
        ORDER BY e.jour, e.heure_debut
    ");
    $stmt->execute([$id_classe_sel]);
    foreach ($stmt->fetchAll() as $cr) {
        $planning[$cr['jour']][$cr['heure_debut']] = $cr;
    }
}

// Ajouter un créneau
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'ajouter') {
    $pdo->prepare("
        INSERT INTO emploi_du_temps (jour, heure_debut, heure_fin, salle, id_cours)
        VALUES (?, ?, ?, ?, ?)
    ")->execute([
        $_POST['jour'],
        $_POST['heure_debut'],
        $_POST['heure_fin'],
        $_POST['salle'],
        $_POST['id_cours']
    ]);
    header("Location: emploi-du-temps.php?id_classe=$id_classe_sel&success=1");
    exit;
}

// Cours disponibles
$cours = $pdo->query("
    SELECT c.id_cours, c.matiere, u.nom, u.prenom
    FROM cours c
    JOIN enseignant e ON e.id_utilisateur = c.id_enseignant
    JOIN utilisateur u ON u.id_utilisateur = e.id_utilisateur
    ORDER BY c.matiere
")->fetchAll();

$jours            = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi'];
$heures           = ['08:00:00','10:00:00','12:00:00','14:00:00','16:00:00'];
$heures_affichage = ['08h00','10h00','12h00','14h00','16h00'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Emploi du temps — SmartCampus</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="layout">

  <aside class="sidebar">
    <div class="sidebar-logo">
      <img src="../images/logo-blanc.png" alt="logo">
    </div>
    <nav class="nav">
      <a href="dashboard-admin.php" class="nav-item">🏠 Tableau de bord</a>
      <a href="emploi-du-temps.php" class="nav-item active">📅 Gestion emploi du temps</a>
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
        <h1>📅 Gestion de l'emploi du temps</h1>
        <p>Gérez les créneaux horaires de toutes les classes</p>
      </div>
      <div class="topbar-right">
        <button class="btn-primary"
                onclick="document.getElementById('modal-ajout').style.display='flex'">
          + Ajouter un cours
        </button>
      </div>
    </div>

    <?php if (isset($_GET['success'])) : ?>
    <div class="pwd-success" style="margin-bottom:16px">✅ Créneau ajouté avec succès.</div>
    <?php endif; ?>

    <!-- Filtres -->
    <div class="card" style="margin-bottom:20px">
      <form method="GET" action="emploi-du-temps.php">
        <div class="filtres">
          <div class="filtre-group">
            <label>Classe</label>
            <select class="filtre-select" name="id_classe">
              <?php foreach ($classes as $cl) : ?>
              <option value="<?php echo $cl['id_classe']; ?>"
                <?php echo $cl['id_classe'] == $id_classe_sel ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cl['nom_classe']); ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <input type="hidden" name="semaine" value="<?php echo $offset; ?>">
          <button type="submit" class="btn-primary">Afficher</button>
        </div>
      </form>
    </div>

    <!-- Planning -->
    <div class="card">
      <div class="card-title">
        Semaine du <?php echo $lundi->format('d/m/Y'); ?>
        au <?php echo $vendredi->format('d/m/Y'); ?>
        <div style="float:right;display:flex;gap:8px;margin-top:-4px">
          <a href="?id_classe=<?php echo $id_classe_sel; ?>&semaine=<?php echo $offset-1; ?>">
            <button class="btn-outline">◀</button>
          </a>
          <a href="?id_classe=<?php echo $id_classe_sel; ?>&semaine=<?php echo $offset+1; ?>">
            <button class="btn-outline">▶</button>
          </a>
        </div>
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
                    <?php echo substr($c['heure_debut'],0,5); ?> —
                    <?php echo substr($c['heure_fin'],0,5); ?>
                    | <?php echo htmlspecialchars($c['salle']); ?>
                  </div>
                  <div class="cours-planning-info">
                    <?php echo htmlspecialchars($c['prenom'].' '.$c['nom']); ?>
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

    <!-- Modal ajout créneau -->
    <div id="modal-ajout" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;
         background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
      <div style="background:white;border-radius:18px;padding:32px;width:500px;max-width:90%">
        <h2 style="margin-bottom:20px;font-size:18px">➕ Ajouter un créneau</h2>
        <form method="POST" action="emploi-du-temps.php?id_classe=<?php echo $id_classe_sel; ?>">
          <input type="hidden" name="action" value="ajouter">
          <div class="profil-info-grid">
            <div class="profil-info-item">
              <span class="profil-info-label">Cours</span>
              <select class="form-input" name="id_cours">
                <?php foreach ($cours as $c) : ?>
                <option value="<?php echo $c['id_cours']; ?>">
                  <?php echo htmlspecialchars($c['matiere'].' — '.$c['prenom'].' '.$c['nom']); ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="profil-info-item">
              <span class="profil-info-label">Jour</span>
              <select class="form-input" name="jour">
                <?php foreach ($jours as $j) : ?>
                <option value="<?php echo $j; ?>"><?php echo $j; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="profil-info-item">
              <span class="profil-info-label">Heure début</span>
              <input type="time" class="form-input" name="heure_debut">
            </div>
            <div class="profil-info-item">
              <span class="profil-info-label">Heure fin</span>
              <input type="time" class="form-input" name="heure_fin">
            </div>
            <div class="profil-info-item">
              <span class="profil-info-label">Salle</span>
              <input type="text" class="form-input" name="salle" placeholder="Ex: 204">
            </div>
          </div>
          <div style="display:flex;gap:10px;margin-top:20px">
            <button type="submit" class="btn-primary" style="flex:1">✅ Ajouter</button>
            <button type="button" class="btn-outline" style="flex:1"
                    onclick="document.getElementById('modal-ajout').style.display='none'">
              Annuler
            </button>
          </div>
        </form>
      </div>
    </div>

  </main>
</div>

</body>
</html>