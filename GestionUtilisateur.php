<?php
class GestionUtilisateur {
    private $_reservations = [];
    private $_calendrier;
    private $_paiement;
    private $_remboursement;
    private static $_penalite = 50;

    public function __construct(Calendrier $calendrier) {
        $this->_reservations = [];
        $this->_calendrier = $calendrier;
        $this->_paiement = null ;
        $this->_remboursement = null ;
    }

    public function getReservations(): array {
        return $this->_reservations;
    }

    public function setReservations(array $reservations): void {
        $this->_reservations = $reservations;
    }

    public function getCalendrier(): Calendrier {
        return $this->_calendrier;
    }

    public function setCalendrier(Calendrier $calendrier): void {
        $this->_calendrier = $calendrier;
    }

    public function getPaiement(): array {
        return $this->_reservations;
    }

    public function setPaiement(Paiement $paiement): void {
        $this->_paiement = $paiement;
    }

    public static function getPenalite(): float {
        return self::$_penalite;
    }

    public static function setPenalite(Float $penalite): void {
        self::$_penalite = $penalite;
    }

    public function getRemboursement(): array {
        return $this->_remboursement;
    }

    public function setRemboursement(Remboursement $remboursement): void {
        $this->_remboursement = $remboursement;
    }

    public function reserver($creneau, $activite, $personne): bool {
        if (!$creneau instanceof Creneau) {
            throw new InvalidArgumentException("Le créneau doit être une instance de Creneau.");
        }

        if (!$activite instanceof Activite) {
            throw new InvalidArgumentException("L'activité doit être une instance de Activite.");
        }

        if (!$personne instanceof Personne) {
            throw new InvalidArgumentException("L'utilisateur doit être une instance de Personne.");
        }

        if ($personne instanceof Utilisateur) {
            if(!$personne->verifPayerCotisation()){
                throw new LogicException("La personne doit avoir une cotisation valide pour réserver.");
            }
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

        $this->effectuerPaiement($personne, $activite);

        $reservation->confirmerReservation();
        $this->ajouterReservation($reservation);

        return true;
    }

    private function effectuerPaiement(Personne $personne, Activite $activite): void {
        $montant = $activite->getTarif();
        $this->_paiement = new PaiementVirement($montant, new DateTime());

        if (!$paiement->effectuerPaiement()) {
            throw new LogicException("Le paiement a échoué.");
        }
    }

    public function annulerReservation(Reservation $reservation): void {
        if ($reservation->getStatut() !== 'confirmée' || $reservation->getStatut() !== 'en attente') {
            throw new LogicException("Seules les réservations confirmées peuvent être annulées.");
        }

        $creneau = $reservation->getCreneau();
        if ($creneau === null) {
            throw new LogicException("Le créneau associé est introuvable.");
        }

        $creneau->libererCreneau();
        $reservation->setStatut('annulée');
        if ($reservation->getStatut() == 'confirmée') {
            $penalite = $this->calculerPenalite($reservation);
            $this->effectuerRemboursement($reservation->getPersonne(), $reservation->getActivite(), $penalite);
        }

        
        $this->supprimerReservation($reservation);
    }

    private function calculerPenalite(Reservation $reservation): float {
        $heureActuelle = new DateTime();
        $creneauHeure = $reservation->getCreneau()->getDateHeure();

        $interval = $heureActuelle->diff($creneauHeure);
        $heuresAvant = $interval->h + ($interval->days * 24);

        return $heuresAvant < 24 ? self::$_penalite : 0;
    }

    private function effectuerRemboursement(Personne $personne, Activite $activite, float $penalite): void {
        $montant = $activite->getTarif() - $penalite;

        if ($montant <= 0) {
            throw new LogicException("Le montant du remboursement est nul ou négatif.");
        }

        $this->_remboursement = new Remboursement($montant);
        if (!$remboursement->effectuerRemboursement($montant)) {
            throw new LogicException("Le remboursement a échoué.");
        }
    }

    public function afficherCreneauxParActivite($personne, $activite): array {
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

        foreach ($this->_reservations as $existingReservation) {
            if ($existingReservation->getId() === $reservationId) {
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