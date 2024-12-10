<?php
session_start();
include 'require.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idRIB = (int)$_POST['idRIB'];
    $idPersonne = $_SESSION['idPersonne'];

    $bdd = new BaseDeDonnees();
    $pdo = $bdd->getConnexion();

    $stmt = $pdo->prepare("SELECT idUtilisateur FROM Utilisateur WHERE idPersonne = :idPersonne");
    $stmt->execute([':idPersonne' => $idPersonne]);
    $idUtilisateur = $stmt->fetchColumn();

    try {
        $pdo->beginTransaction();

        $dateDebut = new DateTime();
        $cotisation = new Cotisation($dateDebut, (int)$idUtilisateur);
        $cotisation->effectuerPaiementCotisation();

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