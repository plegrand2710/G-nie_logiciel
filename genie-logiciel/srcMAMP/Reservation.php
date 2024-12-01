<?php

class Reservation {
    private static $_ids = [];
    private int $_id;
    private Creneau $_creneau;
    private Activite $_activite;
    private Personne $_personne;
    private String $_statut;
    private DateTime $_dateExpiration;

    public function __construct($id, $creneau, $activite, $personne) {
        if (!is_int($id) || $id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier et positif.");
        }

        if (in_array($id, self::$_ids)) {
            throw new LogicException("L'ID $id est déjà utilisé par une autre réservation.");
        }

        if (!$creneau instanceof Creneau) {
            throw new InvalidArgumentException("Le créneau doit être une instance de la classe Creneau.");
        }

        if (!$activite instanceof Activite) {
            throw new InvalidArgumentException("L'activité doit être une instance de la classe Activite.");
        }

        if (!$personne instanceof Personne) {
            throw new InvalidArgumentException("L'utilisateur doit être une instance de la classe Personne.");
        }

        self::$_ids[] = $id;
        $this->_id = $id;
        $this->_creneau = $creneau;
        $this->_activite = $activite;
        $this->_personne = $personne;
        $this->_statut = 'en attente';
        $this->_dateExpiration = new DateTime();
        $this->_dateExpiration->modify('+24 hours');
    }

    public function getId() {
        return $this->_id;
    }

    public function getCreneau() {
        return $this->_creneau;
    }

    public function getActivite() {
        return $this->_activite;
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
    public static function getIds() {
        return self::$_ids;
    }
    
    public function setCreneau($creneau) {
        if (!$creneau instanceof Creneau) {
            throw new InvalidArgumentException("Le créneau doit être une instance de la classe Creneau.");
        }
        $this->_creneau = $creneau;
    }

    public function setPersonne($personne) {
        if (!$personne instanceof Personne) {
            throw new InvalidArgumentException("L'utilisateur doit être une instance de la classe Personne.");
        }

        $this->_personne = $personne;
    }

    public function setActivite($activite) {
        if (!$activite instanceof Activite) {
            throw new InvalidArgumentException("L'activité doit être une instance de la classe Activite.");
        }

        $this->_activite = $activite;
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

    public static function setIds($ids) {
        self::$_ids = $ids;
    }
    public static function reinitialiseIds(): void {
        self::$_ids = [];
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

        if (($key = array_search($this->_id, self::$_ids)) !== false) {
            unset(self::$_ids[$key]);
            self::$_ids = array_values(self::$_ids);
        }
    
        return new DateTime();
    }
}