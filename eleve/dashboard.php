<?php
session_start();

require_once("connexion.php");

if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: page-connexion.html");
    exit();
}

$id_etudiant = $_SESSION["id_utilisateur"];

/* PROFIL */
$profil = $conn->query("
    SELECT nom, prenom, photo, role
    FROM utilisateur
    WHERE id_utilisateur = $id_etudiant
")->fetch_assoc();

/* MOYENNE */
$moyenne = $conn->query("
    SELECT ROUND(AVG(valeur), 1) AS moyenne
    FROM note
    WHERE id_eleve = $id_etudiant
")->fetch_assoc();

/* ABSENCES */
$absences = $conn->query("
    SELECT COUNT(*) AS total
    FROM presence
    WHERE id_eleve = $id_etudiant
    AND statut = 'absent'
")->fetch_assoc();

/* RETARDS */
$retards = $conn->query("
    SELECT COUNT(*) AS total
    FROM presence
    WHERE id_eleve = $id_etudiant
    AND statut = 'retard'
")->fetch_assoc();

/* 4 PROCHAINS COURS */
$prochains_cours = $conn->query("
    SELECT cours.matiere, emploi_du_temps.jour, emploi_du_temps.heure_debut, emploi_du_temps.salle
    FROM inscription
    JOIN suivre ON inscription.id_classe = suivre.id_classe
    JOIN cours ON suivre.id_cours = cours.id_cours
    JOIN emploi_du_temps ON cours.id_cours = emploi_du_temps.id_cours
    WHERE inscription.id_eleve = $id_etudiant
    ORDER BY emploi_du_temps.heure_debut ASC
    LIMIT 4
");

/* 4 DERNIÈRES NOTES */
$notes_recentes = $conn->query("
    SELECT cours.matiere, note.valeur, note.type, note.date_note
    FROM note
    JOIN cours ON note.id_cours = cours.id_cours
    WHERE note.id_eleve = $id_etudiant
    ORDER BY note.date_note DESC
    LIMIT 4
");
