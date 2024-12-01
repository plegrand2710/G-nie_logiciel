<?php
$host = 'localhost';
$dbname = 'gestion_salle_sport';
$user = 'root';
$pass = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connexion réussie à la base de données avec root.";
} catch (PDOException $e) {

    echo "Erreur de connexion : " . $e->getMessage();
}

$stmt = $pdo->prepare("
            INSERT INTO Personne (nom, identifiant, mdp, email, numTel, type)
            VALUES (:nom, :id, :mdp, :email, :num, :type)
        ");
        $stmt->execute([
            ':nom' => "alfred",
            ':id' => "alfred1",
            ':mdp' => "alfred2",
            ':email' => "coucoou@gmail.com",
            ':num' => "0987654",
            ':type' => "Utilisateur"
        ]);
?>
