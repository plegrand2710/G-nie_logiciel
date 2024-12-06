<?php
require_once 'BaseDeDonnees.php';


class Reservation {
    private $_id;
    private $_gestionCreneauActiviteReserve;
    private $_personne;
    private $_statut;
    private $_dateExpiration;
    private $_pdo;

    public function __construct($idGestionCreneauActiviteReserve, $personne) {
        if (!is_int($idGestionCreneauActiviteReserve) || $idGestionCreneauActiviteReserve <= 0) {
            throw new InvalidArgumentException("L'ID de gestion du créneau-activité doit être un entier positif.");
        }

        if (!$personne instanceof Personne) {
            throw new InvalidArgumentException("La personne doit être une instance de la classe Personne.");
        }

        $this->_gestionCreneauActiviteReserve = $idGestionCreneauActiviteReserve;
        $this->_personne = $personne;
        $this->_statut = 'en attente';
        $this->_id = null;
        $this->_dateExpiration = new DateTime();
        $this->_dateExpiration->modify('+24 hours');

        $bdd = new BaseDeDonnees();
        $this->_pdo = $bdd->getConnexion();
    }

    public function getId() {
        return $this->_id;
    }
    public function getGestionCreneauActiviteReserve() {
        return $this->_gestionCreneauActiviteReserve;
    }

    public function getPersonne() {
        return $this->_personne;
    }

    public function getStatut() {
        return $this->_statut;
    }

    public function getDateExpiration() {
        return $this->_dateExpiration;
    }
    
    public function setId($id) {
        if (!is_int($id) || $id <= 0) {
            throw new InvalidArgumentException("L'id doit être un nombre supérieur à 0.");
        }
        $this->_id = $id;
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
    
    public function setGestionCreneauActiviteReserve($idGestionCreneauActiviteReserve) {
        if (!is_int($idGestionCreneauActiviteReserve) || $idGestionCreneauActiviteReserve <= 0) {
            throw new InvalidArgumentException("L'ID de gestion du créneau-activité doit être un entier positif.");
        }
        $this->_gestionCreneauActiviteReserve = $idGestionCreneauActiviteReserve;
    }

    public function setPersonne($personne) {
        if (!$personne instanceof Personne) {
            throw new InvalidArgumentException("L'utilisateur doit être une instance de la classe Personne.");
        }

        $this->_personne = $personne;
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
    
        return new DateTime();
    }

    public function getHeureDebut(): string {
        $stmt = $this->_pdo->prepare("
            SELECT c.heure_debut 
            FROM gestionCreneauxActiviteReserve gcar
            JOIN CreneauxActivite ca ON gcar.idCreneauxActivite = ca.idCreneauxActivite
            JOIN Creneau c ON ca.idCreneau = c.idCreneau
            WHERE gcar.idGestion = :idGestion
        ");
        $stmt->execute([':idGestion' => $this->_gestionCreneauActiviteReserve]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$result) {
            throw new RuntimeException("Impossible de trouver l'heure de début pour le créneau associé à cette réservation.");
        }
    
        return $result['heure_debut'];
    }
}