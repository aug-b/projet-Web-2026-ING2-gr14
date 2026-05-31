<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once("../connexion/connexion.php");

if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: ../connexion/connexion.html");
    exit();
}

if (!isset($_GET["id"])) {
    die("Utilisateur introuvable");
}

$id = intval($_GET["id"]);

$stmt = $conn->prepare("
    SELECT *
    FROM utilisateur
    WHERE id_utilisateur = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Utilisateur introuvable");
}

$user = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nom = $_POST["nom"];
    $prenom = $_POST["prenom"];
    $email = $_POST["email"];
    $telephone = $_POST["telephone"];
    $role = $_POST["role"];

    $sql = "
        UPDATE utilisateur
        SET
            nom = ?,
            prenom = ?,
            email = ?,
            telephone = ?,
            role = ?
        WHERE id_utilisateur = ?
    ";

    $update = $conn->prepare($sql);

    $update->bind_param(
        "sssssi",
        $nom,
        $prenom,
        $email,
        $telephone,
        $role,
        $id
    );

    $update->execute();

    header("Location: utilisateurs.php?success=2");
    exit();
}
?>

<!DOCTYPE html>

<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Modifier utilisateur</title>
<link rel="stylesheet" href="../style.css">
</head>

<body>

<div class="layout">

<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="../images/logo-blanc.png" alt="">
    </div>
</aside>

<main class="main">

<div class="card" style="max-width:700px;margin:auto;">

<h2>Modifier un utilisateur</h2>

<form method="POST">

<div class="profil-info-grid">

<div class="profil-info-item">
<label>Nom</label>
<input
    type="text"
    name="nom"
    class="form-input"
    value="<?= htmlspecialchars($user["nom"]) ?>"
    required>
</div>

<div class="profil-info-item">
<label>Prénom</label>
<input
    type="text"
    name="prenom"
    class="form-input"
    value="<?= htmlspecialchars($user["prenom"]) ?>"
    required>
</div>

<div class="profil-info-item">
<label>Email</label>
<input
    type="email"
    name="email"
    class="form-input"
    value="<?= htmlspecialchars($user["email"]) ?>"
    required>
</div>

<div class="profil-info-item">
<label>Téléphone</label>
<input
    type="text"
    name="telephone"
    class="form-input"
    value="<?= htmlspecialchars($user["telephone"]) ?>">
</div>

<div class="profil-info-item">
<label>Rôle</label>

<select name="role" class="form-input">

<option value="admin"
<?= $user["role"]=="admin" ? "selected" : "" ?>>
Administrateur
</option>

<option value="enseignant"
<?= $user["role"]=="enseignant" ? "selected" : "" ?>>
Enseignant
</option>

<option value="eleve"
<?= $user["role"]=="eleve" ? "selected" : "" ?>>
Élève
</option>

</select>
</div>

</div>

<div style="margin-top:20px;display:flex;gap:10px;">

<button type="submit" class="btn-primary">
💾 Enregistrer
</button>

<a href="utilisateurs.php">
<button type="button" class="btn-outline">
↩ Retour
</button>
</a>

</div>

</form>

</div>

</main>

</div>

</body>
</html>
