<?php
class GestionUtilisateur {
    private $_reservations = [];
    private $_calendrier;
    private $_paiement;
    private $_remboursement;
    private static $_penalite = 50;

    private static $_RIBEntreprise;

    public function __construct($calendrier) {
        if (!($calendrier instanceof Calendrier)) {
            throw new InvalidArgumentException("Le calendrier doit être une instance de Calendrier.");
        }

        $this->_reservations = [];
        $this->_calendrier = $calendrier;
        $this->_paiement = null;
        $this->_remboursement = null;

        self::$_RIBEntreprise = new RIB();
        self::$_RIBEntreprise->initialiseRib(1234, 789, 98, "IBAN_valide", "entreprise", "salle", 56);
    }


    public function getReservations() {
        return $this->_reservations;
    }

    public function setReservations($reservations) {
        if (!is_array($reservations)) {
            throw new InvalidArgumentException("Les réservations doivent être un tableau.");
        }

        foreach ($reservations as $reservation) {
            if (!($reservation instanceof Reservation)) {
                throw new InvalidArgumentException("Chaque élément doit être une instance de Reservation.");
            }
        }

        $this->_reservations = $reservations;
    }

    public function getCalendrier() {
        return $this->_calendrier;
    }

    public function setCalendrier($calendrier) {
        if (!($calendrier instanceof Calendrier)) {
            throw new InvalidArgumentException("Le calendrier doit être une instance de Calendrier.");
        }
        $this->_calendrier = $calendrier;
    }

    public function getPaiement() {
        return $this->_paiement;
    }

    public function setPaiement($paiement) {
        if (!($paiement instanceof Paiement)) {
            throw new InvalidArgumentException("Le paiement doit être une instance de Paiement.");
        }
        $this->_paiement = $paiement;
    }

    public static function getPenalite() {
        return self::$_penalite;
    }

    public static function setPenalite($penalite) {
        if (!is_float($penalite) && !is_int($penalite)) {
            throw new InvalidArgumentException("La pénalité doit être un nombre.");
        }
        self::$_penalite = (float) $penalite;
    }

    public function getRemboursement() {
        return $this->_remboursement;
    }

    public function setRemboursement($remboursement) {
        if (!($remboursement instanceof Remboursement)) {
            throw new InvalidArgumentException("Le remboursement doit être une instance de Remboursement.");
        }
        $this->_remboursement = $remboursement;
    }

    public function reserver($creneau, $activite, $personne) {
        if (!($creneau instanceof Creneau)) {
            throw new InvalidArgumentException("Le créneau doit être une instance de Creneau.");
        }

        if (!($activite instanceof Activite)) {
            throw new InvalidArgumentException("L'activité doit être une instance de Activite.");
        }

        if (!($personne instanceof Personne)) {
            throw new InvalidArgumentException("La personne doit être une instance de Personne.");
        }

        if ($personne instanceof Utilisateur && !$personne->verifPayerCotisation()) {
            throw new LogicException("La personne doit avoir une cotisation valide pour réserver.");
        }

        $gestionCreneaux = $this->_calendrier->trouverGestionCreneauxPourActivite($activite);
        if ($gestionCreneaux === null) {
            throw new LogicException("Aucune gestion de créneaux trouvée pour cette activité.");
        }

        if (!$gestionCreneaux->verifierDisponibilite($creneau)) {
            throw new LogicException("Le créneau demandé n'est pas disponible.");
        }

        $creneau->reserverCreneau();

        $reservation = new Reservation(
            count($this->_reservations) + 1,
            $creneau,
            $activite,
            $personne
        );

        $this->paiementActivite($personne, $activite);

        if (!$reservation->confirmerReservation()) {
            throw new LogicException("La réservation n'a pas pu être confirmée.");
        }

        $this->ajouterReservation($reservation);

        return true;
    }

    public function paiementActivite($personne, $activite) {
        if (!($personne instanceof Personne)) {
            throw new InvalidArgumentException("La personne doit être une instance de Personne.");
        }

        if (!($activite instanceof Activite)) {
            throw new InvalidArgumentException("L'activité doit être une instance de Activite.");
        }

        $montant = $activite->getTarif();

        $this->_paiement = new PaiementVirement($montant, new DateTime(), $personne->getRIB(), self::$_RIBEntreprise);

        if (!$this->_paiement->effectuerPaiement()) {
            throw new LogicException("Le paiement a échoué.");
        }
    }

    public function annulerReservation($reservation) {
        if (!($reservation instanceof Reservation)) {
            throw new InvalidArgumentException("La réservation doit être une instance de Reservation.");
        }

        if ($reservation->getStatut() !== 'confirmée' && $reservation->getStatut() !== 'en attente') {
            throw new LogicException("Seules les réservations confirmées ou en attente peuvent être annulées.");
        }

        $creneau = $reservation->getCreneau();
        if (!$creneau instanceof Creneau) {
            throw new LogicException("Le créneau associé à la réservation est introuvable.");
        }

        $creneau->libererCreneau();
        $reservation->annulerReservation();

        if ($reservation->getStatut() == 'confirmée') {
            $penalite = $this->calculerPenalite($reservation);
            $this->remboursementActivite($reservation->getPersonne(), $reservation->getActivite(), $penalite);
        } 

        $this->supprimerReservation($reservation);
    }

    private function calculerPenalite(Reservation $reservation): float {
        $heureActuelle = new DateTime();
        $creneauHeure = $reservation->getCreneau()->getHeureDebut();
    
        if (!$creneauHeure instanceof DateTimeInterface) {
            throw new LogicException("L'heure de début du créneau est introuvable ou invalide.");
        }
    
        $interval = $heureActuelle->diff($creneauHeure);
        $heuresAvant = $interval->h + ($interval->days * 24);
    
        return $heuresAvant < 24 ? self::$_penalite : 0;
    }

    public function remboursementActivite($personne, $activite, $penalite): void {
        if (!($personne instanceof Personne)) {
            throw new InvalidArgumentException("La personne doit être une instance de Personne.");
        }

        if (!($activite instanceof Activite)) {
            throw new InvalidArgumentException("L'activité doit être une instance de Activite.");
        }

        if (!is_float($penalite) && !is_int($penalite)) {
            throw new InvalidArgumentException("La pénalité doit être un nombre.");
        }

        $this->_remboursement = new Remboursement(new DateTime(), $activite->getTarif(), $penalite);

        if (!$this->_remboursement->effectuerRemboursement()) {
            throw new LogicException("Le remboursement a échoué.");
        }
    }

    public function afficherCreneauxParActiviteParPersonne($personne, $activite): array {
        if (!$personne instanceof Personne) {
            throw new InvalidArgumentException("La personne doit être une instance de Personne.");
        }
        if (!$activite instanceof Activite) {
            throw new InvalidArgumentException("L'activite doit être une instance de Activite.");
        }
    
        $creneaux = [];
        foreach ($this->_reservations as $reservation) {
            if ($reservation->getPersonne() === $personne && $reservation->getActivite() === $activite) {
                $creneaux[] = $reservation->getCreneau();
            }
        }
    
        return $creneaux;
    }

    public function afficherCreneauxDisponiblesParActivite($activite): array {
        if (!$activite instanceof Activite) {
            throw new InvalidArgumentException("L'activité doit être une instance de Activite.");
        }
    
        $gestionCreneaux = $this->_calendrier->trouverGestionCreneauxPourActivite($activite);
        if ($gestionCreneaux === null) {
            throw new LogicException("Aucune gestion de créneaux trouvée pour cette activité.");
        }
    
        return $gestionCreneaux->visualisationCreneauxActivite();
    }

    public function ajouterReservation($reservation): void {
        if (!$reservation instanceof Reservation) {
            throw new InvalidArgumentException("La reservation doit être une instance de Reservation.");
        }
        $reservationId = $reservation->getId();
        if (!is_int($reservationId) || $reservationId <= 0) {
            throw new InvalidArgumentException("L'ID de la réservation doit être un entier positif.");
        }

        foreach ($this->_reservations as $existeReservation) {
            if ($existeReservation->getId() === $reservationId) {
                throw new InvalidArgumentException("Une réservation avec cet ID existe déjà.");
            }
        }
        $this->_reservations[] = $reservation;
    }

    public function supprimerReservation($reservation): void {
        if (!$reservation instanceof Reservation) {
            throw new InvalidArgumentException("La reservation doit être une instance de Reservation.");
        }
        $reservationId = $reservation->getId();
        foreach ($this->_reservations as $key => $reservation) {
            if ($reservation->getId() === $reservationId) {
                unset($this->_reservations[$key]);
                $this->_reservations = array_values($this->_reservations);
                $reservation->annulerReservation();
                return;
            }
        }

        throw new InvalidArgumentException("Aucune réservation trouvée avec l'ID spécifié.");
    }

    public function afficherReservationsParUtilisateur($personne): array {
        if (!$personne instanceof Personne) {
            throw new InvalidArgumentException("La personne doit être une instance de Personne.");
        }
        $reservationsUtilisateur = [];
        foreach ($this->_reservations as $reservation) {
            if ($reservation->getPersonne() === $personne) {
                $reservationsUtilisateur[] = $reservation;
            }
        }
        return $reservationsUtilisateur;
    }
    
    public function afficherTousLesCreneauxPourUtilisateur($personne): array {
        if (!$personne instanceof Personne) {
            throw new InvalidArgumentException("La personne doit être une instance de Personne.");
        }
    
        $creneaux = [];
        foreach ($this->_reservations as $reservation) {
            if ($reservation->getPersonne() === $personne) {
                $creneaux[] = $reservation->getCreneau();
            }
        }
    
        return $creneaux;
    }

    public function afficherReservationsUtilisateurParActivite($personne, $activite): array {
        if (!$personne instanceof Personne) {
            throw new InvalidArgumentException("La personne doit être une instance de Personne.");
        }
        if (!$activite instanceof Activite) {
            throw new InvalidArgumentException("L'activite doit être une instance de Activite.");
        }
        $reservationsUtilisateur = [];
        foreach ($this->_reservations as $reservation) {
            if ($reservation->getPersonne() === $personne && $reservation->getActivite() === $activite) {
                $reservationsUtilisateur[] = $reservation;
            }
        }
        return $reservationsUtilisateur;
    }
    
}