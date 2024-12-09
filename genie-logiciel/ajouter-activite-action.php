<?php
session_start();
include 'require.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $tarif = trim($_POST['tarif']);
    $duree = trim($_POST['duree']);

    try {
        if (preg_match("/^(\d{1,2}):(\d{2})$/", $duree, $matches)) {
            $hours = str_pad($matches[1], 2, '0', STR_PAD_LEFT); 
            $minutes = $matches[2];
            $seconds = "00"; 
        }
        elseif (preg_match("/^(\d{2}):(\d{2})$/", $duree, $matches)) {
            $hours = "00"; 
            $minutes = $matches[1];
            $seconds = $matches[2];
        }
        elseif (preg_match("/^(\d{1,2}):(\d{2}):(\d{2})$/", $duree, $matches)) {
            $hours = $matches[1];
            $minutes = $matches[2];
            $seconds = $matches[3];
        } else {
            throw new InvalidArgumentException("Le format de la durée est invalide. Le format attendu est HH:MM:SS ou HH:MM.");
        }
    
        $formattedDuree = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
    
        error_log("Durée convertie : $formattedDuree"); 
    
    } catch (Exception $e) {
        error_log("Erreur : " . $e->getMessage());
    }
    

    if (empty($nom)) {
        error_log("Le nom de l'activité est obligatoire.");
    }

    if (empty($tarif) || !is_numeric($tarif) || $tarif <= 0) {
        error_log("Le tarif doit être un nombre positif.");
    }

    if (empty($formattedDuree) || !preg_match("/^\d{2}:\d{2}:\d{2}$/", $formattedDuree)) {
        error_log("Le format de la durée est invalide. Il doit être au format HH:MM:SS.");
    }

    try {
        $bdd = new BaseDeDonnees();
        $pdo = $bdd->getConnexion();

        $stmt = $pdo->query("SELECT * FROM Calendrier LIMIT 1");
        $calendrierData = $stmt->fetch(PDO::FETCH_ASSOC);
        $calendrier = new Calendrier($calendrierData['horaire_ouverture'], $calendrierData['horaire_fermeture']);

        $gestionSuperUtilisateur = new GestionSuperUtilisateur($calendrierData['idCalendrier']);
        $gestionSuperUtilisateur->creerActivite($nom, $tarif, $formattedDuree);

        header("Location: tableau-de-bord.php?message=Activité ajoutée avec succès");
        exit;
    } catch (Exception $e) {
        error_log("Erreur lors de l'ajout de l'activité : " . $e->getMessage());
        header("Location: ajouter-activite.php");
        exit;
    }
}
else{
    error_log("probleme");
}
?>