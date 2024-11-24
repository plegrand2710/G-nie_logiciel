<?php

class Creneau {
    private $_date;
    private $_heureDebut;
    private $_heureFin;
    private $_occupe;

    public function __construct($date, $hDebut, $hFin) {}

    public function getDate() {
        return $this->_date;
    }

    public function setDate($date) {
        $this->_date = $date;
    }

    public function getHeureDebut() {
        return $this->_heureDebut;
    }

    public function setHeureDebut($heureDebut) {
        $this->_heureDebut = $heureDebut;
    }

    public function getHeureFin() {
        return $this->_heureFin;
    }

    public function setHeureFin($heureFin) {
        $this->_heureFin = $heureFin;
    }

    public function getOccupe() {
        return $this->_occupe;
    }

    public function setOccupe($occupe) {
        $this->_occupe = $occupe;
    }

    public function reserverCreneau() {}

    public function libererCreneau() {}
}
