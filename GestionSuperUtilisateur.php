<?php

class GestionSuperUtilisateur extends GestionUtilisateur {
    public function __construct() {
        parent::__construct();
    }

    public function modification_creneau($nouveauxCreneaux, $activite = null) {
        if (!is_array($nouveauxCreneaux)) {
            throw new InvalidArgumentException("Les nouveaux créneaux doivent être un tableau.");
        }

        foreach ($nouveauxCreneaux as $creneau) {
            if (!$creneau instanceof Creneau) {
                throw new InvalidArgumentException("Tous les éléments doivent être des instances de la classe Creneau.");
            }
        }

        if ($activite !== null) {
            if (!$activite instanceof Activite) {
                throw new InvalidArgumentException("L'activité doit être une instance de la classe Activite.");
            }
            $activite->setCreneauxDisponibles($nouveauxCreneaux);
        } else {
            foreach (Activite::getToutesLesActivites() as $act) {
                $act->setCreneauxDisponibles($nouveauxCreneaux);
            }
        }
    }

    public function modifier_activite($activite, $nouveauNom = null, $nouveauTarif = null, $nouvelleDescription = null) {
        if (!$activite instanceof Activite) {
            throw new InvalidArgumentException("L'activité doit être une instance de la classe Activite.");
        }

        if ($nouveauNom !== null) {
            if (!is_string($nouveauNom) || empty($nouveauNom)) {
                throw new InvalidArgumentException("Le nom de l'activité doit être une chaîne non vide.");
            }
            $activite->setNom($nouveauNom);
        }

        if ($nouveauTarif !== null) {
            if (!is_numeric($nouveauTarif) || $nouveauTarif <= 0) {
                throw new InvalidArgumentException("Le tarif doit être un nombre positif.");
            }
            $activite->setTarif($nouveauTarif);
        }

        if ($nouvelleDescription !== null) {
            if (!is_string($nouvelleDescription)) {
                throw new InvalidArgumentException("La description doit être une chaîne de caractères.");
            }
            $activite->setDescription($nouvelleDescription);
        }
    }

    public function modifier_reservation($reservationId, $nouveauCreneau = null, $nouvelleActivite = null) {
        $reservation = $this->trouverReservationParId($reservationId);

        if ($reservation->getStatut() === 'expirée' || $reservation->getStatut() === 'annulée') {
            throw new LogicException("Impossible de modifier une réservation expirée ou annulée.");
        }

        if ($nouveauCreneau !== null) {
            if (!$nouveauCreneau instanceof Creneau) {
                throw new InvalidArgumentException("Le créneau doit être une instance de la classe Creneau.");
            }

            if (!$nouveauCreneau->estCompatibleAvecActivite($reservation->getActivite())) {
                throw new LogicException("Le créneau n'est pas compatible avec l'activité de la réservation.");
            }

            $reservation->setCreneau($nouveauCreneau);
        }

        if ($nouvelleActivite !== null) {
            if (!$nouvelleActivite instanceof Activite) {
                throw new InvalidArgumentException("L'activité doit être une instance de la classe Activite.");
            }

            if (!$reservation->getCreneau()->estCompatibleAvecActivite($nouvelleActivite)) {
                throw new LogicException("L'activité n'est pas compatible avec le créneau de la réservation.");
            }

            $reservation->setActivite($nouvelleActivite);
        }
    }
}