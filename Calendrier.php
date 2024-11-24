<?php

class Calendrier {
    private $_horaireOuvertureSalle;
    private $_horaireFermetureSalle;
    private $_joursFermeture = [];

    public function __construct($horaireOuvertureSalle, $horaireFermetureSalle) {}

    public function visualisationCreneaux() {
    }

    public function ajouterJourFermeture($jour) {
        $this->_joursFermeture[] = $jour;
    }

    public function supprimerJourFermeture($jour) {
        $index = array_search($jour, $this->_joursFermeture);
        if ($index !== false) {
            unset($this->_joursFermeture[$index]);
        }
    }

    public function getHoraireOuvertureSalle() {
        return $this->_horaireOuvertureSalle;
    }

    public function setHoraireOuvertureSalle($horaireOuvertureSalle) {
        $this->_horaireOuvertureSalle = $horaireOuvertureSalle;
    }

    public function getHoraireFermetureSalle() {
        return $this->_horaireFermetureSalle;
    }

    public function setHoraireFermetureSalle($horaireFermetureSalle) {
        $this->_horaireFermetureSalle = $horaireFermetureSalle;
    }

    public function getJoursFermeture() {
        return $this->_joursFermeture;
    }

    public function setJoursFermeture($joursFermeture) {
        $this->_joursFermeture = $joursFermeture;
    }

    public function trouverGestionCreneauxPourActivite(Activite $activite): ?GestionCreneauxActivite {
        foreach ($this->_gestionCreneauxActivites as $gestion) {
            if ($gestion->getActivite()->getNom() === $activite->getNom()) {
                return $gestion;
            }
        }
        return null;
    }
}