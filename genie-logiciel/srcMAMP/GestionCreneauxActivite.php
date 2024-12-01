<?php

class GestionCreneauxActivite {
    private $_tableCreneau = [];
    private $_activite;

    public function __construct($activite){
        $this->_activite = $activite;
    }
    public function visualisationCreneauxActivite(): array {
        return [];
    }

    public function verifierDisponibilite($creneau) {
        // Vérifier la disponibilité d’un créneau
    }

    public function modifierCreneauActivite($creneau) {
        // Modifier un créneau en verifiant si c'est 1 creneau ou un tableau de creneaux
    }

    public function supprimerCreneauActivite($creneau) {
        // Supprimer un créneau en verifiant si c'est 1 creneau ou un tableau de creneaux
    }

    public function ajouterCreneauActivite($creneau) {
        // Ajouter un créneau en verifiant si c'est 1 creneau ou un tableau de creneaux
    }

    public function getTableCreneau() {
        return $this->_tableCreneau;
    }

    public function setTableCreneau($tableCreneau) {
        $this->_tableCreneau = $tableCreneau;
    }
}