<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/db.php';

$id_prof = 1; // id de test
// Récupère les cours du prof
$stmt = $pdo->prepare("
    SELECT c.id_cours, c.matiere
    FROM cours c
    WHERE c.id_enseignant = ?
");
$stmt->execute([$id_prof]);
$cours_prof = $stmt->fetchAll();

// Cours sélectionné
$id_cours_selectionne = $_GET['id_cours'] ?? ($cours_prof[0]['id_cours'] ?? null);

// Récupère les classes du cours sélectionné
$classes = [];
$eleves = [];
if ($id_cours_selectionne) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT cl.id_classe, cl.nom_classe
        FROM classe cl
        JOIN suivre s ON cl.id_classe = s.id_classe
        WHERE s.id_cours = ?
    ");
    $stmt->execute([$id_cours_selectionne]);
    $classes = $stmt->fetchAll();

    // Récupère les élèves inscrits à ce cours
    $stmt = $pdo->prepare("
        SELECT u.id_utilisateur, u.nom, u.prenom,
               MAX(CASE WHEN n.type = 'CC1' THEN n.valeur END) as cc1,
               MAX(CASE WHEN n.type = 'CC2' THEN n.valeur END) as cc2,
               MAX(CASE WHEN n.type = 'Examen' THEN n.valeur END) as examen
        FROM utilisateur u
        JOIN inscription i ON u.id_utilisateur = i.id_eleve
        JOIN suivre s ON i.id_classe = s.id_classe
        LEFT JOIN note n ON n.id_eleve = u.id_utilisateur AND n.id_cours = ?
        WHERE s.id_cours = ?
        GROUP BY u.id_utilisateur, u.nom, u.prenom
    ");
    $stmt->execute([$id_cours_selectionne, $id_cours_selectionne]);
    $eleves = $stmt->fetchAll();
}

// Sauvegarde d'une note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sauver') {
    $id_eleve = $_POST['id_eleve'];
    $id_cours = $_POST['id_cours'];
    $types = ['CC1', 'CC2', 'Examen'];
    $valeurs = [$_POST['cc1'], $_POST['cc2'], $_POST['examen']];

    foreach ($types as $i => $type) {
        if ($valeurs[$i] !== '') {
            // Vérifie si la note existe déjà
            $check = $pdo->prepare("SELECT id_note FROM note WHERE id_eleve = ? AND id_cours = ? AND type = ?");
            $check->execute([$id_eleve, $id_cours, $type]);
            if ($check->fetch()) {
                $pdo->prepare("UPDATE note SET valeur = ?, date_note = NOW() WHERE id_eleve = ? AND id_cours = ? AND type = ?")
                    ->execute([$valeurs[$i], $id_eleve, $id_cours, $type]);
            } else {
                $pdo->prepare("INSERT INTO note (valeur, type, date_note, id_eleve, id_cours) VALUES (?, ?, NOW(), ?, ?)")
                    ->execute([$valeurs[$i], $type, $id_eleve, $id_cours]);
            }
        }
    }
    header("Location: notes-prof.php?id_cours=$id_cours");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Notes — SmartCampus</title>
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
      <a href="notes-prof.php"     class="nav-item active">📝 Notes</a>
      <a href="presences-prof.php" class="nav-item">✅ Présences</a>
      <a href="profil-prof.php"    class="nav-item">👤 Profil</a>
    </nav>
    <a href="../connexion.html" class="nav-logout">🚪 Déconnexion</a>
  </aside>

  <main class="main">

    <div class="topbar">
      <div>
        <h1>📝 Gestion des Notes</h1>
        <p>Saisir, modifier et valider les notes de vos élèves</p>
      </div>
      <div class="topbar-right">
        <button class="btn-outline">Exporter</button>
      </div>
    </div>

    <!-- Filtres -->
    <div class="card" style="margin-bottom:20px">
      <form method="GET" action="notes-prof.php">
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
          <button type="submit" class="btn-primary">Afficher</button>
        </div>
      </form>
    </div>

    <!-- Tableau notes -->
    <div class="card">
      <div class="card-title">
        📋 Notes
        <span class="badge-moyenne">Moyenne classe : --</span>
      </div>

      <table class="notes-table">
        <thead>
          <tr>
            <th>Élève</th>
            <th>CC 1</th>
            <th>CC 2</th>
            <th>Examen</th>
            <th>Moyenne</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($eleves)) : ?>
          <tr>
            <td colspan="6" style="text-align:center;color:#94a3b8;padding:20px">
              Aucun élève trouvé pour ce cours.
            </td>
          </tr>
          <?php else : ?>
          <?php foreach ($eleves as $eleve) : ?>
          <tr>
            <td class="eleve-name">
              👤 <?php echo htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']); ?>
            </td>
            <form method="POST" action="notes-prof.php?id_cours=<?php echo $id_cours_selectionne; ?>">
              <input type="hidden" name="action" value="sauver">
              <input type="hidden" name="id_eleve" value="<?php echo $eleve['id_utilisateur']; ?>">
              <input type="hidden" name="id_cours" value="<?php echo $id_cours_selectionne; ?>">
              <td>
                <input type="number" class="note-input" name="cc1"
                       min="0" max="20" step="0.5"
                       value="<?php echo $eleve['cc1'] ?? ''; ?>"
                       placeholder="--">
              </td>
              <td>
                <input type="number" class="note-input" name="cc2"
                       min="0" max="20" step="0.5"
                       value="<?php echo $eleve['cc2'] ?? ''; ?>"
                       placeholder="--">
              </td>
              <td>
                <input type="number" class="note-input" name="examen"
                       min="0" max="20" step="0.5"
                       value="<?php echo $eleve['examen'] ?? ''; ?>"
                       placeholder="--">
              </td>
              <td class="moyenne">
                <?php
                if ($eleve['cc1'] !== null && $eleve['cc2'] !== null && $eleve['examen'] !== null) {
                    $moy = ($eleve['cc1'] + $eleve['cc2'] + $eleve['examen'] * 2) / 4;
                    echo number_format($moy, 2);
                } else {
                    echo '--';
                }
                ?>
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
document.querySelectorAll('tr').forEach(row => {
  const inputs = row.querySelectorAll('.note-input');
  const moyenneCell = row.querySelector('.moyenne');
  if (!inputs.length || !moyenneCell) return;
  inputs.forEach(input => {
    input.addEventListener('input', () => {
      const vals = [...inputs].map(i => parseFloat(i.value)).filter(v => !isNaN(v));
      if (vals.length === 3) {
        const moy = ((vals[0] * 1) + (vals[1] * 1) + (vals[2] * 2)) / 4;
        moyenneCell.textContent = moy.toFixed(2);
        moyenneCell.style.color = moy >= 10 ? '#16a34a' : '#dc2626';
      } else {
        moyenneCell.textContent = '--';
      }
    });
  });
});
</script>

</body>
</html>