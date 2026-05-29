<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/db.php';

// Supprimer un utilisateur
if (isset($_GET['supprimer'])) {
    $pdo->prepare("DELETE FROM utilisateur WHERE id_utilisateur = ?")
        ->execute([$_GET['supprimer']]);
    header("Location: utilisateurs.php");
    exit;
}

// Ajouter un utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'ajouter') {
    $hash = password_hash($_POST['mot_de_passe'], PASSWORD_BCRYPT);
    $pdo->prepare("INSERT INTO utilisateur (nom, prenom, email, mot_de_passe) VALUES (?, ?, ?, ?)")
        ->execute([$_POST['nom'], $_POST['prenom'], $_POST['email'], $hash]);
    $id = $pdo->lastInsertId();

    if ($_POST['role'] === 'eleve') {
        $pdo->prepare("INSERT INTO eleve (id_utilisateur, numero_eleve, niveau_scolaire) VALUES (?, ?, ?)")
            ->execute([$id, 'E'.str_pad($id,3,'0',STR_PAD_LEFT), 'Seconde']);
    } elseif ($_POST['role'] === 'enseignant') {
        $pdo->prepare("INSERT INTO enseignant (id_utilisateur, specialite) VALUES (?, ?)")
            ->execute([$id, '']);
    } elseif ($_POST['role'] === 'admin') {
        $pdo->prepare("INSERT INTO admin (id_utilisateur) VALUES (?)")
            ->execute([$id]);
    }
    header("Location: utilisateurs.php?success=1");
    exit;
}

// Récupère tous les utilisateurs avec leur rôle
$utilisateurs = $pdo->query("
    SELECT u.id_utilisateur, u.nom, u.prenom, u.email, u.telephone,
           CASE
               WHEN a.id_utilisateur IS NOT NULL THEN 'Admin'
               WHEN e.id_utilisateur IS NOT NULL THEN 'Enseignant'
               WHEN el.id_utilisateur IS NOT NULL THEN 'Élève'
               ELSE 'Inconnu'
           END as role
    FROM utilisateur u
    LEFT JOIN admin a ON a.id_utilisateur = u.id_utilisateur
    LEFT JOIN enseignant e ON e.id_utilisateur = u.id_utilisateur
    LEFT JOIN eleve el ON el.id_utilisateur = u.id_utilisateur
    ORDER BY u.nom
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Utilisateurs — SmartCampus</title>
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
      <a href="utilisateurs.php"    class="nav-item active">👥 Gestion des utilisateurs</a>
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
        <h1>👥 Gestion des utilisateurs</h1>
        <p>Gérez tous les comptes de la plateforme</p>
      </div>
      <div class="topbar-right">
        <button class="btn-primary"
                onclick="document.getElementById('modal-ajout').style.display='flex'">
          + Ajouter un utilisateur
        </button>
      </div>
    </div>

    <?php if (isset($_GET['success'])) : ?>
    <div class="pwd-success" style="margin-bottom:16px">✅ Utilisateur ajouté avec succès.</div>
    <?php endif; ?>

    <div class="card">
      <div class="card-title">
        Liste des utilisateurs
        <span class="badge-moyenne"><?php echo count($utilisateurs); ?> utilisateur(s)</span>
      </div>

      <table class="notes-table">
        <thead>
          <tr>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Email</th>
            <th>Rôle</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($utilisateurs)) : ?>
          <tr>
            <td colspan="5" style="text-align:center;color:#94a3b8;padding:20px">
              Aucun utilisateur trouvé.
            </td>
          </tr>
          <?php else : ?>
          <?php foreach ($utilisateurs as $u) : ?>
          <tr>
            <td><?php echo htmlspecialchars($u['nom']); ?></td>
            <td><?php echo htmlspecialchars($u['prenom']); ?></td>
            <td><?php echo htmlspecialchars($u['email']); ?></td>
            <td>
              <span class="badge-statut <?php echo $u['role'] === 'Admin' ? 'valide' : 'attente'; ?>">
                <?php echo htmlspecialchars($u['role']); ?>
              </span>
            </td>
            <td>
              <button class="btn-save">✏️ Modifier</button>
              <a href="utilisateurs.php?supprimer=<?php echo $u['id_utilisateur']; ?>"
                 onclick="return confirm('Supprimer cet utilisateur ?')">
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
        <h2 style="margin-bottom:20px;font-size:18px">➕ Ajouter un utilisateur</h2>
        <form method="POST" action="utilisateurs.php">
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
              <span class="profil-info-label">Rôle</span>
              <select class="form-input" name="role">
                <option value="eleve">Élève</option>
                <option value="enseignant">Enseignant</option>
                <option value="admin">Administrateur</option>
              </select>
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