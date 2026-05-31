<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once("../connexion/connexion.php");

if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: ../connexion/connexion.html");
    exit();
}

/* ACCEPTER UNE DEMANDE */
if (isset($_GET["accepter"])) {

    $id = intval($_GET["accepter"]);

    $stmt = $conn->prepare("
        SELECT *
        FROM utilisateur_en_attente
        WHERE id_attente = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $demande = $stmt->get_result()->fetch_assoc();

    if ($demande) {

        $role = $demande["role"] ?? "eleve";

        if (!in_array($role, ["eleve", "enseignant", "admin"])) {
            $role = "eleve";
        }

        $mdp = $demande["mot_de_passe"] ?? password_hash("temporaire123", PASSWORD_BCRYPT);

        $telephone = $demande["telephone"] ?? "";
        $date_naissance = $demande["date_de_naissance"] ?? null;
        $photo = $demande["photo"] ?? "";

        $stmt = $conn->prepare("
            INSERT INTO utilisateur
            (nom, prenom, email, mot_de_passe, role, telephone, date_de_naissance, photo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssssssss",
            $demande["nom"],
            $demande["prenom"],
            $demande["email"],
            $mdp,
            $role,
            $telephone,
            $date_naissance,
            $photo
        );

        $stmt->execute();

        $id_user = $conn->insert_id;

        if ($role === "eleve") {

            $numero_eleve = "E" . str_pad($id_user, 3, "0", STR_PAD_LEFT);
            $niveau = $demande["classe"] ?? "ING1";

            $stmt = $conn->prepare("
                INSERT INTO eleve
                (id_utilisateur, numero_eleve, niveau_scolaire)
                VALUES (?, ?, ?)
            ");

            $stmt->bind_param("iss", $id_user, $numero_eleve, $niveau);
            $stmt->execute();

        } elseif ($role === "enseignant") {

            $specialite = "";

            $stmt = $conn->prepare("
                INSERT INTO enseignant
                (id_utilisateur, specialite)
                VALUES (?, ?)
            ");

            $stmt->bind_param("is", $id_user, $specialite);
            $stmt->execute();

        } elseif ($role === "admin") {

            $stmt = $conn->prepare("
                INSERT INTO admin
                (id_utilisateur)
                VALUES (?)
            ");

            $stmt->bind_param("i", $id_user);
            $stmt->execute();
        }

        $stmt = $conn->prepare("
            UPDATE utilisateur_en_attente
            SET statut = 'accepte'
            WHERE id_attente = ?
        ");

        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    header("Location: inscriptions.php?success=1");
    exit();
}

/* REFUSER UNE DEMANDE */
if (isset($_GET["refuser"])) {

    $id = intval($_GET["refuser"]);

    $stmt = $conn->prepare("
        UPDATE utilisateur_en_attente
        SET statut = 'refuse'
        WHERE id_attente = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: inscriptions.php");
    exit();
}

/* DEMANDE SÉLECTIONNÉE */
$id_sel = $_GET["id"] ?? null;
$demande_sel = null;

if ($id_sel) {

    $id_sel_int = intval($id_sel);

    $stmt = $conn->prepare("
        SELECT *
        FROM utilisateur_en_attente
        WHERE id_attente = ?
    ");

    $stmt->bind_param("i", $id_sel_int);
    $stmt->execute();

    $demande_sel = $stmt->get_result()->fetch_assoc();
}

/* LISTE DES DEMANDES */
$demandes = [];

$result = $conn->query("
    SELECT *
    FROM utilisateur_en_attente
    WHERE statut = 'en_attente'
    ORDER BY date_demande DESC
");

while ($row = $result->fetch_assoc()) {
    $demandes[] = $row;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Inscriptions — SmartCampus</title>
  <link rel="stylesheet" href="../style.css">
</head>

<body>

<div class="layout">

  <aside class="sidebar">
    <div class="sidebar-logo">
      <img src="../images/logo-blanc.png" alt="logo">
    </div>
    <nav class="nav">
      <a href="dashboard-admin.php" class="nav-item active">🏠 Tableau de bord</a>
      <a href="emploi-du-temps.php" class="nav-item">📅 Gestion emploi du temps</a>
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
        <h1>📋 Gestion des inscriptions</h1>
        <p>Gérez les demandes d'inscription</p>
      </div>
    </div>

    <?php if (isset($_GET["success"])) : ?>
      <div class="pwd-success" style="margin-bottom:16px;">
        ✅ Demande acceptée avec succès.
      </div>
    <?php endif; ?>

    <div class="content-grid" style="grid-template-columns:1fr 1fr;">

      <div class="card">
        <div class="card-title">
          📥 Demandes en attente
          <span class="badge-moyenne">
            <?php echo count($demandes); ?>
          </span>
        </div>

        <?php if (empty($demandes)) : ?>
          <p style="color:#94a3b8;font-size:13px;">
            Aucune demande en attente.
          </p>
        <?php endif; ?>

        <?php foreach ($demandes as $d) : ?>
          <a 
            href="inscriptions.php?id=<?php echo $d["id_attente"]; ?>" 
            style="text-decoration:none;"
          >
            <div 
              class="notif-item" 
              style="<?php echo $id_sel == $d["id_attente"] ? 'background:#eff6ff;border-radius:8px;padding:8px;' : ''; ?>"
            >
              <span class="notif-dot orange"></span>

              <div>
                <div style="font-weight:700;color:#0f172a;font-size:13px;">
                  <?php echo htmlspecialchars($d["prenom"] . " " . $d["nom"]); ?>
                </div>

                <div style="font-size:11px;color:#94a3b8;">
                  <?php echo htmlspecialchars($d["role"] ?? "eleve"); ?>
                  —
                  <?php echo date("d/m/Y", strtotime($d["date_demande"])); ?>
                </div>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>

      <div class="card">
        <div class="card-title">
          📄 Information de la demande
        </div>

        <?php if ($demande_sel) : ?>

          <div class="profil-info-grid">

            <div class="profil-info-item">
              <span class="profil-info-label">Nom</span>
              <span><?php echo htmlspecialchars($demande_sel["nom"]); ?></span>
            </div>

            <div class="profil-info-item">
              <span class="profil-info-label">Prénom</span>
              <span><?php echo htmlspecialchars($demande_sel["prenom"]); ?></span>
            </div>

            <div class="profil-info-item">
              <span class="profil-info-label">Email</span>
              <span><?php echo htmlspecialchars($demande_sel["email"]); ?></span>
            </div>

            <div class="profil-info-item">
              <span class="profil-info-label">Rôle demandé</span>
              <span><?php echo htmlspecialchars($demande_sel["role"] ?? "eleve"); ?></span>
            </div>

            <div class="profil-info-item">
              <span class="profil-info-label">Date</span>
              <span>
                <?php echo date("d/m/Y H:i", strtotime($demande_sel["date_demande"])); ?>
              </span>
            </div>

          </div>

          <div style="display:flex;gap:10px;margin-top:16px;">
            <a 
              href="inscriptions.php?accepter=<?php echo $demande_sel["id_attente"]; ?>" 
              class="btn-primary" 
              style="text-align:center;text-decoration:none;flex:1;"
            >
              ✅ Accepter
            </a>

            <a 
              href="inscriptions.php?refuser=<?php echo $demande_sel["id_attente"]; ?>" 
              onclick="return confirm('Refuser cette demande ?')" 
              class="btn-outline" 
              style="text-align:center;text-decoration:none;color:#dc2626;border-color:#fecaca;flex:1;"
            >
              ❌ Refuser
            </a>
          </div>

        <?php else : ?>

          <p style="color:#94a3b8;font-size:13px;">
            Sélectionnez une demande dans la liste pour voir les détails.
          </p>

        <?php endif; ?>
      </div>

    </div>

  </main>
</div>

</body>
</html>