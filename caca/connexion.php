<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

$conn = new mysqli("localhost", "root", "root", "smartcampus");

if ($conn->connect_error) {
    die("Erreur connexion BDD : " . $conn->connect_error);
}
?>
