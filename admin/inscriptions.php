<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/db.php';

// Accepter une demande avec rôle choisi par l'admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'accepter') {
    $id   = $_POST['id_attente'];
    $role = $_POST['role'];

    $stmt = $pdo->prepare("SELECT * FROM utilisateur_en_attente WHERE id_attente = ?");
    $stmt->execute([$id]);
    $demande = $stmt->fetch();

    if ($demande) {
        $pdo->prepare("INSERT INTO utilisateur (nom, prenom, email, mot_de_passe) VALUES (?, ?, ?, ?)")
            ->execute([$demande['nom'], $demande['prenom'], $demande['email'], $demande['mot_de_passe']]);
        $id_user = $pdo->lastInsertId();

        if ($role === 'eleve') {
            $pdo->prepare("INSERT INTO eleve (id_utilisateur, numero_eleve, niveau_scolaire) VALUES (?, ?, ?)")
                ->execute([$id_user, 'E'.str_pad($id_user, 3, '0', STR_PAD_LEFT), 'Seconde']);
        } elseif ($role === 'enseignant') {
            $pdo->prepare("INSERT INTO enseignant (id_utilisateur, specialite) VALUES (?, ?)")
                ->execute([$id_user, '']);
        } elseif ($role === 'admin') {
            $pdo->prepare("INSERT INTO admin (id_utilisateur) VALUES (?)")
                ->execute([$id_user]);
        }

        $pdo->prepare("UPDATE utilisateur_en_attente SET statut = 'accepte' WHERE id_attente = ?")
            ->execute([$id]);
    }
    header("Location: inscriptions.php?success=1");
    exit;
}

// Refuser une demande
if (isset($_GET['refuser'])) {
    $pdo->prepare("UPDATE utilisateur_en_attente SET statut = 'refuse' WHERE id_attente = ?")
        ->execute([$_GET['refuser']]);
    header("Location: inscriptions.php");
    exit;
}

// Demande sélectionnée
$id_sel      = $_GET['id'] ?? null;
$demande_sel = null;
if ($id_sel) {
    $stmt = $pdo->prepare("SELECT * FROM utilisateur_en_attente WHERE id_attente = ?");
    $stmt->execute([$id_sel]);
    $demande_sel = $stmt->fetch();
}

// Récupère les demandes en attente
$demandes = $pdo->query("
    SELECT * FROM utilisateur_en_attente 
    WHERE statut = 'en_attente'
    ORDER BY date_demande DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Inscriptions — SmartCampus</title>
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
      <a href="enseignants.php"     class="nav-item">🎓 Gestion des enseignants</a>
      <a href="eleves.php"          class="nav-item">👤 Gestion des élèves</a>
      <a href="inscriptions.php"    class="nav-item active">📋 Gestion des inscriptions</a>
      <a href="mon-profil.php"      class="nav-item">👤 Mon Profil</a>
    </nav>
    <a href="../connexion/connexion.html" class="nav-logout">🚪 Déconnexion</a>
  </aside>

  <main class="main">

    <div class="topbar">
      <div>
        <h1>📋 Gestion des inscriptions</h1>
        <p>Gérez les demandes d'inscription des élèves</p>
      </div>
    </div>

    <?php if (isset($_GET['success'])) : ?>
    <div class="pwd-success" style="margin-bottom:16px">✅ Demande acceptée avec succès.</div>
    <?php endif; ?>

    <div class="content-grid" style="grid-template-columns:1fr 1fr">

      <!-- Liste des demandes -->
      <div class="card">
        <div class="card-title">
          📥 Demandes en attente
          <span class="badge-moyenne"><?php echo count($demandes); ?></span>
        </div>

        <?php if (empty($demandes)) : ?>
          <p style="color:#94a3b8;font-size:13px">Aucune demande en attente.</p>
        <?php else : ?>
          <?php foreach ($demandes as $d) : ?>
          <a href="inscriptions.php?id=<?php echo $d['id_attente']; ?>"
             style="text-decoration:none">
            <div class="notif-item" style="cursor:pointer;
              <?php echo $id_sel == $d['id_attente'] ? 'background:#eff6ff;border-radius:8px;padding:8px;' : ''; ?>">
              <span class="notif-dot orange"></span>
              <div>
                <div style="font-weight:500;color:#0f172a;font-size:13px">
                  <?php echo htmlspecialchars($d['prenom'].' '.$d['nom']); ?>
                </div>
                <div style="font-size:11px;color:#94a3b8">
                  <?php
                  echo match($d['role']) {
                    'eleve'      => '👤 Élève',
                    'enseignant' => '🎓 Enseignant',
                    'admin'      => '⚙️ Admin',
                    default      => htmlspecialchars($d['role'])
                  };
                  ?> —
                  <?php echo date('d/m/Y', strtotime($d['date_demande'])); ?>
                </div>
              </div>
            </div>
          </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Détail de la demande -->
      <div class="card">
        <div class="card-title">📄 Information de la demande</div>

        <?php if ($demande_sel) : ?>
          <div class="profil-info-grid">
            <div class="profil-info-item">
              <span class="profil-info-label">Nom</span>
              <span><?php echo htmlspecialchars($demande_sel['nom']); ?></span>
            </div>
            <div class="profil-info-item">
              <span class="profil-info-label">Prénom</span>
              <span><?php echo htmlspecialchars($demande_sel['prenom']); ?></span>
            </div>
            <div class="profil-info-item">
              <span class="profil-info-label">Email</span>
              <span><?php echo htmlspecialchars($demande_sel['email']); ?></span>
            </div>
            <div class="profil-info-item">
              <span class="profil-info-label">Rôle demandé</span>
              <span>
                <?php
                echo match($demande_sel['role']) {
                  'eleve'      => '👤 Élève',
                  'enseignant' => '🎓 Enseignant',
                  'admin'      => '⚙️ Administrateur',
                  default      => htmlspecialchars($demande_sel['role'])
                };
                ?>
              </span>
            </div>
            <div class="profil-info-item">
              <span class="profil-info-label">Date de demande</span>
              <span><?php echo date('d/m/Y H:i', strtotime($demande_sel['date_demande'])); ?></span>
            </div>
          </div>

          <!-- Choix du rôle par l'admin -->
          <form method="POST" action="inscriptions.php?id=<?php echo $demande_sel['id_attente']; ?>" style="margin-top:16px">
            <input type="hidden" name="action" value="accepter">
            <input type="hidden" name="id_attente" value="<?php echo $demande_sel['id_attente']; ?>">

            <div class="profil-info-item" style="margin-bottom:14px">
              <span class="profil-info-label">Attribuer le rôle</span>
              <select name="role" class="form-input">
                <option value="eleve"
                  <?php echo $demande_sel['role']==='eleve' ? 'selected' : ''; ?>>
                  👤 Élève
                </option>
                <option value="enseignant"
                  <?php echo $demande_sel['role']==='enseignant' ? 'selected' : ''; ?>>
                  🎓 Enseignant
                </option>
                <option value="admin"
                  <?php echo $demande_sel['role']==='admin' ? 'selected' : ''; ?>>
                  ⚙️ Administrateur
                </option>
              </select>
            </div>

            <div style="display:flex;gap:10px">
              <button type="submit" class="btn-primary" style="flex:1">
                ✅ Accepter avec ce rôle
              </button>
              <a href="inscriptions.php?refuser=<?php echo $demande_sel['id_attente']; ?>"
                 onclick="return confirm('Refuser cette demande ?')"
                 style="flex:1">
                <button type="button" class="btn-outline"
                        style="width:100%;color:#dc2626;border-color:#fecaca">
                  ❌ Refuser
                </button>
              </a>
            </div>
          </form>

        <?php else : ?>
          <p style="color:#94a3b8;font-size:13px">
            Sélectionnez une demande dans la liste pour voir les détails.
          </p>
        <?php endif; ?>
      </div>

    </div>

  </main>
</div>

</body>
</html>