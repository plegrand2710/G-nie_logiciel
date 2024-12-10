<?php

require_once 'BaseDeDonnees.php';

class CreneauxActivite {
    private int $_idCreneauxActivite;
    private int $_idCreneau;
    private int $_idActivite;
    private $_pdo;
    private static $_calendrier;

    function __construct($idCreneau, $idActivite) {
        $this->set_idCreneau($idCreneau);
        $this->set_idActivite($idActivite);
        $bdd = new BaseDeDonnees();
        $this->_pdo = $bdd->getConnexion();
        $this->get_CalendrierBDD();
    }

    public function set_idCreneauxActivite($idCreneauxActivite): void {
        if (!is_int($idCreneauxActivite) || $idCreneauxActivite <= 0) {
            throw new InvalidArgumentException("L'ID du créneau-activité doit être un entier positif.");
        }
        $this->_idCreneauxActivite = $idCreneauxActivite;
    }
    
    public function set_idCreneau($idCreneau): void {
        if (!is_int($idCreneau) || $idCreneau <= 0) {
            throw new InvalidArgumentException("L'ID du créneau doit être un entier positif.");
        }
        $this->_idCreneau = $idCreneau;
    }
    
    public function set_idActivite($idActivite): void {
        if (!is_int($idActivite) || $idActivite <= 0) {
            throw new InvalidArgumentException("L'ID de l'activité doit être un entier positif.");
        }
        $this->_idActivite = $idActivite;
    }
    
    public function get_idCreneauxActivite(): int {
        return $this->_idCreneauxActivite;
    }

    public function get_idCreneau(): int {
        return $this->_idCreneau;
    }

    public function get_idActivite(): int {
        return $this->_idActivite;
    }

    public function get_Calendrier(): Calendrier {
        return self::$_calendrier;
    }
    public function genererCreneauxPourActivite(int $idActivite, $dureeActivite = null): void {
        try {
            if($dureeActivite == null){
                $dureeActivite = $this->getDureeActivite($idActivite);
            }
            
            if ($this->isDureeInvalid($dureeActivite)) {
                throw new Exception("La durée de l'activité doit être comprise entre 30 minutes et 5 heures.");
            }

            $heureOuverture = new DateTime(self::$_calendrier->getHoraireOuvertureSalle());
            $heureFermeture = new DateTime(self::$_calendrier->getHoraireFermetureSalle());
    
            $heureActuelle = clone $heureOuverture;
            while ($heureActuelle < $heureFermeture) {
                $heureFin = (clone $heureActuelle)->add($dureeActivite);
    
                if ($heureFin > $heureFermeture) {
                    break;
                }
                    $idCreneau = $this->getCreneauId($heureActuelle, $heureFin, $idActivite);
                    $this->ajouterCreneauxActivite($idCreneau, $idActivite);
    
                $heureActuelle = $heureFin;
            }
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la génération des créneaux : " . $e->getMessage());
        }
    }
    
    private function isDureeInvalid(DateInterval $dureeActivite): bool{
    $minutes = $dureeActivite->h * 60 + $dureeActivite->i;
    return $minutes < 30 || $minutes > 300; 
}

    private function getDureeActivite(int $idActivite): DateInterval {
        $stmt = $this->_pdo->prepare("SELECT duree FROM Activite WHERE idActivite = :idActivite");
        $stmt->execute([':idActivite' => $idActivite]);
        $activite = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$activite) {
            throw new Exception("Activité introuvable.");
        }
    
        $duree = $activite['duree'];

        list($hours, $minutes, $seconds) = explode(":", $duree);

        return new DateInterval('PT' . $hours . 'H' . $minutes . 'M' . $seconds . 'S');

    }
    
    private function get_CalendrierBDD(): void {
        $stmt = $this->_pdo->query("SELECT * FROM Calendrier LIMIT 1");
        $calendrier = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$calendrier) {
            throw new Exception("Calendrier introuvable.");
        }
        self::$_calendrier = new Calendrier($calendrier['horaire_ouverture'], $calendrier['horaire_fermeture']);
        self::$_calendrier->setID($calendrier['idCalendrier']);
    }
    
    private function getCreneauId(DateTime $heureDebut, DateTime $heureFin, int $idActivite): int {
        $stmt = $this->_pdo->prepare("SELECT idCreneau FROM Creneau WHERE heure_debut = :heureDebut AND heure_fin = :heureFin");
        $stmt->execute([
            ':heureDebut' => $heureDebut->format('H:i:s'),
            ':heureFin' => $heureFin->format('H:i:s'),
        ]);
        $creneau = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$creneau) {
            $stmt = $this->_pdo->prepare("INSERT INTO Creneau (heure_debut, heure_fin, duree) VALUES (:heureDebut, :heureFin, :duree)");
            $stmt->execute([
                ':heureDebut' => $heureDebut->format('H:i:s'),
                ':heureFin' => $heureFin->format('H:i:s'),
                ':duree' => $this->getDureeActivite($idActivite)->format('%H:%I:%S'),
            ]);
            return $this->_pdo->lastInsertId();
        } else {
            return $creneau['idCreneau'];
        }
    }
    
    public function ajouterCreneauxActivite(int $idCreneau, int $idActivite): void {
        try {
            $stmt = $this->_pdo->prepare("
                INSERT INTO CreneauxActivite (idCreneau, idActivite, idCalendrier) 
                VALUES (:idCreneau, :idActivite, :idCalendrier)
            ");
            $stmt->execute([
                ':idCreneau' => $idCreneau,
                ':idActivite' => $idActivite,
                ':idCalendrier' => self::$_calendrier->getId(),
            ]);
            $this->_idCreneauxActivite = $this->_pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de l'ajout du créneau activité : " . $e->getMessage());
        }
    }

    public function getCreneauxActiviteById(int $idCreneauxActivite): array {
        try {
            $stmt = $this->_pdo->prepare("SELECT * FROM CreneauxActivite WHERE idCreneauxActivite = :idCreneauxActivite");
            $stmt->execute([':idCreneauxActivite' => $idCreneauxActivite]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la récupération du créneau activité : " . $e->getMessage());
        }
    }

    public function getAllCreneauxActivite(): array {
        try {
            $stmt = $this->_pdo->prepare("SELECT * FROM CreneauxActivite");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la récupération des créneaux activités : " . $e->getMessage());
        }
    }

    public function modifierCreneauxActivite(int $idCreneauxActivite): void {
        try {
            $stmt = $this->_pdo->prepare("
                UPDATE CreneauxActivite 
                SET idCreneau = :idCreneau, idActivite = :idActivite, idCalendrier = :idCalendrier
                WHERE idCreneauxActivite = :idCreneauxActivite
            ");
            $stmt->execute([
                ':idCreneau' => $this->_idCreneau,
                ':idActivite' => $this->_idActivite,
                ':idCalendrier' => self::$_calendrier->getId(),
                ':idCreneauxActivite' => $idCreneauxActivite,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la mise à jour du créneau activité : " . $e->getMessage());
        }
    }

    public function supprimerCreneauxActivite(int $idCreneauxActivite): void {
        try {
            $stmt = $this->_pdo->prepare("DELETE FROM CreneauxActivite WHERE idCreneauxActivite = :idCreneauxActivite");
            $stmt->execute([':idCreneauxActivite' => $idCreneauxActivite]);
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la suppression du créneau activité : " . $e->getMessage());
        }
    }

    public function verifierEtRegenererCreneaux(int $idActivite): void {
        try {
            $dureeActivite = $this->getDureeActiviteFromDB($idActivite);
 
            $stmt = $this->_pdo->prepare("
                SELECT C.idCreneau, C.heure_debut, C.heure_fin
                FROM Creneau AS C
                INNER JOIN CreneauxActivite AS CA ON CA.idCreneau = C.idCreneau
                WHERE CA.idActivite = :idActivite
            ");
            $stmt->execute([':idActivite' => $idActivite]);
            $creneaux = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($creneaux as $creneau) {
                $heureDebut = new DateTime($creneau['heure_debut']);
                $heureFin = new DateTime($creneau['heure_fin']);
                $dureeCreneau = $heureDebut->diff($heureFin);

                if ($dureeCreneau->h != $dureeActivite->h || $dureeCreneau->i != $dureeActivite->i) {
                    $this->supprimerCreneauxActivite($creneau['idCreneau']);

                    $this->genererCreneauxPourActivite($idActivite);
                }
            }
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la vérification et génération des créneaux : " . $e->getMessage());
        }
    }

    private function getDureeActiviteFromDB(int $idActivite): DateInterval {
        $stmt = $this->_pdo->prepare("SELECT duree FROM Activite WHERE idActivite = :idActivite");
        $stmt->execute([':idActivite' => $idActivite]);
        $activite = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$activite) {
            throw new Exception("Activité introuvable.");
        }

        $duree = $activite['duree'];

        list($hours, $minutes, $seconds) = explode(":", $duree);

        return new DateInterval('PT' . $hours . 'H' . $minutes . 'M' . $seconds . 'S');
    }
}
?>