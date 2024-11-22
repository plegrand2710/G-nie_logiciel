<?php

class GestionUtilisateur {
    private $_reservations = [];
    private $_calendrier;

    public function __construct(Calendrier $calendrier) {
        $this->_reservations = [];
        $this->_calendrier = $calendrier;
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
        $this->ajouterReservation($reservation);
    
        return true;
    }

    public function annulerReservation(Reservation $reservation): void {
        if ($reservation->getStatut() !== 'confirmée') {
            throw new LogicException("Seules les réservations confirmées peuvent être annulées.");
        }
    
        $creneau = $reservation->getCreneau();
        if ($creneau === null) {
            throw new LogicException("Le créneau associé est introuvable.");
        }
    
        $creneau->libererCreneau();
        $reservation->setStatut('annulée');
        $this->supprimerReservation($reservation);
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

    public function afficherReservations(): array {
        return $this->_reservations;
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