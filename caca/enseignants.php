<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once("connexion.php");

if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: connexion.html");
    exit();
}

/* AJOUTER UN ENSEIGNANT */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "ajouter") {

    $hash = password_hash($_POST["mot_de_passe"], PASSWORD_BCRYPT);

    $telephone = $_POST["telephone"] ?? "";
    $date_naissance = $_POST["date_naissance"] ?? null;

    $stmt = $conn->prepare("
        INSERT INTO utilisateur
        (
            nom,
            prenom,
            email,
            mot_de_passe,
            role,
            telephone,
            date_de_naissance,
            photo
        )
        VALUES (?, ?, ?, ?, 'enseignant', ?, ?, '')
    ");

    $stmt->bind_param(
        "ssssss",
        $_POST["nom"],
        $_POST["prenom"],
        $_POST["email"],
        $hash,
        $telephone,
        $date_naissance
    );

    $stmt->execute();

    $id = $conn->insert_id;

    $stmt = $conn->prepare("
        INSERT INTO enseignant
        (
            id_utilisateur,
            specialite
        )
        VALUES (?, ?)
    ");

    $stmt->bind_param(
        "is",
        $id,
        $_POST["specialite"]
    );

    $stmt->execute();

    header("Location: enseignants.php?success=1");
    exit();
}

/* SUPPRIMER */
if (isset($_GET["supprimer"])) {

    $id = intval($_GET["supprimer"]);

    $stmt = $conn->prepare("
        DELETE FROM utilisateur
        WHERE id_utilisateur = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: enseignants.php");
    exit();
}

/* RECHERCHE */
$search = $_GET["search"] ?? "";

/* LISTE DES ENSEIGNANTS */

$sql = "
SELECT
    u.id_utilisateur,
    u.nom,
    u.prenom,
    u.email,
    u.telephone,
    e.specialite,
    GROUP_CONCAT(c.matiere SEPARATOR ', ') AS cours
FROM utilisateur u
JOIN enseignant e
    ON u.id_utilisateur = e.id_utilisateur
LEFT JOIN cours c
    ON c.id_enseignant = u.id_utilisateur
WHERE u.role = 'enseignant'
";

$params = [];
$types = "";

if ($search !== "") {

    $sql .= "
    AND (
        u.nom LIKE ?
        OR u.prenom LIKE ?
        OR u.email LIKE ?
    )
    ";

    $recherche = "%".$search."%";

    $params[] = $recherche;
    $params[] = $recherche;
    $params[] = $recherche;

    $types .= "sss";
}

$sql .= "
GROUP BY
    u.id_utilisateur,
    u.nom,
    u.prenom,
    u.email,
    u.telephone,
    e.specialite
ORDER BY u.nom, u.prenom
";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$result = $stmt->get_result();

$enseignants = [];

while ($row = $result->fetch_assoc()) {
    $enseignants[] = $row;
}
?>
<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Enseignants — SmartCampus</title><link rel="stylesheet" href="style.css"></head><body>
<div class="layout">
  <aside class="sidebar">
    <div class="sidebar-logo"><img src="images/logo-blanc.png" alt="logo"></div>
    <nav class="nav">
      <a href="dashboard-admin.php" class="nav-item">🏠 Tableau de bord</a>
      <a href="planning-admin.php" class="nav-item ">📅 Gestion emploi du temps</a>
      <a href="utilisateurs.php" class="nav-item ">👥 Gestion des utilisateurs</a>
      <a href="enseignants.php" class="nav-item active">🎓 Gestion des enseignants</a>
      <a href="eleves.php" class="nav-item ">👤 Gestion des élèves</a>
      <a href="inscriptions.php" class="nav-item ">📋 Gestion des inscriptions</a>
      <a href="mon-profil.php" class="nav-item ">👤 Mon Profil</a>
    </nav>
    <a href="connexion.html" class="nav-logout">🚪 Déconnexion</a>
  </aside>
<main class="main">
  <div class="topbar"><div><h1>🎓 Gestion des enseignants</h1><p>Gérez les enseignants de l'établissement</p></div><div class="topbar-right"><button class="btn-primary" onclick="document.getElementById('modal-ajout').style.display='flex'">+ Ajouter</button></div></div>
  <?php if (isset($_GET['success'])) : ?><div class="pwd-success" style="margin-bottom:16px">✅ Enseignant ajouté avec succès.</div><?php endif; ?>
  <div class="card" style="margin-bottom:20px"><form method="GET"><div class="filtres"><div class="filtre-group"><label>Rechercher</label><input class="filtre-select" name="search" placeholder="Nom, prénom, email..." value="<?= htmlspecialchars($search) ?>"></div><button class="btn-primary">Rechercher</button></div></form></div>
  <div class="card"><div class="card-title">Liste des enseignants <span class="badge-moyenne"><?= count($enseignants) ?> enseignant(s)</span></div>
    <table class="notes-table"><thead><tr><th>Nom</th><th>Prénom</th><th>Email</th><th>Téléphone</th><th>Spécialité</th><th>Cours</th><th>Actions</th></tr></thead><tbody>
    <?php if (empty($enseignants)) : ?><tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:20px">Aucun enseignant trouvé.</td></tr><?php endif; ?>
    <?php foreach ($enseignants as $ens) : ?><tr><td><?= htmlspecialchars($ens['nom']) ?></td><td><?= htmlspecialchars($ens['prenom']) ?></td><td><?= htmlspecialchars($ens['email']) ?></td><td><?= htmlspecialchars($ens['telephone'] ?? '--') ?></td><td><?= htmlspecialchars($ens['specialite'] ?? '--') ?></td><td><?= htmlspecialchars($ens['cours'] ?? '--') ?></td><td><button class="btn-save">✏️ Modifier</button><a href="enseignants.php?supprimer=<?= $ens['id_utilisateur'] ?>" onclick="return confirm('Supprimer cet enseignant ?')"><button class="btn-lock">🗑️ Supprimer</button></a></td></tr><?php endforeach; ?>
    </tbody></table>
  </div>
  <br><br>
  <div id="modal-ajout" class="modal"><div class="modal-card"><h2 class="modal-title">➕ Ajouter un enseignant</h2><form method="POST"><input type="hidden" name="action" value="ajouter"><div class="profil-info-grid">
    <div class="profil-info-item"><span class="profil-info-label">Nom</span><input class="form-input" name="nom" required></div>
    <div class="profil-info-item"><span class="profil-info-label">Prénom</span><input class="form-input" name="prenom" required></div>
    <div class="profil-info-item"><span class="profil-info-label">Email</span><input type="email" class="form-input" name="email" required></div>
    <div class="profil-info-item"><span class="profil-info-label">Téléphone</span><input class="form-input" name="telephone"></div>
    <div class="profil-info-item"><span class="profil-info-label">Date naissance</span><input type="date" class="form-input" name="date_naissance"></div>
    <div class="profil-info-item"><span class="profil-info-label">Mot de passe</span><input type="password" class="form-input" name="mot_de_passe" required></div>
    <div class="profil-info-item"><span class="profil-info-label">Spécialité</span><input class="form-input" name="specialite" placeholder="Ex : Mathématiques"></div>
  </div><div class="actions-row"><button class="btn-primary">✅ Ajouter</button><button type="button" class="btn-outline" onclick="document.getElementById('modal-ajout').style.display='none'">Annuler</button></div></form></div></div>
</main></div></body></html>
