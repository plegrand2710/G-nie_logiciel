<?php
include 'require.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? 'Utilisateur';
    $nom = trim($_POST['nom'] ?? '');
    $identifiant = trim($_POST['identifiant'] ?? '');
    $mdp = trim($_POST['mdp'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $numTel = trim($_POST['numTel'] ?? '');

    if (empty($nom) || empty($identifiant) || empty($mdp) || empty($email) || empty($numTel)) {
        echo "<script>alert('Tous les champs sont obligatoires.');</script>";
        header("Location: inscription.php?type=" . urlencode($type));
        exit;
    }

    try {
        if ($type === 'Utilisateur') {
            $utilisateur = new Utilisateur($nom, $identifiant, $mdp, $email, $numTel);
        } elseif ($type === 'Moderateur') {
            $moderateur = new Moderateur($nom, $identifiant, $mdp, $email, $numTel);
        } else {
            throw new InvalidArgumentException("Type d'inscription invalide.");
        }
        header('Location: index.php?reussi=oui');
    } catch (Exception $e) {
        error_log("Erreur d'inscription : " . $e->getMessage());
        header("Location: inscription.php?type=" . urlencode($type)."&reussi=non");
    }
}
?>