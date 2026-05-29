<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/db.php';

$id_prof = 1; // id de test

// Récupère les infos du prof
$stmt = $pdo->prepare("
    SELECT u.nom, u.prenom, u.email, u.telephone, u.date_de_naissance,
           e.specialite
    FROM utilisateur u
    JOIN enseignant e ON u.id_utilisateur = e.id_utilisateur
    WHERE u.id_utilisateur = ?
");
$stmt->execute([$id_prof]);
$prof = $stmt->fetch();

// Initiales
$initiales = strtoupper(substr($prof['prenom'], 0, 1) . substr($prof['nom'], 0, 1));

// Sauvegarde infos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'infos') {
        $pdo->prepare("
            UPDATE utilisateur 
            SET email = ?, telephone = ?, date_de_naissance = ?
            WHERE id_utilisateur = ?
        ")->execute([
            $_POST['email'],
            $_POST['telephone'],
            $_POST['date_naissance'],
            $id_prof
        ]);
        $pdo->prepare("
            UPDATE enseignant SET specialite = ? WHERE id_utilisateur = ?
        ")->execute([$_POST['specialite'], $id_prof]);

        header("Location: profil-prof.php?success=1");
        exit;
    }

    if ($_POST['action'] === 'mdp') {
        $nouveau = $_POST['nouveau_mdp'];
        $confirm = $_POST['confirm_mdp'];

        if ($nouveau === $confirm && strlen($nouveau) >= 6) {
            $hash = password_hash($nouveau, PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE utilisateur SET mot_de_passe = ? WHERE id_utilisateur = ?")
                ->execute([$hash, $id_prof]);
            header("Location: profil-prof.php?mdp=1");
            exit;
        } else {
            header("Location: profil-prof.php?mdp=error");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Profil — SmartCampus</title>
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
      <a href="presences-prof.php" class="nav-item">✅ Présences</a>
      <a href="profil-prof.php"    class="nav-item active">👤 Profil</a>
    </nav>
    <a href="../connexion.html" class="nav-logout">🚪 Déconnexion</a>
  </aside>

  <main class="main">

    <div class="topbar">
      <div>
        <h1>👤 Mon Profil</h1>
        <p>Gérez vos informations personnelles</p>
      </div>
    </div>

    <?php if (isset($_GET['success'])) : ?>
    <div class="pwd-success" style="margin-bottom:16px">
      ✅ Informations sauvegardées avec succès.
    </div>
    <?php endif; ?>

    <div class="profil-layout">

      <!-- Colonne gauche -->
      <div class="profil-left">
        <div class="photo-cercle">
          <span class="photo-initiales"><?php echo $initiales; ?></span>
        </div>
        <div class="profil-nom">
          <?php echo htmlspecialchars($prof['prenom'] . ' ' . $prof['nom']); ?>
        </div>
        <button class="btn-outline" style="margin-top:16px;width:100%">
          📷 Modifier la photo
        </button>
      </div>

      <!-- Colonne droite -->
      <div class="profil-right">

        <!-- Informations personnelles -->
        <div class="card">
          <div class="card-title">Informations personnelles</div>
          <form method="POST" action="profil-prof.php">
            <input type="hidden" name="action" value="infos">
            <div class="profil-info-grid">
              <div class="profil-info-item">
                <span class="profil-info-label">Email</span>
                <input type="email" class="form-input" name="email"
                       value="<?php echo htmlspecialchars($prof['email']); ?>">
              </div>
              <div class="profil-info-item">
                <span class="profil-info-label">Téléphone</span>
                <input type="tel" class="form-input" name="telephone"
                       value="<?php echo htmlspecialchars($prof['telephone'] ?? ''); ?>">
              </div>
              <div class="profil-info-item">
                <span class="profil-info-label">Date de naissance</span>
                <input type="date" class="form-input" name="date_naissance"
                       value="<?php echo $prof['date_de_naissance'] ?? ''; ?>">
              </div>
              <div class="profil-info-item">
                <span class="profil-info-label">Spécialité</span>
                <input type="text" class="form-input" name="specialite"
                       value="<?php echo htmlspecialchars($prof['specialite'] ?? ''); ?>">
              </div>
            </div>
            <button type="submit" class="btn-primary" style="margin-top:16px">
              💾 Sauvegarder
            </button>
          </form>
        </div>

        <!-- Sécurité -->
        <div class="card" style="margin-top:16px">
          <div class="card-title">Sécurité</div>
          <form method="POST" action="profil-prof.php">
            <input type="hidden" name="action" value="mdp">
            <div class="profil-info-item">
              <span class="profil-info-label">Nouveau mot de passe</span>
              <input type="password" class="form-input" name="nouveau_mdp"
                     placeholder="••••••••">
            </div>
            <div class="profil-info-item" style="margin-top:10px">
              <span class="profil-info-label">Confirmer</span>
              <input type="password" class="form-input" name="confirm_mdp"
                     placeholder="••••••••">
            </div>

            <?php if (isset($_GET['mdp']) && $_GET['mdp'] === '1') : ?>
            <div class="pwd-success" style="margin-top:10px">
              ✅ Mot de passe modifié avec succès.
            </div>
            <?php elseif (isset($_GET['mdp']) && $_GET['mdp'] === 'error') : ?>
            <div class="pwd-error" style="margin-top:10px">
              ❌ Les mots de passe ne correspondent pas ou sont trop courts.
            </div>
            <?php endif; ?>

            <button type="submit" class="btn-outline" style="margin-top:16px">
              🔒 Changer le mot de passe
            </button>
          </form>
        </div>

      </div>
    </div>

  </main>
</div>

</body>
</html>