<?php
include 'require.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idRIB = (int)$_POST['idRIB'];
    $idUtilisateur = $_SESSION['idUtilisateur'];

    $bdd = new BaseDeDonnees();
    $pdo = $bdd->getConnexion();

    try {
        $pdo->beginTransaction();

        $dateDebut = new DateTime();
        $cotisation = new Cotisation($dateDebut, $idUtilisateur);

        $pdo->commit();

        unset($_SESSION['idUtilisateur']);
        header("Location: index.php?reussi=oui");
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erreur de finalisation : " . $e->getMessage());
        header("Location: paiement-cotisation.php?erreur=oui");
    }
}
?>