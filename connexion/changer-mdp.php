<?php

// Connexion à la base de données
$host = "localhost";
$dbname = "smartcampus";
$user = "root";
$password = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $user,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Vérifie que le formulaire est envoyé
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $ancien_mdp = trim($_POST['ancien_mdp']);
    $nouveau_mdp = trim($_POST['nouveau_mdp']);
    $confirm_mdp = trim($_POST['confirm_mdp']);

    // Vérification des mots de passe
    if ($nouveau_mdp != $confirm_mdp) {
        die("Les nouveaux mots de passe ne correspondent pas.");
    }

    // Recherche de l'utilisateur
    $req = $pdo->prepare("
        SELECT id_utilisateur, mot_de_passe
        FROM utilisateur
        WHERE email = ?
    ");

    $req->execute([$email]);
    $user = $req->fetch();

    if (!$user) {
        die("Aucun compte trouvé avec cet email.");
    }

    // Vérifie l'ancien mot de passe
    if ($ancien_mdp != $user['mot_de_passe']) {
        die("Mot de passe actuel incorrect.");
    }

    // Mise à jour du mot de passe
    $update = $pdo->prepare("
        UPDATE utilisateur
        SET mot_de_passe = ?
        WHERE id_utilisateur = ?
    ");

    $update->execute([
        $nouveau_mdp,
        $user['id_utilisateur']
    ]);

    echo "
    <script>
        alert('Mot de passe modifié avec succès !');
        window.location.href='connexion.html';
    </script>
    ";

}
?>