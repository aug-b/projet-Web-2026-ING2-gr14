<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once("connexion.php");

if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: connexion.html");
    exit();
}

/* SUPPRIMER UN UTILISATEUR */
if (isset($_GET["supprimer"])) {
    $id = intval($_GET["supprimer"]);

    $stmt = $conn->prepare("
        DELETE FROM utilisateur 
        WHERE id_utilisateur = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: utilisateurs.php");
    exit();
}

/* AJOUTER UN UTILISATEUR */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "ajouter") {

    $nom = $_POST["nom"];
    $prenom = $_POST["prenom"];
    $email = $_POST["email"];
    $mot_de_passe = $_POST["mot_de_passe"];
    $role = $_POST["role"];

    $hash = password_hash($mot_de_passe, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("
        INSERT INTO utilisateur
        (nom, prenom, email, mot_de_passe, role, telephone, date_de_naissance, photo)
        VALUES (?, ?, ?, ?, ?, '', NULL, '')
    ");

    $stmt->bind_param("sssss", $nom, $prenom, $email, $hash, $role);
    $stmt->execute();

    $id_user = $conn->insert_id;

    if ($role === "eleve") {
        $numero_eleve = "E" . str_pad($id_user, 3, "0", STR_PAD_LEFT);
        $niveau = $_POST["niveau"] ?? "ING1";

        $stmt = $conn->prepare("
            INSERT INTO eleve
            (id_utilisateur, numero_eleve, niveau_scolaire)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iss", $id_user, $numero_eleve, $niveau);
        $stmt->execute();
    }

    if ($role === "enseignant") {
        $specialite = $_POST["specialite"] ?? "";

        $stmt = $conn->prepare("
            INSERT INTO enseignant
            (id_utilisateur, specialite)
            VALUES (?, ?)
        ");
        $stmt->bind_param("is", $id_user, $specialite);
        $stmt->execute();
    }

    if ($role === "admin") {
        $stmt = $conn->prepare("
            INSERT INTO admin
            (id_utilisateur)
            VALUES (?)
        ");
        $stmt->bind_param("i", $id_user);
        $stmt->execute();
    }

    header("Location: utilisateurs.php?success=1");
    exit();
}

/* RÉCUPÉRER TOUS LES UTILISATEURS */
$utilisateurs = [];

$result = $conn->query("
    SELECT 
        id_utilisateur,
        nom,
        prenom,
        email,
        telephone,
        role
    FROM utilisateur
    ORDER BY nom, prenom
");

while ($row = $result->fetch_assoc()) {
    $utilisateurs[] = $row;
}
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
      <img src="images/logo-blanc.png" alt="logo">
    </div>

    <nav class="nav">
      <a href="dashboard-admin.php" class="nav-item">🏠 Tableau de bord</a>
      <a href="planning-admin.php" class="nav-item">📅 Gestion emploi du temps</a>
      <a href="utilisateurs.php" class="nav-item active">👥 Gestion des utilisateurs</a>
      <a href="enseignants.php" class="nav-item">🎓 Gestion des enseignants</a>
      <a href="eleves.php" class="nav-item">👤 Gestion des élèves</a>
      <a href="inscriptions.php" class="nav-item">📋 Gestion des inscriptions</a>
      <a href="mon-profil.php" class="nav-item">👤 Mon Profil</a>
    </nav>

    <a href="connexion.php" class="nav-logout">🚪 Déconnexion</a>
  </aside>

  <main class="main">

    <div class="topbar">
      <div>
        <h1>👥 Gestion des utilisateurs</h1>
        <p>Gérez tous les comptes de la plateforme</p>
      </div>

      <div class="topbar-right">
        <button class="btn-primary" onclick="document.getElementById('modal-ajout').style.display='flex'">
          + Ajouter un utilisateur
        </button>
      </div>
    </div>

    <?php if (isset($_GET["success"])) : ?>
      <div class="pwd-success" style="margin-bottom:16px;">
        ✅ Utilisateur ajouté avec succès.
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-title">
        Liste des utilisateurs
        <span class="badge-moyenne">
          <?php echo count($utilisateurs); ?> utilisateur(s)
        </span>
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
              <td colspan="5" style="text-align:center;color:#94a3b8;padding:20px;">
                Aucun utilisateur trouvé.
              </td>
            </tr>
          <?php else : ?>
            <?php foreach ($utilisateurs as $u) : ?>
              <?php
                $role_class = strtolower($u["role"]);
                $role_label = ucfirst($u["role"]);

                if ($u["role"] === "eleve") {
                    $role_label = "Élève";
                } elseif ($u["role"] === "enseignant") {
                    $role_label = "Enseignant";
                } elseif ($u["role"] === "admin") {
                    $role_label = "Admin";
                }
              ?>

              <tr>
                <td><?php echo htmlspecialchars($u["nom"]); ?></td>
                <td><?php echo htmlspecialchars($u["prenom"]); ?></td>
                <td><?php echo htmlspecialchars($u["email"]); ?></td>
                <td>
                  <span class="badge-statut <?php echo $role_class; ?>">
                    <?php echo htmlspecialchars($role_label); ?>
                  </span>
                </td>
                <td>
                  <a href="modifier-utilisateur.php?id=<?php echo $u["id_utilisateur"]; ?>">
                    <button class="btn-save">✏️ Modifier</button>
                  </a>

                  <a 
                    href="utilisateurs.php?supprimer=<?php echo $u["id_utilisateur"]; ?>"
                    onclick="return confirm('Supprimer cet utilisateur ?')"
                  >
                    <button class="btn-lock">🗑️ Supprimer</button>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div 
      id="modal-ajout"
      style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;
      background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;"
    >
      <div style="background:white;border-radius:18px;padding:32px;width:500px;max-width:90%;">

        <h2 style="margin-bottom:20px;font-size:18px;">
          ➕ Ajouter un utilisateur
        </h2>

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
              <input type="email" class="form-input" name="email" required placeholder="email@smartcampus.fr">
            </div>

            <div class="profil-info-item">
              <span class="profil-info-label">Mot de passe</span>
              <input type="password" class="form-input" name="mot_de_passe" required placeholder="••••••••">
            </div>

            <div class="profil-info-item">
              <span class="profil-info-label">Rôle</span>
              <select class="form-input" name="role" id="role-select" onchange="changerChampsRole()">
                <option value="eleve">Élève</option>
                <option value="enseignant">Enseignant</option>
                <option value="admin">Administrateur</option>
              </select>
            </div>

            <div class="profil-info-item" id="champ-niveau">
              <span class="profil-info-label">Niveau</span>
              <select class="form-input" name="niveau">
                <option value="ING1">ING1</option>
                <option value="ING2">ING2</option>
                <option value="ING3">ING3</option>
              </select>
            </div>

            <div class="profil-info-item" id="champ-specialite" style="display:none;">
              <span class="profil-info-label">Spécialité</span>
              <input type="text" class="form-input" name="specialite" placeholder="Ex: Mathématiques">
            </div>

          </div>

          <div style="display:flex;gap:10px;margin-top:20px;">
            <button type="submit" class="btn-primary" style="flex:1;">
              ✅ Ajouter
            </button>

            <button 
              type="button" 
              class="btn-outline" 
              style="flex:1;"
              onclick="document.getElementById('modal-ajout').style.display='none'"
            >
              Annuler
            </button>
          </div>

        </form>
      </div>
    </div>

  </main>
</div>

<script>
function changerChampsRole() {
    const role = document.getElementById("role-select").value;
    const champNiveau = document.getElementById("champ-niveau");
    const champSpecialite = document.getElementById("champ-specialite");

    if (role === "eleve") {
        champNiveau.style.display = "flex";
        champSpecialite.style.display = "none";
    } else if (role === "enseignant") {
        champNiveau.style.display = "none";
        champSpecialite.style.display = "flex";
    } else {
        champNiveau.style.display = "none";
        champSpecialite.style.display = "none";
    }
}
</script>

</body>
</html>
