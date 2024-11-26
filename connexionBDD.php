<?php
$host = 'localhost';
$dbname = 'gestion_salle_sport';
$user = 'root';
$pass = 'root';

try {
$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Connexion réussie à la base de données.";
} catch (PDOException $e) {
echo "Erreur de connexion : " . $e->getMessage();
}



?>
