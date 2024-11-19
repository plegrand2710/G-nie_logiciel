<?php

class GestionUtilisateur {
    private $_reservations;

    public function __construct() {
        $this->_reservations = [];
    }

    public function ajouterReservation($reservation) {
        if (!$reservation instanceof Reservation) {
            throw new InvalidArgumentException("L'objet fourni n'est pas une instance de Reservation.");
        }
        $this->_reservations[] = $reservation;
    }

    public function reserver($creneau, $activite, $utilisateur) {
        $creneau->verifierCreneau();
        $activite->verifierActivite();
        $utilisateur->verifierUtilisateur();

        if (!$this->verifierDisponibilite($creneau)) {
            throw new InvalidArgumentException("Le créneau " . $creneau->getPlageHoraire() . " est déjà pris.");
        }

        $reservation = new Reservation(
            count($this->_reservations) + 1,
            $creneau,
            $activite,
            $utilisateur
        );
        $this->ajouterReservation($reservation);
        return $reservation;
    }

    public function annulerReservation($reservationId) {
        foreach ($this->_reservations as $reservation) {
            if ($reservation->getId() === $reservationId) {
                $reservation->setStatut("annulée");
                return "Réservation $reservationId annulée.";
            }
        }
        throw new InvalidArgumentException("Réservation introuvable pour l'ID $reservationId.");
    }

    public function verifierDisponibilite($reservation) {
        if (!$reservation instanceof Reservation) {
            throw new InvalidArgumentException("L'objet fourni doit être une instance de Reservation.");
        }
    
        $creneau = $reservation->getCreneau();
        $activite = $reservation->getActivite();
        $utilisateur = $reservation->getUtilisateur();
    
        foreach ($this->_reservations as $res) {
            if ($res->getCreneau()->getPlageHoraire() === $creneau->getPlageHoraire()) {
                if ($res->getStatut() === 'confirmée') {
                    return false;
                }
    
                if ($res->getUtilisateur()->getId() !== $utilisateur->getId() &&
                    ($res->getStatut() === 'en attente' || $res->getStatut() === 'confirmée')) {
                    return false;
                }
            }
        }
    
        if (!$creneau->estCompatibleAvecActivite($activite)) {
            throw new LogicException("Le créneau " . $creneau->getPlageHoraire() . " n'est pas compatible avec l'activité " . $activite->getNom() . ".");
        }
    
        if (!$creneau->verifierCreneau()) {
            throw new LogicException("Le créneau fourni n'est pas valide.");
        }
    
        return true;
    }

    public function afficherCreneauxDisponiblesParActivite($creneaux, $activites) {
        $disponibilites = [];
    
        foreach ($activites as $activite) {
            if (!$activite instanceof Activite) {
                throw new InvalidArgumentException("Chaque activité doit être une instance de la classe Activite.");
            }
    
            $disponibilites[$activite->getNom()] = [];
    
            foreach ($creneaux as $creneau) {
                if (!$creneau instanceof Creneau) {
                    throw new InvalidArgumentException("Chaque créneau doit être une instance de la classe Creneau.");
                }
    
                if ($creneau->estCompatibleAvecActivite($activite)) {
                    $creneauLibre = true;
    
                    foreach ($this->_reservations as $reservation) {
                        if ($reservation->getCreneau()->getPlageHoraire() === $creneau->getPlageHoraire() &&
                            $reservation->getStatut() === 'confirmée') {
                            $creneauLibre = false;
                            break;
                        }
                    }
    
                    if ($creneauLibre) {
                        $disponibilites[$activite->getNom()][] = $creneau->getPlageHoraire();
                    }
                }
            }
        }
    
        return $disponibilites;
    }

    public function trouverReservationParId($reservationId) {
        foreach ($this->_reservations as $reservation) {
            if ($reservation->getId() === $reservationId) {
                return $reservation;
            }
        }
        throw new InvalidArgumentException("Réservation introuvable avec l'ID $reservationId.");
    }

    public function afficherCreneauxDisponibles() {
        $creneaux = [];
        foreach ($this->_reservations as $reservation) {
            $creneaux[] = $reservation->getCreneau()->getPlageHoraire();
        }
        return $creneaux;
    }

    public function getReservations() {
        return $this->_reservations;
    }
}