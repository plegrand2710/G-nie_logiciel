<?php
require_once __DIR__ . '/BaseDeDonnees.php';

class GestionCreneauxActiviteReserve {
    private array $_paireCreneauActivite;
    private string $_date;
    private bool $_reserver;
    private $_pdo;

    public function __construct($date) {
        $this->set_dateReservation($date);

        $bdd = new BaseDeDonnees();
        $this->_pdo = $bdd->getConnexion();

        $this->_paireCreneauActivite = [];
        $this->_reserver = null;
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
    public function set_reserver($reserver): void {
        if (!is_bool($reserver)) {
            throw new InvalidArgumentException("Le paramètre doit être valide un booleen.");
        }
        $this->_reserver = $reserver;
    }
    public function get_reserver(): string {
        return $this->_reserver;
    }

    public function ajouterCreneauActiviteReserver($idCreneau, $idActivite): void {
        if (!is_int($idCreneau) || $idCreneau <= 0) {
            throw new InvalidArgumentException("L'ID du créneau doit être un entier positif.");
        }
        if (!is_int($idActivite) || $idActivite <= 0) {
            throw new InvalidArgumentException("L'ID de l'activité doit être un entier positif.");
        }

        $stmt = $this->_pdo->prepare("
            INSERT INTO gestionCreneauxActiviteReserve (idCreneauxActivite, date)
            SELECT idCreneauxActivite, :date
            FROM CreneauActivite
            WHERE idCreneau = :idCreneau AND idActivite = :idActivite
        ");
        try {
            $stmt->execute([
                ':date' => $this->_date,
                ':idCreneau' => $idCreneau,
                ':idActivite' => $idActivite,
            ]);
        } catch (Exception $e) {
            throw new Exception("Erreur lors de l'ajout du créneau réservé : " . $e->getMessage());
        }
    }

    public function getCreneauxActiviteReserver($idActivite): array {
        if (!is_int($idActivite) || $idActivite <= 0) {
            throw new InvalidArgumentException("L'ID de l'activité doit être un entier positif.");
        }

        $stmt = $this->_pdo->prepare("
            SELECT CreneauActivite.idCreneau, CreneauActivite.idActivite, gestionCreneauxActiviteReserve.date
            FROM gestionCreneauxActiviteReserve
            JOIN CreneauActivite ON gestionCreneauxActiviteReserve.idCreneauxActivite = CreneauActivite.idCreneauxActivite
            WHERE CreneauActivite.idActivite = :idActivite AND gestionCreneauxActiviteReserve.date = :date
        ");
        try {
            $stmt->execute([
                ':idActivite' => $idActivite,
                ':date' => $this->_date,
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération des créneaux réservés : " . $e->getMessage());
        }
    }

    private function validerDate($date): bool {
        $format = 'Y-m-d';
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
}