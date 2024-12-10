<?php

require_once 'BaseDeDonnees.php';

class Creneau {
    private $_heureDebut;
    private $_heureFin;
    private $_duree;
    private $_id;
    private $_pdo;

    public function __construct($hDebut, $hFin) {
        $this->_pdo = (new BaseDeDonnees())->getConnexion();

        $this->setHeureDebut($hDebut);
        $this->setHeureFin($hFin);
        $this->_duree = $this->calculerDuree();
        echo "\n duree = " . $this->_duree;
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

    public function getPDO() {
        return $this->_pdo;
    }

    public function setPDO($pdo) {
        if (!$pdo instanceof PDO) {
            throw new InvalidArgumentException("Le pdo doit être un PDO.");
        }
        $this->_pdo = $pdo;
    }

    public function getHeureDebut() {
        return $this->_heureDebut;
    }

    public function setHeureDebut($heureDebut) {
        if ($heureDebut instanceof DateTime) {
            $this->_heureDebut = $heureDebut;
        }
        else if (is_string($heureDebut) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $heureDebut)) {
            if (!strtotime($heureDebut)) {
                throw new InvalidArgumentException("L'heure de début est invalide.");
            }
            $this->_heureDebut = $heureDebut;
        }
        else {
            throw new InvalidArgumentException("L'heure de début doit être un objet DateTime ou une chaîne au format HH:MM:SS.");
        }
    }

    public function getHeureFin() {
        return $this->_heureFin;
    }

    public function setHeureFin($heureFin) {
        if ($heureFin instanceof DateTime) {
            if ($heureFin <= $this->_heureDebut) {
                throw new InvalidArgumentException("L'heure de fin doit être supérieure à l'heure de début.");
            }
            $this->_heureFin = $heureFin;
        }
        else if (is_string($heureFin) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $heureFin)) {
            if (!strtotime($heureFin)) {
                throw new InvalidArgumentException("L'heure de fin est invalide.");
            }
            if (strtotime($heureFin) <= strtotime($this->_heureDebut)) {
                throw new InvalidArgumentException("L'heure de fin doit être supérieure à l'heure de début.");
            }
            $this->_heureFin = $heureFin;
        }
        else {
            throw new InvalidArgumentException("L'heure de fin doit être un objet DateTime ou une chaîne au format HH:MM:SS.");
        }
    }
    public function getDuree() {
        return $this->_duree;
    }

    private function calculerDuree() {
    if (is_string($this->_heureDebut)) {
        $this->_heureDebut = new DateTime($this->_heureDebut);
    }

    if (is_string($this->_heureFin)) {
        $this->_heureFin = new DateTime($this->_heureFin);
    }

    if (!$this->_heureDebut instanceof DateTime || !$this->_heureFin instanceof DateTime) {
        throw new InvalidArgumentException("Les heures doivent être des chaînes de caractères valides ou des objets DateTime.");
    }

    if ($this->_heureFin < $this->_heureDebut) {
        $this->_heureFin->modify('+1 day');
    }

    $interval = $this->_heureFin->diff($this->_heureDebut);


    return $interval->format('%H:%I');
}

    public function ajouterCreneauBDD() {
        try {
            $this->_pdo->beginTransaction();

            $stmt = $this->_pdo->prepare("
                SELECT COUNT(*) FROM Creneau 
                WHERE heure_debut = :heureDebut AND heure_fin = :heureFin
            ");
            $stmt->execute([
                ':heureDebut' => $this->_heureDebut->format("h:i:s"),
                ':heureFin' => $this->_heureFin->format("h:i:s")
            ]);
            
            $existingCreneauCount = $stmt->fetchColumn();
            
            if ($existingCreneauCount > 0) {
                echo "ça plante";
                $stmt = $this->_pdo->prepare("
                SELECT * FROM Creneau
                ");
                $stmt->execute();
                print_r($stmt->fetch(PDO::FETCH_ASSOC));
                throw new RuntimeException("Un créneau avec les mêmes horaires existe déjà dans la base.");
            }

            $stmt = $this->_pdo->prepare("
                INSERT INTO Creneau (heure_debut, heure_fin, duree) 
                VALUES (:heureDebut, :heureFin, :duree)
            ");
            $stmt->execute([
                ':heureDebut' => $this->_heureDebut->format("h:i:s"),
                ':heureFin' => $this->_heureFin->format("h:i:s"),
                ':duree' => $this->_duree,
            ]);
            
            $this->_id = $this->_pdo->lastInsertId();

            $this->_pdo->commit();
        } catch (PDOException $e) {
            $this->_pdo->rollBack();
            throw new RuntimeException("Erreur lors de l'insertion du créneau dans la base de données : " . $e->getMessage());
        }
    }

    public function lireCreneauBDD($id) {
        $stmt = $this->_pdo->prepare("SELECT * FROM Creneau WHERE idCreneau = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result;
        }
        
        throw new RuntimeException("Creneau non trouvé avec l'ID {$id}");
    }

    public function modifierCreneauBDD() {
        try {
            $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Creneau WHERE idCreneau = :idCreneau");
            $stmt->execute([':idCreneau' => $this->getId()]);
            $exists = $stmt->fetchColumn();
    
            if ($exists == 0) {
                throw new RuntimeException("Le créneau avec l'ID $this->_id n'existe pas.");
            }
    
            $stmt = $this->_pdo->prepare("UPDATE Creneau SET heure_debut = :heureDebut, heure_fin = :heureFin WHERE idCreneau = :idCreneau");
            $stmt->execute([
                ':heureDebut' => $this->getHeureDebut(),
                ':heureFin' => $this->getHeureFin(),
                ':idCreneau' => $this->getId(),
            ]);
    
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la modification du créneau dans la base de données : " . $e->getMessage());
        }
    }

    public function supprimerCreneauBDD() {
        try {
            $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Creneau WHERE idCreneau = :idCreneau");
            $stmt->execute([':idCreneau' => $this->_id]);
            $exists = $stmt->fetchColumn();
    
            if ($exists == 0) {
                throw new RuntimeException("Le créneau avec l'ID $this->_id n'existe pas.");
            }
    
            $stmt = $this->_pdo->prepare("DELETE FROM Creneau WHERE idCreneau = :idCreneau");
            $stmt->execute([':idCreneau' => $this->_id]);
    
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la suppression du créneau dans la base de données : " . $e->getMessage());
        }
    }
}
?>