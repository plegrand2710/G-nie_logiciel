<?php
require_once __DIR__ . '/BaseDeDonnees.php';
require_once __DIR__ . '/CreneauxActivite.php';

class CreneauxActiviteReserve {
    private string $_date;
    private bool $_reserver;
    private $_pdo;
    private $_idCreneauxActiviteReserve;

    public function __construct($date) {
        $this->set_dateReservation($date);

        $bdd = new BaseDeDonnees();
        $this->_pdo = $bdd->getConnexion();
        $this->_reserver = false;
    }

    public function set_dateReservation($date): void {
        if (!$this->validerDate($date)) {
            throw new InvalidArgumentException("La date doit être valide au format YYYY-MM-DD.");
        }
        $this->_date = $date;
    }

    public function get_date(): string {
        return $this->_date;
    }

    public function getIdCreneauxActiviteReserve() {
        return $this->_idCreneauxActiviteReserve;
    }

    public function setId($id) {
        if (!is_int($id) || $id <= 0) {
            throw new InvalidArgumentException("L'id de creneauxActiviteReserve doit être un nombre supérieur à 0.");
        }
        $this->_id = $id;
    }

    public function set_reserver($reserver): void {
        if (!is_bool($reserver)) {
            throw new InvalidArgumentException("Le paramètre doit être valide un booleen.");
        }
        $this->_reserver = $reserver;
    }

    public function get_reserver(): bool {
        return $this->_reserver;
    }

    public function ajouterReservation($creneauxActivite): void {
        if (!$creneauxActivite instanceof CreneauxActivite) {
            throw new InvalidArgumentException("L'objet fourni n'est pas de type CreneauxActivite.");
        }

        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM CreneauxActivite WHERE idCreneauxActivite = :idCreneauxActivite");
        $stmt->execute([':idCreneauxActivite' => $creneauxActivite->get_IDCreneauxActivite()]);
        $count = $stmt->fetchColumn();

        if ($count == 0) {
            throw new Exception("L'ID du créneau d'activité n'existe pas dans la table CreneauxActivite.");
        }

        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM CreneauxActiviteReserve WHERE idCreneauxActivite = :idCreneauxActivite AND date = :date AND reserver = 1");
        $stmt->execute([
            ':idCreneauxActivite' => $creneauxActivite->get_IDCreneauxActivite(),
            ':date' => $this->_date
        ]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            throw new Exception("Ce créneau est déjà réservé pour cette date.");
        }

        $stmt = $this->_pdo->prepare("
            INSERT INTO CreneauxActiviteReserve (idCreneauxActivite, date, reserver)
            VALUES (:idCreneauxActivite, :date, :reserver)
        ");
        try {
            $stmt->execute([
                ':idCreneauxActivite' => $creneauxActivite->get_IDCreneauxActivite(),
                ':date' => $this->_date,
                ':reserver' => $this->_reserver? 1 : 0,
            ]);
            $this->_idCreneauxActiviteReserve = $this->_pdo->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Erreur lors de l'ajout du créneau réservé : " . $e->getMessage());
        }
    }

    public function annulerReservation(CreneauxActivite $creneauxActivite): void {
        if (!$creneauxActivite instanceof CreneauxActivite) {
            throw new InvalidArgumentException("L'objet fourni n'est pas de type CreneauxActivite.");
        }

        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM CreneauxActivite WHERE idCreneauxActivite = :idCreneauxActivite");
        $stmt->execute([':idCreneauxActivite' => $creneauxActivite->get_IDCreneauxActivite()]);
        $count = $stmt->fetchColumn();

        if ($count == 0) {
            throw new Exception("L'ID du créneau d'activité n'existe pas dans la table CreneauxActivite.");
        }

        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM CreneauxActiviteReserve WHERE idCreneauxActivite = :idCreneauxActivite AND reserver = false");
        $stmt->execute([':idCreneauxActivite' => $creneauxActivite->get_IDCreneauxActivite()]);
        $count = $stmt->fetchColumn();

        if ($count != 0) {
            throw new Exception("La réservation a déjà été annulée.");
        }

        $stmt = $this->_pdo->prepare("
            UPDATE CreneauxActiviteReserve
            SET reserver = false
            WHERE idCreneauxActivite = :idCreneauxActivite AND date = :date
        ");
        try {
            $stmt->execute([
                ':idCreneauxActivite' => $creneauxActivite->get_IDCreneauxActivite(),
                ':date' => $this->_date
            ]);
            $this->set_reserver(false);

        } catch (Exception $e) {
            throw new Exception("Erreur lors de l'annulation de la réservation : " . $e->getMessage());
        }
    }

    public function getCreneauxActiviteReserver(CreneauxActivite $creneauxActivite): array {
        if (!$creneauxActivite instanceof CreneauxActivite) {
            throw new InvalidArgumentException("L'objet fourni n'est pas de type CreneauxActivite.");
        }

        $stmt = $this->_pdo->prepare("
            SELECT CreneauxActivite.idCreneauxActivite, CreneauxActivite.idCreneau, CreneauxActivite.idActivite, CreneauxActiviteReserve.date
            FROM CreneauxActiviteReserve
            JOIN CreneauxActivite ON CreneauxActiviteReserve.idCreneauxActivite = CreneauxActivite.idCreneauxActivite
            WHERE CreneauxActivite.idCreneauxActivite = :idCreneauxActivite AND CreneauxActiviteReserve.date = :date AND CreneauxActiviteReserve.reserver = 1
        ");
        try {
            $stmt->execute([
                ':idCreneauxActivite' => $creneauxActivite->get_IDCreneauxActivite(),
                ':date' => $this->_date
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération des créneaux réservés : " . $e->getMessage());
        }
    }

    public function supprimerReservation(CreneauxActivite $creneauxActivite): void {
        if (!$creneauxActivite instanceof CreneauxActivite) {
            throw new InvalidArgumentException("L'objet fourni n'est pas de type CreneauxActivite.");
        }

        $stmt = $this->_pdo->prepare("
            DELETE FROM CreneauxActiviteReserve
            WHERE idCreneauxActivite = :idCreneauxActivite AND date = :date
        ");
        try {
            $stmt->execute([
                ':idCreneauxActivite' => $creneauxActivite->get_IDCreneauxActivite(),
                ':date' => $this->_date
            ]);
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la suppression de la réservation : " . $e->getMessage());
        }
    }

    
    private function validerDate($date): bool {
        $format = 'Y-m-d';
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
}
?>