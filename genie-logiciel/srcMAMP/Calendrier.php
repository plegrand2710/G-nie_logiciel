<?php

require_once 'BaseDeDonnees.php';

class Calendrier {
    private $_idCalendrier;
    private $_horaireOuvertureSalle;
    private $_horaireFermetureSalle;
    private $_joursFermeture = [];
    private $_pdo;

    public function __construct($horaireOuvertureSalle = null, $horaireFermetureSalle = null) {
        $bdd = new BaseDeDonnees();
        $this->_pdo = $bdd->getConnexion();
        if ($horaireOuvertureSalle !== null && $horaireFermetureSalle !== null) {
            $this->setHoraireOuvertureSalle($horaireOuvertureSalle);
            $this->setHoraireFermetureSalle($horaireFermetureSalle);
        }
    }

    private function validerEtAffecterDate(&$propriete, $date) {
        $dateTime = DateTime::createFromFormat('H:i:s', $date);
        if ($dateTime && $dateTime->format('H:i:s') === $date) {
            $propriete = $date;
        } else {
            throw new InvalidArgumentException("Le format de la date est invalide. Attendu 'H:i:s'.");
        }
    }

    public function recupererCalendrierDepuisBDD($idCalendrier): void {
        $stmt = $this->_pdo->prepare("SELECT * FROM Calendrier WHERE idCalendrier = :idCalendrier");
        $stmt->execute([':idCalendrier' => $idCalendrier]);
        $calendrier = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($calendrier) {
            $this->_idCalendrier = $calendrier['idCalendrier'];
            $this->setHoraireOuvertureSalle($calendrier['horaire_ouverture']);
            $this->setHoraireFermetureSalle($calendrier['horaire_fermeture']);
            $this->recupererJoursFermetureDepuisBDD();
        } else {
            throw new Exception("Calendrier introuvable.");
        }
    }

    private function recupererJoursFermetureDepuisBDD(): void {
        $stmt = $this->_pdo->prepare("
            SELECT J.dateJour 
            FROM JourFermeture AS J
            INNER JOIN Fermeture AS F ON J.idJourFermeture = F.idJourFermeture
            WHERE F.idCalendrier = :idCalendrier
        ");
        $stmt->execute([':idCalendrier' => $this->_idCalendrier]);
        $jours = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($jours as $jour) {
            $this->_joursFermeture[] = $jour['dateJour'];
        }
    }

    public function ajouterCalendrierDansBDD(): void {
        $stmt = $this->_pdo->prepare("INSERT INTO Calendrier (horaire_ouverture, horaire_fermeture) VALUES (:horaireOuverture, :horaireFermeture)");
        $stmt->execute([
            ':horaireOuverture' => $this->_horaireOuvertureSalle,
            ':horaireFermeture' => $this->_horaireFermetureSalle
        ]);
        $this->_idCalendrier = $this->_pdo->lastInsertId();
    }

    public function mettreAJourCalendrierDansBDD(): void {
        if ($this->_idCalendrier === null) {
            throw new Exception("ID Calendrier non défini.");
        }

        $stmt = $this->_pdo->prepare("UPDATE Calendrier SET horaire_ouverture = :horaireOuverture, horaire_fermeture = :horaireFermeture WHERE idCalendrier = :idCalendrier");
        $stmt->execute([
            ':horaireOuverture' => $this->_horaireOuvertureSalle,
            ':horaireFermeture' => $this->_horaireFermetureSalle,
            ':idCalendrier' => $this->_idCalendrier
        ]);
    }

    public function supprimerCalendrierDansBDD(): void {
        if ($this->_idCalendrier === null) {
            throw new Exception("ID Calendrier non défini.");
        }

        $stmt = $this->_pdo->prepare("DELETE FROM Fermeture WHERE idCalendrier = :idCalendrier");
        $stmt->execute([':idCalendrier' => $this->_idCalendrier]);

        $stmt = $this->_pdo->prepare("DELETE FROM Calendrier WHERE idCalendrier = :idCalendrier");
        $stmt->execute([':idCalendrier' => $this->_idCalendrier]);
    }

    public function ajouterJourFermetureDansBDD($dateJour): void {
        if ($this->_idCalendrier === null) {
            throw new Exception("ID Calendrier non défini.");
        }

        $dateTime = DateTime::createFromFormat('Y-m-d', $dateJour);
        if (!$dateTime || $dateTime->format('Y-m-d') !== $dateJour) {
            throw new InvalidArgumentException("Le format de la date est invalide. Attendu 'Y-m-d'.");
        }
        
        $stmt = $this->_pdo->prepare("INSERT INTO JourFermeture (dateJour) VALUES (:dateJour)");
        $stmt->execute([
            ':dateJour' => $dateJour
        ]);
        $idFermeture = $this->_pdo->lastInsertId();

        $stmt = $this->_pdo->prepare("INSERT INTO Fermeture (idJourFermeture, idCalendrier) VALUES (:idJourFermeture, :idCalendrier)");
        $stmt->execute([
            ':idJourFermeture' => $idFermeture,
            ':idCalendrier' => $this->_idCalendrier
        ]);
    }

    public function supprimerJourFermetureDansBDD($dateJour): void {
        if ($this->_idCalendrier === null) {
            throw new Exception("ID Calendrier non défini.");
        }
    
        $stmt = $this->_pdo->prepare("SELECT idJourFermeture FROM JourFermeture WHERE dateJour = :dateJour");
        $stmt->execute([':dateJour' => $dateJour]);
        $jourFermeture = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$jourFermeture) {
            throw new Exception("Jour de fermeture introuvable.");
        }
    
        try{
            $stmt = $this->_pdo->prepare("DELETE FROM JourFermeture WHERE idJourFermeture = :idJourFermeture");
            $stmt->execute([
                ':idJourFermeture' => $jourFermeture['idJourFermeture'],
            ]);
        }catch(Exception $e){
            throw new Exception("aucune ligne sélectionnée");
        }
        
     }

    public function getHoraireOuvertureSalle() {
        return $this->_horaireOuvertureSalle;
    }

    public function setHoraireOuvertureSalle($horaireOuvertureSalle) {
        $this->validerEtAffecterDate($this->_horaireOuvertureSalle, $horaireOuvertureSalle);
    }

    public function getHoraireFermetureSalle() {
        return $this->_horaireFermetureSalle;
    }

    public function setHoraireFermetureSalle($horaireFermetureSalle) {
        $this->validerEtAffecterDate($this->_horaireFermetureSalle, $horaireFermetureSalle);
    }

    public function getJoursFermeture() {
        $this->recupererJoursFermetureDepuisBDD();
        return $this->_joursFermeture;
    }

    public function setJoursFermeture($joursFermeture) {
        $this->_joursFermeture = $joursFermeture;
    }

    public function getId(): int {
        return $this->_idCalendrier;
    }

    public function setId($i): void {
        if (!is_int($i) || $i <= 0) {
            throw new InvalidArgumentException("L'ID du calendrier doit être un entier positif.");
        }
        $this->_idCalendrier = $i;
    }
}
?>