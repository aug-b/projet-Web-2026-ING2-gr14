<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/db.php';

// Ajouter un enseignant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'ajouter') {
    $hash = password_hash($_POST['mot_de_passe'], PASSWORD_BCRYPT);
    $pdo->prepare("INSERT INTO utilisateur (nom, prenom, email, mot_de_passe) VALUES (?, ?, ?, ?)")
        ->execute([$_POST['nom'], $_POST['prenom'], $_POST['email'], $hash]);
    $id = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO enseignant (id_utilisateur, specialite) VALUES (?, ?)")
        ->execute([$id, $_POST['specialite']]);
    header("Location: enseignants.php?success=1");
    exit;
}

// Supprimer un enseignant
if (isset($_GET['supprimer'])) {
    $pdo->prepare("DELETE FROM utilisateur WHERE id_utilisateur = ?")
        ->execute([$_GET['supprimer']]);
    header("Location: enseignants.php");
    exit;
}

// Recherche
$search = $_GET['search'] ?? '';

$sql = "
    SELECT u.id_utilisateur, u.nom, u.prenom, u.email,
           e.specialite,
           GROUP_CONCAT(c.matiere SEPARATOR ', ') as cours
    FROM utilisateur u
    JOIN enseignant e ON u.id_utilisateur = e.id_utilisateur
    LEFT JOIN cours c ON c.id_enseignant = u.id_utilisateur
    WHERE 1=1
";
$params = [];

if ($search) {
    $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " GROUP BY u.id_utilisateur, u.nom, u.prenom, u.email, e.specialite ORDER BY u.nom";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$enseignants = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Enseignants — SmartCampus</title>
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
      <a href="emploi-du-temps.php" class="nav-item">📅 Gestion emploi du temps</a>
      <a href="utilisateurs.php"    class="nav-item">👥 Gestion des utilisateurs</a>
      <a href="enseignants.php"     class="nav-item active">🎓 Gestion des enseignants</a>
      <a href="eleves.php"          class="nav-item">👤 Gestion des élèves</a>
      <a href="inscriptions.php"    class="nav-item">📋 Gestion des inscriptions</a>
      <a href="mon-profil.php"      class="nav-item">👤 Mon Profil</a>
    </nav>
    <a href="../connexion/connexion.html" class="nav-logout">🚪 Déconnexion</a>
  </aside>

  <main class="main">

    <div class="topbar">
      <div>
        <h1>🎓 Gestion des enseignants</h1>
        <p>Gérez les enseignants de l'établissement</p>
      </div>
      <div class="topbar-right">
        <button class="btn-primary"
                onclick="document.getElementById('modal-ajout').style.display='flex'">
          + Ajouter
        </button>
      </div>
    </div>

    <?php if (isset($_GET['success'])) : ?>
    <div class="pwd-success" style="margin-bottom:16px">✅ Enseignant ajouté avec succès.</div>
    <?php endif; ?>

    <!-- Filtres -->
    <div class="card" style="margin-bottom:20px">
      <form method="GET" action="enseignants.php">
        <div class="filtres">
          <div class="filtre-group">
            <label>Rechercher un enseignant</label>
            <input type="text" class="filtre-select" name="search"
                   placeholder="Nom, prénom..."
                   value="<?php echo htmlspecialchars($search); ?>">
          </div>
          <button type="submit" class="btn-primary">Rechercher</button>
        </div>
      </form>
    </div>

    <!-- Tableau -->
    <div class="card">
      <div class="card-title">
        Liste des enseignants
        <span class="badge-moyenne"><?php echo count($enseignants); ?> enseignant(s)</span>
      </div>
      <table class="notes-table">
        <thead>
          <tr>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Email</th>
            <th>Spécialité</th>
            <th>Cours</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($enseignants)) : ?>
          <tr>
            <td colspan="6" style="text-align:center;color:#94a3b8;padding:20px">
              Aucun enseignant trouvé.
            </td>
          </tr>
          <?php else : ?>
          <?php foreach ($enseignants as $ens) : ?>
          <tr>
            <td><?php echo htmlspecialchars($ens['nom']); ?></td>
            <td><?php echo htmlspecialchars($ens['prenom']); ?></td>
            <td><?php echo htmlspecialchars($ens['email']); ?></td>
            <td><?php echo htmlspecialchars($ens['specialite'] ?? '--'); ?></td>
            <td><?php echo htmlspecialchars($ens['cours'] ?? '--'); ?></td>
            <td>
              <button class="btn-save">✏️ Modifier</button>
              <a href="enseignants.php?supprimer=<?php echo $ens['id_utilisateur']; ?>"
                 onclick="return confirm('Supprimer cet enseignant ?')">
                <button class="btn-lock">🗑️ Supprimer</button>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Modal ajout -->
    <div id="modal-ajout" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;
         background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
      <div style="background:white;border-radius:18px;padding:32px;width:500px;max-width:90%">
        <h2 style="margin-bottom:20px;font-size:18px">➕ Ajouter un enseignant</h2>
        <form method="POST" action="enseignants.php">
          <input type="hidden" name="action" value="ajouter">
          <div class="profil-info-grid">
            <div class="profil-info-item">
              <span class="profil-info-label">Nom</span>
              <input type="text" class="form-input" name="nom" required placeholder="Nom">
            </div>
            <div class="profil-info-item">
              <span class="profil-info-label">Prénom</span>
              <input type="text" class="form-input" name="prenom" required placeholder="Prénom">
            </div>
            <div class="profil-info-item">
              <span class="profil-info-label">Email</span>
              <input type="email" class="form-input" name="email" required placeholder="email@lycee.fr">
            </div>
            <div class="profil-info-item">
              <span class="profil-info-label">Mot de passe</span>
              <input type="password" class="form-input" name="mot_de_passe" required placeholder="••••••••">
            </div>
            <div class="profil-info-item">
              <span class="profil-info-label">Spécialité</span>
              <input type="text" class="form-input" name="specialite" placeholder="Ex: Mathématiques">
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