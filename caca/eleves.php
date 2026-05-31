<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once("connexion.php");

if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: connexion.html");
    exit();
}

/* AJOUTER UN ÉLÈVE */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "ajouter") {

    $nom = $_POST["nom"];
    $prenom = $_POST["prenom"];
    $email = $_POST["email"];
    $mot_de_passe = $_POST["mot_de_passe"];
    $niveau = $_POST["niveau"];
    $id_classe = $_POST["id_classe"] ?? null;

    $hash = password_hash($mot_de_passe, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("
        INSERT INTO utilisateur
        (nom, prenom, email, mot_de_passe, role, telephone, date_de_naissance, photo)
        VALUES (?, ?, ?, ?, 'eleve', '', NULL, '')
    ");

    $stmt->bind_param("ssss", $nom, $prenom, $email, $hash);
    $stmt->execute();

    $id_eleve = $conn->insert_id;
    $numero_eleve = "E" . str_pad($id_eleve, 3, "0", STR_PAD_LEFT);

    $stmt = $conn->prepare("
        INSERT INTO eleve
        (id_utilisateur, numero_eleve, niveau_scolaire)
        VALUES (?, ?, ?)
    ");

    $stmt->bind_param("iss", $id_eleve, $numero_eleve, $niveau);
    $stmt->execute();

    if (!empty($id_classe)) {
        $stmt = $conn->prepare("
            INSERT IGNORE INTO inscription
            (date_inscription, statut, id_eleve, id_classe)
            VALUES (CURDATE(), 'valide', ?, ?)
        ");

        $stmt->bind_param("ii", $id_eleve, $id_classe);
        $stmt->execute();
    }

    header("Location: eleves.php?success=1");
    exit();
}

/* SUPPRIMER UN ÉLÈVE */
if (isset($_GET["supprimer"])) {
    $id = intval($_GET["supprimer"]);

    $stmt = $conn->prepare("
        DELETE FROM utilisateur
        WHERE id_utilisateur = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: eleves.php");
    exit();
}

/* FILTRES */
$search = $_GET["search"] ?? "";
$classe = $_GET["classe"] ?? "";

/* RÉCUPÉRER LES CLASSES */
$classes = [];

$result = $conn->query("
    SELECT *
    FROM classe
    ORDER BY nom_classe
");

while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}

/* RÉCUPÉRER LES ÉLÈVES */
$sql = "
    SELECT 
        u.id_utilisateur,
        u.nom,
        u.prenom,
        u.email,
        el.niveau_scolaire,
        MAX(cl.nom_classe) AS nom_classe
    FROM utilisateur u
    JOIN eleve el 
        ON u.id_utilisateur = el.id_utilisateur
    LEFT JOIN inscription i 
        ON i.id_eleve = u.id_utilisateur
    LEFT JOIN classe cl 
        ON cl.id_classe = i.id_classe
    WHERE u.role = 'eleve'
";

$types = "";
$params = [];

if (!empty($search)) {
    $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ?)";
    $search_param = "%" . $search . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($classe)) {
    $sql .= " AND cl.id_classe = ?";
    $params[] = $classe;
    $types .= "i";
}

$sql .= "
    GROUP BY 
        u.id_utilisateur,
        u.nom,
        u.prenom,
        u.email,
        el.niveau_scolaire
    ORDER BY u.nom
";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$eleves = [];

while ($row = $result->fetch_assoc()) {
    $eleves[] = $row;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Élèves — SmartCampus</title>
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
      <a href="utilisateurs.php" class="nav-item">👥 Gestion des utilisateurs</a>
      <a href="enseignants.php" class="nav-item">🎓 Gestion des enseignants</a>
      <a href="eleves.php" class="nav-item active">👤 Gestion des élèves</a>
      <a href="inscriptions.php" class="nav-item">📋 Gestion des inscriptions</a>
      <a href="mon-profil.php" class="nav-item">👤 Mon Profil</a>
    </nav>

    <a href="connexion.php" class="nav-logout">🚪 Déconnexion</a>
  </aside>

  <main class="main">

    <div class="topbar">
      <div>
        <h1>👤 Gestion des élèves</h1>
        <p>Gérez les élèves de l'établissement</p>
      </div>

      <div class="topbar-right">
        <button class="btn-primary" onclick="document.getElementById('modal-ajout').style.display='flex'">
          + Ajouter
        </button>
      </div>
    </div>

    <?php if (isset($_GET["success"])) : ?>
      <div class="pwd-success" style="margin-bottom:16px;">
        ✅ Élève ajouté avec succès.
      </div>
    <?php endif; ?>

    <div class="card" style="margin-bottom:20px;">
      <form method="GET" action="eleves.php">
        <div class="filtres">

          <div class="filtre-group">
            <label>Rechercher</label>
            <input 
              type="text" 
              class="filtre-select" 
              name="search"
              placeholder="Nom, prénom..."
              value="<?php echo htmlspecialchars($search); ?>"
            >
          </div>

          <div class="filtre-group">
            <label>Classe</label>
            <select class="filtre-select" name="classe">
              <option value="">Toutes les classes</option>

              <?php foreach ($classes as $cl) : ?>
                <option 
                  value="<?php echo $cl["id_classe"]; ?>"
                  <?php echo $classe == $cl["id_classe"] ? "selected" : ""; ?>
                >
                  <?php echo htmlspecialchars($cl["nom_classe"]); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <button type="submit" class="btn-primary">
            Rechercher
          </button>

        </div>
      </form>
    </div>

    <div class="card">
      <div class="card-title">
        Liste des élèves
        <span class="badge-moyenne">
          <?php echo count($eleves); ?> élève(s)
        </span>
      </div>

      <table class="notes-table">
        <thead>
          <tr>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Email</th>
            <th>Niveau</th>
            <th>Classe</th>
            <th>Actions</th>
          </tr>
        </thead>

        <tbody>
          <?php if (empty($eleves)) : ?>
            <tr>
              <td colspan="6" style="text-align:center;color:#94a3b8;padding:20px;">
                Aucun élève trouvé.
              </td>
            </tr>
          <?php else : ?>
            <?php foreach ($eleves as $eleve) : ?>
              <tr>
                <td><?php echo htmlspecialchars($eleve["nom"]); ?></td>
                <td><?php echo htmlspecialchars($eleve["prenom"]); ?></td>
                <td><?php echo htmlspecialchars($eleve["email"]); ?></td>
                <td><?php echo htmlspecialchars($eleve["niveau_scolaire"] ?? "--"); ?></td>
                <td><?php echo htmlspecialchars($eleve["nom_classe"] ?? "--"); ?></td>
                <td>
                  <a href="modifier-eleve.php?id=<?php echo $eleve["id_utilisateur"]; ?>">
                    <button class="btn-save">✏️ Modifier</button>
                  </a>

                  <a 
                    href="eleves.php?supprimer=<?php echo $eleve["id_utilisateur"]; ?>"
                    onclick="return confirm('Supprimer cet élève ?')"
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
          ➕ Ajouter un élève
        </h2>

        <form method="POST" action="eleves.php">
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
              <span class="profil-info-label">Niveau</span>
              <select class="form-input" name="niveau">
                <option value="ING1">6éme</option>
                <option value="ING2">5éme</option>
                <option value="ING3">4éme</option>
                <option value="ING3">3éme</option>
              </select>
            </div>

            <div class="profil-info-item">
              <span class="profil-info-label">Classe</span>
              <select class="form-input" name="id_classe">
                <option value="">Aucune classe</option>

                <?php foreach ($classes as $cl) : ?>
                  <option value="<?php echo $cl["id_classe"]; ?>">
                    <?php echo htmlspecialchars($cl["nom_classe"]); ?>
                  </option>
                <?php endforeach; ?>
              </select>
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

</body>
</html>
