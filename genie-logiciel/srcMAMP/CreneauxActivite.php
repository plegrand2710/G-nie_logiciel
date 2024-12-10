<?php

require_once 'BaseDeDonnees.php';

class CreneauxActivite {
    private int $_idCreneauxActivite;
    private int $_idCreneau;
    private int $_idActivite;
    private $_pdo;
    private static $_calendrier;

    function __construct($idCreneau, $idActivite) {
        $bdd = new BaseDeDonnees();
        $this->_pdo = $bdd->getConnexion();
        $this->set_idCreneau($idCreneau);
        $this->set_idActivite($idActivite);
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
    
    private function isDureeInvalid(DateInterval $dureeActivite): bool{
        $minutes = $dureeActivite->h * 60 + $dureeActivite->i;
        return $minutes < 30 || $minutes > 300; 
    }

    public function getDureeActivite(int $idActivite): DateInterval {
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
    
    public function get_CalendrierBDD(): void {
        $stmt = $this->_pdo->query("SELECT * FROM Calendrier LIMIT 1");
        $calendrier = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$calendrier) {
            throw new Exception("Calendrier introuvable.");
        }
        self::$_calendrier = new Calendrier($calendrier['horaire_ouverture'], $calendrier['horaire_fermeture']);
        self::$_calendrier->setID($calendrier['idCalendrier']);
    }
    
    public function getCreneauId(DateTime $heureDebut, DateTime $heureFin, int $idActivite): int {
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
    
    public function ajouterCreneauxActivite(): void {
        try {
            $stmt = $this->_pdo->prepare("
                INSERT INTO CreneauxActivite (idCreneau, idActivite, idCalendrier) 
                VALUES (:idCreneau, :idActivite, :idCalendrier)
            ");
            $stmt->execute([
                ':idCreneau' => $this->_idCreneau,
                ':idActivite' => $this->_idActivite,
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
}
?>