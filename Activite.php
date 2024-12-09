<?php

require_once 'BaseDeDonnees.php';

class Activite {
    private $_nom;
    private $_tarif;
    private $_duree;
    private $_pdo;
    private $_id;

    public function __construct($nom, $tarif, $duree) {
        $this->setNom($nom);
        $this->setTarif($tarif);
        $this->setDuree($duree);

        $bdd = new BaseDeDonnees();
        $this->_pdo = $bdd->getConnexion();
    }

    public function getNom() {
        return $this->_nom;
    }

    public function setNom($nom) {
        if (!is_string($nom) || empty($nom)) {
            throw new InvalidArgumentException("Le nom doit être une chaîne de caractères non vide.");
        }
        $this->_nom = $nom;
    }

    public function getTarif() {
        return $this->_tarif;
    }

    public function setTarif($tarif) {
        if (!is_numeric($tarif) || $tarif <= 0) {
            throw new InvalidArgumentException("Le tarif doit être un nombre supérieur à 0.");
        }
        $this->_tarif = $tarif;
    }

    public function getDuree() {
        return $this->_duree;
    }

    public function setDuree($duree) {
        if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $duree)) {
            throw new InvalidArgumentException("La durée doit être au format HH:MM:SS.");
        }
        $this->_duree = $duree;
    }

    public function getId() {
        return $this->_id;
    }

    public function setId($id) {
        if (!is_int($id) || $id <= 0) {
            throw new InvalidArgumentException("L'id doit être un nombre supérieur à 0.");
        }
        $this->_id = $id;
    }

    public function ajouterActiviteBDD() {
        $stmt = $this->_pdo->prepare("
            INSERT INTO Activite (nom, tarif, duree) 
            VALUES (:nom, :tarif, :duree)
        ");
        $stmt->execute([
            ':nom' => $this->_nom,
            ':tarif' => $this->_tarif,
            ':duree' => $this->_duree
        ]);
        $this->_id = $this->_pdo->lastInsertId();
    }

    public function mettreAJourActiviteBDD() {
        if (empty($this->_id) || $this->_id <= 0) {
            throw new RuntimeException("ID non défini ou invalide pour mettre à jour l'activité.");
        }
    
        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Activite WHERE idActivite = :id");
        $stmt->execute([':id' => $this->_id]);
        $activityExists = $stmt->fetchColumn();
    
        if ($activityExists == 0) {
            throw new RuntimeException("Aucune activité trouvée avec l'ID {$this->_id}. Impossible de mettre à jour.");
        }
    
        $stmt = $this->_pdo->prepare("
            UPDATE Activite
            SET tarif = :tarif, duree = :duree
            WHERE idActivite = :id
        ");
        $stmt->execute([
            ':id' => $this->_id,
            ':tarif' => $this->_tarif,
            ':duree' => $this->_duree
        ]);
    }
    public function supprimerActiviteBDD() {
        if (empty($this->_id)) {
            throw new RuntimeException("ID non défini pour supprimer l'activité.");
        }

        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Activite WHERE idActivite = :id");
        $stmt->execute([':id' => $this->_id]);
        $count = $stmt->fetchColumn();

        if ($count == 0) {
            throw new RuntimeException("L'activité avec l'ID {$this->_id} n'existe pas.");
        }

        $stmt = $this->_pdo->prepare("
            DELETE FROM Activite WHERE idActivite = :id
        ");
        $stmt->execute([':id' => $this->_id]);
    }

    public function lireActiviteBDD() {
        if (empty($this->_id)) {
            throw new RuntimeException("ID non défini pour lire l'activité.");
        }
        $stmt = $this->_pdo->prepare("SELECT * FROM Activite WHERE idActivite = :id");
        $stmt->execute([':id' => $this->_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function lireToutesActivitesBDD() {
        $stmt = $this->_pdo->prepare("SELECT * FROM Activite");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function verifierDureeActivite(): bool {
        $duree = $this->_duree; 
    
        list($hours, $minutes, $seconds) = explode(":", $duree);
        $totalMinutes = ($hours * 60) + $minutes;
    
        if ($totalMinutes >= 30 && $totalMinutes <= 300) {
            return true;
        } else {
            return false;
        }
    }
}

?>