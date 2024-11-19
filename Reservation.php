<?php

class Reservation {
    private static $_idsUtilises = [];
    private $_id;
    private $_creneau;
    private $_activite;
    private $_utilisateur;
    private $_statut;
    private $_dateCreation;
    private $_dateExpiration;

    public function __construct($id, $creneau, $activite, $utilisateur) {
        if (!is_int($id) || $id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif.");
        }

        if (in_array($id, self::$_idsUtilises)) {
            throw new LogicException("L'ID $id est déjà utilisé par une autre réservation.");
        }

        if (!$creneau instanceof Creneau) {
            throw new InvalidArgumentException("Le créneau doit être une instance de la classe Creneau.");
        }

        if (!$activite instanceof Activite) {
            throw new InvalidArgumentException("L'activité doit être une instance de la classe Activite.");
        }

        if (!$utilisateur instanceof Utilisateur) {
            throw new InvalidArgumentException("L'utilisateur doit être une instance de la classe Utilisateur.");
        }

        if (!$creneau->estCompatibleAvecActivite($activite)) {
            throw new LogicException("L'activité " . $activite->getNom() . " n'est pas disponible pour le créneau " . $creneau->getPlageHoraire() . ".");
        }

        self::$_idsUtilises[] = $id;
        $this->_id = $id;
        $this->_creneau = $creneau;
        $this->_activite = $activite;
        $this->_utilisateur = $utilisateur;
        $this->_statut = 'en attente';
        $this->_dateCreation = new DateTime();
        $this->_dateExpiration = $this->_dateCreation + 24;
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

    public function getUtilisateur() {
        return $this->_utilisateur;
    }

    public function getStatut() {
        return $this->_statut;
    }

    public function getDateExpiration() {
        return $this->_dateExpiration;
    }

    public function setCreneau($creneau) {
        if (!$creneau instanceof Creneau) {
            throw new InvalidArgumentException("Le créneau doit être une instance de la classe Creneau.");
        }

        if (!$creneau->estCompatibleAvecActivite($this->_activite)) {
            throw new LogicException("Le créneau " . $creneau->getPlageHoraire() . " n'est pas compatible avec l'activité actuelle.");
        }

        $this->_creneau = $creneau;
    }

    public function setUtilisateur($utilisateur) {
        if (!$utilisateur instanceof Utilisateur) {
            throw new InvalidArgumentException("L'utilisateur doit être une instance de la classe Utilisateur.");
        }

        $this->_utilisateur = $utilisateur;
    }

    public function setActivite($activite) {
        if (!$activite instanceof Activite) {
            throw new InvalidArgumentException("L'activité doit être une instance de la classe Activite.");
        }

        if (!$this->_creneau->estCompatibleAvecActivite($activite)) {
            throw new LogicException("L'activité " . $activite->getNom() . " n'est pas compatible avec le créneau actuel.");
        }

        $this->_activite = $activite;
    }

    public function setStatut($statut) {
        $statutsValides = ['en attente', 'confirmée', 'annulée', 'expirée'];
        if (!in_array($statut, $statutsValides)) {
            throw new InvalidArgumentException("Le statut doit être l'un des suivants : " . implode(', ', $statutsValides));
        }

        if ($statut === 'annulée' && $this->_statut === 'en attente') {
            throw new LogicException("Une réservation en attente ne peut pas être annulée.");
        }

        if ($statut === 'confirmée' && $this->_statut === 'en attente') {
            $this->_creneau->marquerOccupe();
        }

        if ($statut === 'annulée') {
            $this->_creneau->liberer();
        }

        $this->_statut = $statut;
    }

    public function setDateExpiration($dateExpiration) {
        if (!$dateExpiration instanceof DateTime) {
            throw new InvalidArgumentException("La date d'expiration doit être une instance de la classe DateTime.");
        }

        if ($dateExpiration < $this->_dateCreation) {
            throw new LogicException("La date d'expiration ne peut pas être antérieure à la date de création.");
        }

        $this->_dateExpiration = $dateExpiration;
    }

    public function verifierExpiration() {
        $now = new DateTime();
        if ($now > $this->_dateExpiration && $this->_statut === 'en attente') {
            $this->_statut = 'expirée';
            return true;
        }
        return false;
    }

    public function payerReservation() {
        if ($this->_statut !== 'en attente') {
            throw new LogicException("La réservation doit être en attente pour être payée.");
        }

        if (!$this->_activite->validerPaiement($this->_activite->getMontant())) {
            throw new InvalidArgumentException("Le paiement est incorrect.");
            return false ;
        }

        $this->_statut = 'confirmée';
        $this->_creneau->marquerOccupe();
        return true ;
    }

    public function mettreAJourEtatReservation($confirmation) {
        if($confirmation){
            $now = new DateTime();
            if ($now > $this->_dateExpiration && $this->_statut === 'en attente') {
                $this->_statut = 'expirée';
                $this->_creneau->liberer();
            }
        }
        else{
            throw new LogicException("La réservation n'est pas confirmée");
        }
    }

    public function confirmerReservation() {
        if (!$this->_utilisateur->aPayeCotisation()) {
            return false;
        }
    
        if (!$this->_activite->estPayee()) {
            return false;
        }
    
        if (!$this->_creneau->estDisponible()) {
            return false;
        }
    
        if ($this->_statut !== 'en attente') {
            return false;
        }
    
        if ($this->verifierExpiration()) {
            return false;
        }
    
        return true;
    }

    public function annulerReservation() {
        if ($this->_statut === 'annulée') {
            throw new LogicException("La réservation est déjà annulée.");
        }

        $this->_statut = 'annulée';
        return new DateTime();
    }
}