<?php
session_start();
include 'require.php';

if (!isset($_SESSION['idPersonne'])) {
    header('Location: accueil.php?pb=connexion');
    exit;
}

$date = $_POST['date'];
$heureDebut = $_POST['heureDebut'];
$heureFin = $_POST['heureFin'];
$activiteId = $_POST['activite'];
$tarif = $_POST['tarif'];
$rib = $_POST['rib']; 

$idUtilisateur = $_SESSION['idPersonne'];

$bdd = new BaseDeDonnees();
$pdo = $bdd->getConnexion();

$stmt = $pdo->query("SELECT * FROM Calendrier LIMIT 1");
$calendrierData = $stmt->fetch(PDO::FETCH_ASSOC);
$calendrier = new Calendrier($calendrierData['horaire_ouverture'], $calendrierData['horaire_fermeture']);


$stmt = $pdo->prepare("
    SELECT ca.idCreneauxActivite
    FROM CreneauxActivite ca
    JOIN Creneau c ON ca.idCreneau = c.idCreneau
    WHERE ca.idActivite = :idActivite
    AND c.heure_debut = :heureDebut
    AND c.heure_fin = :heureFin
");
$stmt->execute([
    ':idActivite' => $activiteId,
    ':heureDebut' => $heureDebut,
    ':heureFin' => $heureFin
]);


$creneauActivite = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$creneauActivite) {
    die("Aucun créneau d'activité trouvé pour cette activité à l'heure sélectionnée.");
}

$stmt = $pdo->prepare("
    INSERT INTO CreneauxActiviteReserve (idCreneauxActivite, date, reserver)
    VALUES (:idCreneauxActivite, :date, 0)
");
$stmt->execute([
    ':idCreneauxActivite' => $creneauActivite['idCreneauxActivite'],
    ':date' => $date
]);

$idCreneauxActiviteReserve = $pdo->lastInsertId();

try {
    $gestionUtilisateur = new GestionUtilisateur($calendrierData['idCalendrier']);
    
    $gestionUtilisateur->reserver((int)$idCreneauxActiviteReserve, (int)$idUtilisateur); 
    
    error_log("je suis là");
    header('Location: confirmation-reservation.php'); 
    exit;
} catch (Exception $e) {
    die("Erreur lors de la réservation : " . $e->getMessage());
}
?>