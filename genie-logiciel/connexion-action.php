<?php
include 'require.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifiant = trim($_POST['identifiant'] ?? '');
    $mdp = trim($_POST['mdp'] ?? '');

    if (empty($identifiant) || empty($mdp)) {
        echo "<script>alert('Identifiant et mot de passe sont requis.');</script>";
        header('Location: index.php');
        exit;
    }

    try {
        $bdd = new BaseDeDonnees();
        $pdo = $bdd->getConnexion();

        $stmt = $pdo->prepare("SELECT * FROM Personne WHERE identifiant = :identifiant");
        $stmt->execute([':identifiant' => $identifiant]);
        $personne = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$personne) {
            echo "<script>alert('Utilisateur non trouvé.');</script>";
            header('Location: index.php?reussi=utilisateurintrouvable');
            exit;
        }

        if (!password_verify($mdp, $personne['mdp'])) {
            echo "<script>alert('Mot de passe incorrect.');</script>";
            header('Location: index.php?reussi=motdepasseincorrect');
            exit;
        }

        header('Location: tableau-de-bord.php');
        exit;
    } catch (Exception $e) {
        echo "<script>alert('Erreur lors de la connexion : " . $e->getMessage() . "');</script>";
        header('Location: index.php');
        exit;
    }
}
?>