<?php
$host   = 'localhost';
$dbname = 'smartcampus';
$user   = 'root';
$pass   = 'root';

try {
  $pdo = new PDO(
    "mysql:host=$host;port=3306;dbname=$dbname;charset=utf8",
    $user,
    $pass,
    [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
  );
} catch (PDOException $e) {
  die(json_encode(['success' => false, 'message' => 'Erreur BDD : ' . $e->getMessage()]));
}
?>