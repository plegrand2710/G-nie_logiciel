<?php

class Activite {
    private $_nom;
    private $_tarif;
    private $_duree;

    public function __construct($nom, $tarif, $duree) {}

    public function getNom() {
        return $this->_nom;
    }

    public function setNom($nom) {
        $this->_nom = $nom;
    }

    public function getTarif() {
        return $this->_tarif;
    }

    public function setTarif($tarif) {
        $this->_tarif = $tarif;
    }

    public function getDuree() {
        return $this->_duree;
    }

    public function setDuree($duree) {
        $this->_duree = $duree;
    }
}