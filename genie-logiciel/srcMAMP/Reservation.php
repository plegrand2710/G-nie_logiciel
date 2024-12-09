<?php
require_once __DIR__ . '/BaseDeDonnees.php';

class Reservation {
    private $_idReservation;
    private $_CreneauActiviteReserve;
    private $_idPersonne;
    private $_statut;
    private $_dateExpiration;
    private $_pdo;

    public function __construct($idCreneauxActiviteReserve, $idPersonne) {
        $bdd = new BaseDeDonnees();
        $this->_pdo = $bdd->getConnexion();
        $this->setCreneauActiviteReserve($idCreneauxActiviteReserve);
        $this->setIdPersonne($idPersonne);
        $this->_statut = 'en attente';
        $this->_dateExpiration = new DateTime();
        $this->_dateExpiration->modify('+24 hours');

    }

    public function getIdReservation() {
        return $this->_idReservation;
    }

    public function getCreneauActiviteReserve() {
        return $this->_CreneauActiviteReserve;
    }

    public function getIdPersonne() {
        return $this->_idPersonne;
    }

    public function getStatut() {
        return $this->_statut;
    }

    public function getDateExpiration() {
        return $this->_dateExpiration;
    }

    public function setIdReservation($id) {
        if (!is_int($id) || $id <= 0) {
            throw new InvalidArgumentException("L'id de réservation doit être un nombre supérieur à 0.");
        }
        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Reservation WHERE idReservation = :idReservation");
        $stmt->execute([':idReservation' => $id]);
        $count = $stmt->fetchColumn();
    
        if ($count == 0) {
            throw new RuntimeException("La réservation avec l'ID {$id} n'existe pas.");
        }
    
        $this->_idReservation = $id;
    }

    public function setCreneauActiviteReserve($idCreneauxActiviteReserve) {
        if (!is_int($idCreneauxActiviteReserve) || $idCreneauxActiviteReserve <= 0) {
            throw new InvalidArgumentException("L'ID de gestion du créneau-activité doit être un entier positif.");
        }
        $this->_CreneauActiviteReserve = $idCreneauxActiviteReserve;
    }

    public function setIdPersonne($idPersonne) {
        if (!is_int($idPersonne) || $idPersonne <= 0) {
            throw new InvalidArgumentException("L'ID de la personne doit être un entier positif.");
        }

        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Personne WHERE idPersonne = :idPersonne");
        $stmt->execute([':idPersonne' => $idPersonne]);
        if ($stmt->fetchColumn() == 0) {
            throw new RuntimeException("L'utilisateur avec l'ID {$idPersonne} n'existe pas dans la base de données.");
        }

        $this->_idPersonne = $idPersonne;
    }

    public function setStatut($statut) {
        $statutsValides = ['en attente', 'confirmée', 'annulée', 'expirée'];
        if (!in_array($statut, $statutsValides)) {
            throw new InvalidArgumentException("Le statut doit être l'un des suivants : " . implode(', ', $statutsValides));
        }

        if ($statut === 'confirmée' && $this->_statut === 'en attente') {
            $this->confirmerReservation();
        }

        if ($statut === 'annulée') {
            $this->annulerReservation();
        }

        if ($this->_statut === 'confirmée' && $statut === 'en attente') {
            throw new LogicException("Une réservation confirmée ne peut pas revenir en attente.");
        }

        $this->_statut = $statut;
    }

    public function setDateExpiration($dateExpiration): void {
        if (!$dateExpiration instanceof DateTime && !is_string($dateExpiration)) {
            throw new InvalidArgumentException("La date d'expiration doit être une instance de DateTime ou une chaîne au format 'Y-m-d H:i:s'.");
        }

        if (is_string($dateExpiration)) {
            $date = DateTime::createFromFormat('Y-m-d H:i:s', $dateExpiration);
            if (!$date || $date->format('Y-m-d H:i:s') !== $dateExpiration) {
                throw new InvalidArgumentException("La chaîne de date d'expiration n'est pas valide ou n'est pas au format 'Y-m-d H:i:s'.");
            }
            $this->_dateExpiration = $date;
        } else {
            $this->_dateExpiration = $dateExpiration;
        }
    }

    public function estExpirée() {
        $now = new DateTime();
        if ($now > $this->_dateExpiration) {
            $this->_statut = 'expirée';
            return true;
        }
        return false;
    }

    public function confirmerReservation() {
        if ($this->_statut !== 'en attente') {
            return false;
        }

        if ($this->estExpirée()) {
            return false;
        }

        $this->_statut = 'confirmée';
        return true;
    }

    public function annulerReservation() {
        if ($this->_statut === 'annulée') {
            throw new LogicException("La réservation est déjà annulée.");
        }

        $this->_statut = 'annulée';

        $stmt = $this->_pdo->prepare("UPDATE Reservation SET statut = :statut WHERE idReservation = :idReservation");
        $stmt->execute([
            ':statut' => $this->_statut,
            ':idReservation' => $this->_idReservation
        ]);

        return new DateTime();
    }
    public function getHeureDebut(): string {
        $stmt = $this->_pdo->prepare("
            SELECT c.heure_debut 
            FROM CreneauxActiviteReserve gcar
            JOIN CreneauxActivite ca ON gcar.idCreneauxActivite = ca.idCreneauxActivite
            JOIN Creneau c ON ca.idCreneau = c.idCreneau
            WHERE gcar.idCreneauxActiviteReserve = :idCreneauxActiviteReserve
        ");
        $stmt->execute([':idCreneauxActiviteReserve' => $this->_CreneauActiviteReserve]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$result) {
            throw new RuntimeException("Impossible de trouver l'heure de début pour le créneau associé à cette réservation.");
        }
    
        return $result['heure_debut'];
    }
    public function getCreneau() {
        $stmt = $this->_pdo->prepare("
            SELECT c.* 
            FROM CreneauxActiviteReserve gcar
            JOIN CreneauxActivite ca ON gcar.idCreneauxActivite = ca.idCreneauxActivite
            JOIN Creneau c ON ca.idCreneau = c.idCreneau
            WHERE gcar.idCreneauxActiviteReserve = :idCreneauxActiviteReserve
        ");
        $stmt->execute([':idCreneauxActiviteReserve' => $this->_CreneauActiviteReserve]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            throw new RuntimeException("Impossible de trouver le créneau pour cette réservation.");
        }

        return $result;
    }

    public function getActivite() {
        $stmt = $this->_pdo->prepare("
            SELECT ca.* 
            FROM CreneauxActiviteReserve gcar
            JOIN CreneauxActivite ca ON gcar.idCreneauxActivite = ca.idCreneauxActivite
            WHERE gcar.idCreneauxActiviteReserve = :idCreneauxActiviteReserve
        ");
        $stmt->execute([':idCreneauxActiviteReserve' => $this->_CreneauActiviteReserve]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            throw new RuntimeException("Impossible de trouver l'activité pour cette réservation.");
        }

        return $result;
    }

    public function ajouterDansLaBDD(): void {
        if (!is_int($this->_CreneauActiviteReserve) || $this->_CreneauActiviteReserve <= 0) {
            throw new InvalidArgumentException("L'ID de créneau-activité réservé doit être un entier positif.");
        }
 
    
        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Personne WHERE idPersonne = :idPersonne");
        $stmt->execute([':idPersonne' => $this->_idPersonne]);
        if ($stmt->fetchColumn() == 0) {
            throw new RuntimeException("L'utilisateur avec l'ID {$this->_idPersonne} n'existe pas dans la base de données.");
        }
    
        try {
            $stmt = $this->_pdo->prepare("
                INSERT INTO Reservation (idCreneauxActiviteReserve, idPersonne, statut, date_expiration)
                VALUES (:idCreneauxActiviteReserve, :idPersonne, :statut, :dateExpiration)
            ");
            $stmt->execute([
                ':idCreneauxActiviteReserve' => $this->_CreneauActiviteReserve,
                ':idPersonne' => $this->_idPersonne,
                ':statut' => $this->_statut,
                ':dateExpiration' => $this->_dateExpiration->format('Y-m-d H:i:s'),
            ]);
    
            $this->_idReservation = $this->_pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de l'ajout de la réservation dans la base de données : " . $e->getMessage());
        }
    }
}
?>
