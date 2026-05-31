<?php
session_start();
require_once("../connexion/connexion.php");

if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: ../connexion/connexion.html");
    exit();
}

$id_utilisateur = $_SESSION["id_utilisateur"];

if (
    isset($_POST["ancien_mdp"]) &&
    isset($_POST["nouveau_mdp"]) &&
    isset($_POST["confirm_mdp"])
) {
    $ancien = $_POST["ancien_mdp"];
    $nouveau = $_POST["nouveau_mdp"];
    $confirm = $_POST["confirm_mdp"];

    if ($nouveau !== $confirm) {
        header("Location: changer-mdp-admin.html?error=confirm");
        exit();
    }

    $sql = "SELECT mot_de_passe FROM utilisateur WHERE id_utilisateur = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_utilisateur);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || $user["mot_de_passe"] !== $ancien) {
        header("Location: changer-mdp-admin.html?error=ancien");
        exit();
    }

    $sql = "UPDATE utilisateur SET mot_de_passe = ? WHERE id_utilisateur = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $nouveau, $id_utilisateur);
    $stmt->execute();

    header("Location: mon-profil.php?success=mdp");
    exit();
}

header("Location: changer-mdp-admin.html");
exit();
